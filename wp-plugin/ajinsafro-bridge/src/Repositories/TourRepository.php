<?php
/**
 * Tour Repository
 *
 * Handles retrieval of tour data from WordPress (wp_posts + wp_postmeta)
 * including Traveler theme specific meta fields.
 *
 * @package AjinsafroBridge\Repositories
 */

namespace AjinsafroBridge\Repositories;

class TourRepository
{
    /**
     * Essential Traveler theme meta keys for tours
     * @var array
     */
    private array $metaKeys = [
        // Location
        'address',
        'st_location_id',
        'map_lat',
        'map_lng',
        'map_zoom',

        // Pricing
        'adult_price',
        'child_price',
        'infant_price',
        'base_price',
        'discount',
        'sale_price',

        // Duration & Capacity
        'duration_day',
        'duration',
        'max_people',
        'min_people',

        // Tour info
        'type_tour',
        'tours_program',
        'tour_external_booking_link',
        'included',
        'excluded',

        // Gallery
        'gallery',
        'st_gallery',

        // Reviews
        'rate_review',
        'review_score',

        // Booking
        'is_featured',
        'allow_deposit',
        'deposit_type',
        'deposit_amount',

        // Extras
        'faqs',
        'video',
        'cancellation_policy',
    ];

    /**
     * Get tour by post ID
     *
     * @param int $postId
     * @return array|null Tour data array or null if not found
     */
    public function getById(int $postId): ?array
    {
        $post = get_post($postId);

        if (!$post || $post->post_type !== AJBRIDGE_POST_TYPE) {
            return null;
        }

        return $this->formatTourData($post);
    }

    /**
     * Get tours for archive (paginated)
     *
     * @param array $args Query arguments
     * @return array Array of tour data
     */
    public function getArchive(array $args = []): array
    {
        $defaults = [
            'post_type' => AJBRIDGE_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $query = new \WP_Query($args);

        $tours = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $tours[] = $this->formatTourData(get_post());
            }
            wp_reset_postdata();
        }

        return [
            'tours' => $tours,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $args['paged'],
        ];
    }

    /**
     * Format tour data into a clean array
     *
     * @param \WP_Post $post
     * @return array
     */
    private function formatTourData(\WP_Post $post): array
    {
        // Get all meta at once for performance
        $meta = $this->getAllMeta($post->ID);

        // Get featured image (hero _tour_hero_image_id first, then _thumbnail_id)
        $featured_image = $this->getFeaturedImage($post->ID, $meta);

        // Get gallery images
        $gallery = $this->getGallery($meta);

        // Get taxonomies
        $taxonomies = $this->getTaxonomies($post->ID);

        return [
            // Basic post data
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => $this->getExcerpt($post),
            'permalink' => get_permalink($post),
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'date' => $post->post_date,

            // Images
            'featured_image' => $featured_image,
            'gallery' => $gallery,

            // Location
            'address' => $meta['address'] ?? '',
            'location_id' => (int) ($meta['st_location_id'] ?? 0),
            'map_lat' => $meta['map_lat'] ?? '',
            'map_lng' => $meta['map_lng'] ?? '',
            'map_zoom' => (int) ($meta['map_zoom'] ?? 14),

            // Pricing
            'adult_price' => $this->formatPrice($meta['adult_price'] ?? 0),
            'child_price' => $this->formatPrice($meta['child_price'] ?? 0),
            'infant_price' => $this->formatPrice($meta['infant_price'] ?? 0),
            'base_price' => $this->formatPrice($meta['base_price'] ?? 0),
            'discount' => (float) ($meta['discount'] ?? 0),
            'sale_price' => $this->formatPrice($meta['sale_price'] ?? 0),
            'has_discount' => !empty($meta['discount']) && $meta['discount'] > 0,

            // Duration & Capacity
            'duration_day' => (int) ($meta['duration_day'] ?? 0),
            'duration' => $meta['duration'] ?? '',
            'max_people' => (int) ($meta['max_people'] ?? 0),
            'min_people' => (int) ($meta['min_people'] ?? 0),

            // Tour info
            'type_tour' => $meta['type_tour'] ?? 'daily_tour',
            'tours_program' => $this->unserializeSafe($meta['tours_program'] ?? ''),
            'external_booking_link' => $meta['tour_external_booking_link'] ?? '',
            'included' => $meta['included'] ?? '',
            'excluded' => $meta['excluded'] ?? '',

            // Reviews
            'rate_review' => (float) ($meta['rate_review'] ?? 0),
            'review_score' => (float) ($meta['review_score'] ?? 0),

            // Flags
            'is_featured' => ($meta['is_featured'] ?? 'off') === 'on',

            // Booking settings
            'allow_deposit' => ($meta['allow_deposit'] ?? 'no') === 'yes',
            'deposit_type' => $meta['deposit_type'] ?? 'percent',
            'deposit_amount' => (float) ($meta['deposit_amount'] ?? 0),

            // Extras
            'faqs' => $this->unserializeSafe($meta['faqs'] ?? ''),
            'video' => $meta['video'] ?? '',
            'cancellation_policy' => $meta['cancellation_policy'] ?? '',

            // Taxonomies
            'categories' => $taxonomies['tours_cat'] ?? [],
            'tour_types' => $taxonomies['st_tour_type'] ?? [],
            'tags' => $taxonomies['tour_tag'] ?? [],

            // Raw meta for custom access
            '_raw_meta' => $meta,
        ];
    }

    /**
     * Get all meta values for a post
     *
     * @param int $postId
     * @return array
     */
    private function getAllMeta(int $postId): array
    {
        global $wpdb;

        $meta = [];

        // Get all meta in single query
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
                $postId
            ),
            ARRAY_A
        );

        if ($results) {
            foreach ($results as $row) {
                // Only include our target keys or prefix (st_, tour_, etc.)
                $key = $row['meta_key'];
                if (in_array($key, $this->metaKeys) || strpos($key, 'st_') === 0 || strpos($key, 'tour') === 0) {
                    $meta[$key] = $row['meta_value'];
                }
            }
        }

        return $meta;
    }

    /**
     * Get featured image data (hero image first: _tour_hero_image_id, then _thumbnail_id).
     *
     * @param int $postId
     * @param array $meta Post meta (for _tour_hero_image_id)
     * @return array
     */
    private function getFeaturedImage(int $postId, array $meta = []): array
    {
        $attachment_id = 0;

        // 1) Image principale du voyage (Hero / Cover) – priorité
        $hero_id = isset($meta['_tour_hero_image_id']) ? (int) $meta['_tour_hero_image_id'] : 0;
        if ($hero_id > 0) {
            $url = wp_get_attachment_image_url($hero_id, 'full');
            if ($url) {
                $attachment_id = $hero_id;
            }
        }

        // 2) Image à la une WordPress
        if ($attachment_id === 0) {
            $attachment_id = get_post_thumbnail_id($postId);
        }

        if (!$attachment_id) {
            return [
                'id' => 0,
                'url' => '',
                'alt' => '',
                'sizes' => [],
            ];
        }

        return [
            'id' => $attachment_id,
            'url' => wp_get_attachment_image_url($attachment_id, 'full') ?: '',
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'sizes' => [
                'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
                'large' => wp_get_attachment_image_url($attachment_id, 'large'),
                'full' => wp_get_attachment_image_url($attachment_id, 'full'),
            ],
        ];
    }

    /**
     * Get gallery images
     *
     * @param array $meta
     * @return array
     */
    private function getGallery(array $meta): array
    {
        $gallery = [];

        // Check both gallery meta keys
        $gallery_value = $meta['gallery'] ?? $meta['st_gallery'] ?? '';

        if (empty($gallery_value)) {
            return $gallery;
        }

        // Gallery can be serialized array or comma-separated IDs
        $ids = $this->unserializeSafe($gallery_value);

        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }

        if (!is_array($ids)) {
            return $gallery;
        }

        foreach ($ids as $attachment_id) {
            $attachment_id = (int) $attachment_id;
            if ($attachment_id > 0) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $gallery[] = [
                        'id' => $attachment_id,
                        'url' => $url,
                        'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                        'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                        'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
                    ];
                }
            }
        }

        return $gallery;
    }

    /**
     * Get post taxonomies
     *
     * @param int $postId
     * @return array
     */
    private function getTaxonomies(int $postId): array
    {
        $taxonomies = [];

        $tax_names = ['tours_cat', 'st_tour_type', 'tour_tag'];

        foreach ($tax_names as $tax) {
            $terms = get_the_terms($postId, $tax);
            if ($terms && !is_wp_error($terms)) {
                $taxonomies[$tax] = array_map(function ($term) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'link' => get_term_link($term),
                    ];
                }, $terms);
            } else {
                $taxonomies[$tax] = [];
            }
        }

        return $taxonomies;
    }

    /**
     * Get excerpt (auto-generate if empty)
     *
     * @param \WP_Post $post
     * @return string
     */
    private function getExcerpt(\WP_Post $post): string
    {
        if (!empty($post->post_excerpt)) {
            return $post->post_excerpt;
        }

        return wp_trim_words(strip_tags($post->post_content), 25, '...');
    }

    /**
     * Format price value
     *
     * @param mixed $value
     * @return float
     */
    private function formatPrice($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Handle potential serialized or formatted values
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value);
        return (float) $cleaned;
    }

    /**
     * Safely unserialize a value
     *
     * @param mixed $value
     * @return mixed
     */
    private function unserializeSafe($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check if serialized
        if ($this->isSerialized($value)) {
            $unserialized = @unserialize($value);
            return $unserialized !== false ? $unserialized : $value;
        }

        return $value;
    }

    /**
     * Check if value is serialized
     *
     * @param string $data
     * @return bool
     */
    private function isSerialized(string $data): bool
    {
        if ($data === 'N;') {
            return true;
        }

        if (!preg_match('/^([adObis]):/', $data, $matches)) {
            return false;
        }

        return true;
    }
}
