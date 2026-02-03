<?php

namespace Ajinsafro\Sync;

use Ajinsafro\Core\Options;

/**
 * Push WordPress tour changes to Laravel.
 */
class LaravelPushSync
{
    /**
     * Initialize hooks.
     */
    public function __construct()
    {
        // Hook into post save/update
        add_action('save_post_st_tours', [$this, 'onSaveTour'], 20, 3);
        
        // Hook into post deletion
        add_action('before_delete_post', [$this, 'onDeleteTour'], 10, 2);
    }

    /**
     * Handle tour save/update.
     *
     * @param int $post_id
     * @param \WP_Post $post
     * @param bool $update
     */
    public function onSaveTour($post_id, $post, $update)
    {
        // Check if Laravel sync is enabled
        if (!Options::get('enable_laravel_sync', false)) {
            return;
        }

        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Skip if sync lock exists (prevents loop during Laravel->WP update)
        if (get_post_meta($post_id, '_aj_sync_lock', true)) {
            error_log("[LaravelPushSync] Skipping sync - lock exists for post {$post_id}");
            return;
        }

        // Build payload
        $payload = $this->buildTourPayload($post_id, $post);
        
        // Compute sync hash
        $syncHash = hash('sha256', json_encode($payload));
        $payload['sync_hash'] = $syncHash;

        // Check if data changed (compare with stored hash)
        $storedHash = get_post_meta($post_id, '_aj_sync_hash', true);
        if ($storedHash === $syncHash) {
            error_log("[LaravelPushSync] Skipping sync - no changes detected for post {$post_id}");
            return;
        }

        // Push to Laravel
        $result = $this->pushToLaravel($payload, 'upsert');

        if ($result['success']) {
            // Update sync hash after successful push
            update_post_meta($post_id, '_aj_sync_hash', $syncHash);
            update_post_meta($post_id, '_aj_last_synced', current_time('mysql'));
            
            error_log("[LaravelPushSync] Successfully synced post {$post_id} to Laravel");
        } else {
            error_log("[LaravelPushSync] Failed to sync post {$post_id}: " . $result['message']);
        }
    }

    /**
     * Handle tour deletion.
     *
     * @param int $post_id
     * @param \WP_Post $post
     */
    public function onDeleteTour($post_id, $post)
    {
        if ($post->post_type !== 'st_tours') {
            return;
        }

        // Check if Laravel sync is enabled
        if (!Options::get('enable_laravel_sync', false)) {
            return;
        }

        // Skip if sync lock exists
        if (get_post_meta($post_id, '_aj_sync_lock', true)) {
            return;
        }

        $payload = [
            'action' => 'delete',
            'entity_type' => 'tour',
            'source' => 'wp',
            'wp_post_id' => $post_id,
        ];

        $result = $this->pushToLaravel($payload, 'delete');

        if ($result['success']) {
            error_log("[LaravelPushSync] Successfully deleted post {$post_id} from Laravel");
        } else {
            error_log("[LaravelPushSync] Failed to delete post {$post_id} from Laravel: " . $result['message']);
        }
    }

    /**
     * Build tour payload for Laravel.
     *
     * @param int $post_id
     * @param \WP_Post $post
     * @return array
     */
    protected function buildTourPayload($post_id, $post)
    {
        global $wpdb;

        // Get st_tours data
        $st_tour = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}st_tours WHERE post_id = %d",
            $post_id
        ));

        // Get post meta
        $address = get_post_meta($post_id, 'address', true);
        $duration_day = get_post_meta($post_id, 'duration_day', true);
        $adult_price = get_post_meta($post_id, 'adult_price', true);
        $child_price = get_post_meta($post_id, 'child_price', true);
        $min_price = get_post_meta($post_id, 'min_price', true);

        // Determine destination
        $destination = null;
        if ($st_tour && !empty($st_tour->address)) {
            $destination = $st_tour->address;
        } elseif (!empty($address)) {
            $destination = $address;
        }

        // Determine duration
        $duration_text = null;
        if ($st_tour && !empty($st_tour->duration_day)) {
            $duration_text = $st_tour->duration_day;
        } elseif (!empty($duration_day)) {
            $duration_text = $duration_day;
        }

        // Determine price (first non-null)
        $price_from = null;
        if ($st_tour && !empty($st_tour->adult_price) && $st_tour->adult_price > 0) {
            $price_from = (int) $st_tour->adult_price;
        } elseif ($st_tour && !empty($st_tour->min_price) && $st_tour->min_price > 0) {
            $price_from = (int) $st_tour->min_price;
        } elseif (!empty($adult_price) && $adult_price > 0) {
            $price_from = (int) $adult_price;
        } elseif (!empty($min_price) && $min_price > 0) {
            $price_from = (int) $min_price;
        }

        return [
            'action' => 'upsert',
            'entity_type' => 'tour',
            'source' => 'wp',
            'wp_post_id' => $post_id,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'destination' => $destination,
            'duration_text' => $duration_text,
            'price_from' => $price_from,
            'old_price' => null,
            'currency' => 'MAD',
            'status' => $post->post_status,
        ];
    }

    /**
     * Push payload to Laravel.
     *
     * @param array $payload
     * @param string $endpoint 'upsert' or 'delete'
     * @return array ['success' => bool, 'message' => string]
     */
    protected function pushToLaravel($payload, $endpoint = 'upsert')
    {
        $base_url = Options::get('laravel_sync_base_url', 'https://booking.ajinsafro.net');
        $token = Options::get('laravel_webhook_token', Options::get('hmac_secret', ''));

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Laravel webhook token not configured',
            ];
        }

        $url = rtrim($base_url, '/') . '/api/sync/wp-to-laravel';
        if ($endpoint === 'delete') {
            $url .= '/delete';
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($payload),
            'timeout' => 15,
            'sslverify' => true,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code >= 200 && $status_code < 300) {
            return [
                'success' => true,
                'message' => $data['message'] ?? 'Success',
                'data' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $data['message'] ?? "HTTP {$status_code}",
        ];
    }
}
