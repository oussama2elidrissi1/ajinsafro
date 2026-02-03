<?php

namespace Ajinsafro\Sync;

class TourSyncer
{
    private $table_prefix;
    private $custom_table;

    public function __construct()
    {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix;
        $this->custom_table = $this->table_prefix . 'st_tours';
    }

    public function upsert($data)
    {
        global $wpdb;

        $laravel_id = absint($data['laravel_id'] ?? 0);
        $slug = sanitize_title($data['slug'] ?? '');
        $title = sanitize_text_field($data['title'] ?? '');
        $content = wp_kses_post($data['content_html'] ?? '');

        if (!$laravel_id || !$slug || !$title) {
            throw new \Exception('Missing required fields: laravel_id, slug, title');
        }

        // Check if post already exists by slug or meta
        $existing_post = $this->find_existing_post($laravel_id, $slug);

        $post_data = [
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $content,
            'post_type' => 'st_tours',
            'post_status' => 'publish',
            'meta_input' => [
                '_aj_laravel_voyage_id' => $laravel_id,
            ],
        ];

        // Set sync lock to prevent WordPress → Laravel push during this update
        if ($existing_post) {
            update_post_meta($existing_post->ID, '_aj_sync_lock', '1');
        }

        if ($existing_post) {
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
            // Set lock for new posts too
            update_post_meta($post_id, '_aj_sync_lock', '1');
        }

        if (is_wp_error($post_id)) {
            throw new \Exception('Failed to create/update post: ' . $post_id->get_error_message());
        }

        // Update meta data
        $this->update_tour_meta($post_id, $data);

        // Handle images
        if (isset($data['images'])) {
            $this->handle_images($post_id, $data['images']);
        }

        // Update custom table
        $this->update_custom_table($post_id, $data);

        // Store sync hash to prevent unnecessary pushes
        if (isset($data['sync_hash'])) {
            update_post_meta($post_id, '_aj_sync_hash', $data['sync_hash']);
        }

        // Remove sync lock after all updates are done
        delete_post_meta($post_id, '_aj_sync_lock');

        return [
            'post_id' => $post_id,
            'wp_post_id' => $post_id, // For Laravel response
            'laravel_id' => $laravel_id,
            'action' => $existing_post ? 'updated' : 'created',
        ];
    }

    public function delete($data)
    {
        $laravel_id = absint($data['laravel_id'] ?? 0);

        if (!$laravel_id) {
            throw new \Exception('Missing laravel_id');
        }

        $existing_post = $this->find_existing_post($laravel_id, '');

        if (!$existing_post) {
            return ['message' => 'Post not found'];
        }

        wp_delete_post($existing_post->ID, true);

        return [
            'post_id' => $existing_post->ID,
            'laravel_id' => $laravel_id,
            'action' => 'deleted',
        ];
    }

    private function find_existing_post($laravel_id, $slug)
    {
        // Try by Laravel ID first
        $posts = get_posts([
            'post_type' => 'st_tours',
            'meta_key' => '_aj_laravel_voyage_id',
            'meta_value' => $laravel_id,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);

        if (!empty($posts)) {
            return $posts[0];
        }

        // Try by slug
        if ($slug) {
            $post = get_page_by_path($slug, OBJECT, 'st_tours');
            if ($post) {
                return $post;
            }
        }

        return null;
    }

    private function update_tour_meta($post_id, $data)
    {
        $meta_fields = [
            'address' => sanitize_text_field($data['address'] ?? ''),
            'duration_day' => sanitize_text_field($data['duration_day'] ?? ''),
            'adult_price' => absint($data['adult_price'] ?? 0),
            'child_price' => absint($data['child_price'] ?? 0),
            'is_featured' => sanitize_text_field($data['is_featured'] ?? 'off'),
            'is_sale_schedule' => sanitize_text_field($data['is_sale_schedule'] ?? 'off'),
            'discount_type' => sanitize_text_field($data['discount_type'] ?? ''),
        ];

        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
    }

    private function handle_images($post_id, $images)
    {
        // Featured image
        if (!empty($images['featured'])) {
            $attachment_id = $this->import_image($images['featured'], $post_id);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
                update_post_meta($post_id, '_thumbnail_id', $attachment_id);
            }
        }

        // Gallery images
        if (!empty($images['gallery']) && is_array($images['gallery'])) {
            $gallery_ids = [];
            foreach ($images['gallery'] as $image_url) {
                $attachment_id = $this->import_image($image_url, $post_id);
                if ($attachment_id) {
                    $gallery_ids[] = $attachment_id;
                }
            }

            if (!empty($gallery_ids)) {
                update_post_meta($post_id, 'gallery', implode(',', $gallery_ids));
            }
        }
    }

    private function import_image($url, $post_id)
    {
        if (empty($url)) {
            return null;
        }

        // Check if already imported
        $existing = $this->find_attachment_by_url($url);
        if ($existing) {
            return $existing;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return null;
        }

        $file_array = [
            'name' => basename($url),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return null;
        }

        // Store original URL for deduplication
        update_post_meta($attachment_id, '_aj_original_url', $url);

        return $attachment_id;
    }

    private function find_attachment_by_url($url)
    {
        global $wpdb;

        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_aj_original_url' AND meta_value = %s LIMIT 1",
            $url
        ));

        return $attachment_id ? (int) $attachment_id : null;
    }

    private function update_custom_table($post_id, $data)
    {
        global $wpdb;

        $table = $this->custom_table;

        // Check if row exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$table} WHERE post_id = %d",
            $post_id
        ));

        $row_data = [
            'post_id' => $post_id,
            'address' => sanitize_text_field($data['address'] ?? ''),
            'adult_price' => absint($data['adult_price'] ?? 0),
            'child_price' => absint($data['child_price'] ?? 0),
            'price' => absint($data['adult_price'] ?? 0), // Same as adult_price
            'min_price' => absint($data['adult_price'] ?? 0), // Same as adult_price
            'duration_day' => sanitize_text_field($data['duration_day'] ?? ''),
            'is_featured' => sanitize_text_field($data['is_featured'] ?? 'off'),
            'is_sale_schedule' => sanitize_text_field($data['is_sale_schedule'] ?? 'off'),
            'discount_type' => sanitize_text_field($data['discount_type'] ?? ''),
        ];

        if ($existing) {
            $wpdb->update($table, $row_data, ['post_id' => $post_id]);
        } else {
            $wpdb->insert($table, $row_data);
        }
    }
}
