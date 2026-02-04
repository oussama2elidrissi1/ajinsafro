<?php

namespace Ajinsafro\Admin;

use Ajinsafro\Core\Options;

/**
 * Global importer for all Laravel tours to WordPress.
 */
class GlobalImporter
{
    /**
     * Import all tours from Laravel.
     *
     * @return array ['success' => bool, 'imported' => int, 'updated' => int, 'errors' => array]
     */
    public static function importAllTours()
    {
        $laravel_base_url = Options::get('laravel_base_url', 'https://booking.ajinsafro.net');
        $endpoint = rtrim($laravel_base_url, '/') . '/api/public/tours';

        // Fetch all tours from Laravel
        $response = wp_remote_get($endpoint, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'errors' => [$response->get_error_message()],
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['success']) || !$data['success']) {
            return [
                'success' => false,
                'imported' => 0,
                'updated' => 0,
                'errors' => ['Invalid response from Laravel'],
            ];
        }

        $tours = $data['data'] ?? [];
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($tours as $tour) {
            try {
                $result = self::importSingleTour($tour);
                if ($result['action'] === 'created') {
                    $imported++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Tour {$tour['id']}: " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Import a single tour.
     *
     * @param array $tour
     * @return array
     */
    protected static function importSingleTour($tour)
    {
        global $wpdb;

        $laravel_id = absint($tour['id']);
        
        // Check if tour already exists by Laravel ID
        $existing_post = get_posts([
            'post_type' => 'st_tours',
            'meta_key' => '_aj_laravel_voyage_id',
            'meta_value' => $laravel_id,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);

        $post_data = [
            'post_title' => sanitize_text_field($tour['name']),
            'post_name' => sanitize_title($tour['slug']),
            'post_content' => wp_kses_post($tour['description'] ?? ''),
            'post_excerpt' => sanitize_text_field($tour['accroche'] ?? ''),
            'post_type' => 'st_tours',
            'post_status' => $tour['status'] === 'actif' ? 'publish' : 'draft',
        ];

        // Set sync lock to prevent pushing back to Laravel
        $temp_lock = true;

        if (!empty($existing_post)) {
            $post_id = $existing_post[0]->ID;
            $post_data['ID'] = $post_id;
            
            // Set lock before update
            update_post_meta($post_id, '_aj_sync_lock', '1');
            
            wp_update_post($post_data);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            
            // Set lock for new post
            update_post_meta($post_id, '_aj_sync_lock', '1');
            
            $action = 'created';
        }

        if (is_wp_error($post_id)) {
            throw new \Exception($post_id->get_error_message());
        }

        // Update meta
        update_post_meta($post_id, '_aj_laravel_voyage_id', $laravel_id);
        update_post_meta($post_id, 'address', sanitize_text_field($tour['destination'] ?? ''));
        update_post_meta($post_id, 'duration_day', sanitize_text_field($tour['duration_text'] ?? ''));

        // Update st_tours custom table
        $price = absint($tour['price_from'] ?? 0);
        
        $wpdb->replace(
            $wpdb->prefix . 'st_tours',
            [
                'post_id' => $post_id,
                'adult_price' => $price,
                'min_price' => $price,
                'address' => sanitize_text_field($tour['destination'] ?? ''),
                'duration_day' => sanitize_text_field($tour['duration_text'] ?? ''),
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );

        // Compute and store sync hash
        $sync_hash = hash('sha256', json_encode([
            'name' => $tour['name'],
            'slug' => $tour['slug'],
            'description' => $tour['description'] ?? '',
            'destination' => $tour['destination'] ?? '',
            'price_from' => $tour['price_from'] ?? 0,
        ]));
        update_post_meta($post_id, '_aj_sync_hash', $sync_hash);

        // Remove lock after all updates
        delete_post_meta($post_id, '_aj_sync_lock');

        return [
            'post_id' => $post_id,
            'laravel_id' => $laravel_id,
            'action' => $action,
        ];
    }
}
