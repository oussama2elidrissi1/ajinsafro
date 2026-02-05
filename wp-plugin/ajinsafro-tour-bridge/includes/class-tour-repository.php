<?php
/**
 * Tour Repository - WordPress Data
 *
 * Handles fetching tour data from WordPress (posts + metas)
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Tour_Repository {

    /**
     * Tour post ID
     * @var int
     */
    private $post_id;

    /**
     * Cached meta data
     * @var array|null
     */
    private $meta_cache = null;

    /**
     * Essential Traveler meta keys
     * @var array
     */
    private $meta_keys = [
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
        
        // Tour details
        'type_tour',
        'tours_program',
        'included',
        'excluded',
        'tour_external_booking_link',
        
        // Gallery
        'gallery',
        'st_gallery',
        
        // Reviews
        'rate_review',
        'review_score',
        
        // Flags
        'is_featured',
        
        // Extras
        'faqs',
        'video',
        'cancellation_policy',
        'highlight',
    ];

    /**
     * Constructor
     *
     * @param int $post_id Tour post ID
     */
    public function __construct($post_id) {
        $this->post_id = (int) $post_id;
    }

    /**
     * Get complete tour data
     *
     * @return array|null Tour data or null if not found
     */
    public function get_tour_data() {
        $post = get_post($this->post_id);

        if (!$post || $post->post_type !== AJTB_POST_TYPE) {
            return null;
        }

        // Load all meta
        $meta = $this->get_all_meta();

        return [
            // Basic post data
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => $this->get_excerpt($post),
            'permalink' => get_permalink($post),
            'slug' => $post->post_name,

            // Images
            'featured_image' => $this->get_featured_image(),
            'gallery' => $this->get_gallery($meta),

            // Location
            'address' => $meta['address'] ?? '',
            'location_id' => (int) ($meta['st_location_id'] ?? 0),
            'map' => [
                'lat' => $meta['map_lat'] ?? '',
                'lng' => $meta['map_lng'] ?? '',
                'zoom' => (int) ($meta['map_zoom'] ?? 14),
            ],

            // Pricing
            'pricing' => $this->get_pricing($meta),

            // Duration
            'duration_day' => (int) ($meta['duration_day'] ?? 0),
            'duration' => $meta['duration'] ?? '',

            // Capacity
            'max_people' => (int) ($meta['max_people'] ?? 0),
            'min_people' => (int) ($meta['min_people'] ?? 0),

            // Tour type
            'type_tour' => $meta['type_tour'] ?? 'daily_tour',

            // Content sections
            'tours_program' => $this->parse_program($meta['tours_program'] ?? ''),
            'included' => ajtb_parse_list_content($meta['included'] ?? ''),
            'excluded' => ajtb_parse_list_content($meta['excluded'] ?? ''),
            'highlights' => ajtb_parse_list_content($meta['highlight'] ?? ''),
            'faqs' => $this->parse_faqs($meta['faqs'] ?? ''),

            // Reviews
            'rating' => (float) ($meta['rate_review'] ?? 0),
            'review_score' => (float) ($meta['review_score'] ?? 0),

            // Flags
            'is_featured' => ($meta['is_featured'] ?? 'off') === 'on',

            // Extras
            'external_booking_link' => $meta['tour_external_booking_link'] ?? '',
            'video' => $meta['video'] ?? '',
            'cancellation_policy' => $meta['cancellation_policy'] ?? '',

            // Taxonomies
            'categories' => $this->get_taxonomies('tours_cat'),
            'tour_types' => $this->get_taxonomies('st_tour_type'),
            'tags' => $this->get_taxonomies('tour_tag'),

            // Raw meta for custom access
            '_meta' => $meta,
        ];
    }

    /**
     * Get all meta values
     *
     * @return array
     */
    private function get_all_meta() {
        if ($this->meta_cache !== null) {
            return $this->meta_cache;
        }

        global $wpdb;

        $this->meta_cache = [];

        // Get all meta in one query
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
                $this->post_id
            ),
            ARRAY_A
        );

        if ($results) {
            foreach ($results as $row) {
                $key = $row['meta_key'];
                // Only include relevant keys
                if (in_array($key, $this->meta_keys) || strpos($key, 'st_') === 0) {
                    $this->meta_cache[$key] = $row['meta_value'];
                }
            }
        }

        return $this->meta_cache;
    }

    /**
     * Get featured image data
     *
     * @return array
     */
    private function get_featured_image() {
        $thumbnail_id = get_post_thumbnail_id($this->post_id);

        if (!$thumbnail_id) {
            return [
                'id' => 0,
                'url' => '',
                'alt' => '',
            ];
        }

        return [
            'id' => $thumbnail_id,
            'url' => get_the_post_thumbnail_url($this->post_id, 'full'),
            'large' => get_the_post_thumbnail_url($this->post_id, 'large'),
            'medium' => get_the_post_thumbnail_url($this->post_id, 'medium'),
            'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
        ];
    }

    /**
     * Get gallery images
     *
     * @param array $meta Meta data
     * @return array
     */
    private function get_gallery($meta) {
        $gallery_value = $meta['gallery'] ?? $meta['st_gallery'] ?? '';
        return ajtb_parse_gallery($gallery_value);
    }

    /**
     * Get pricing information
     *
     * @param array $meta Meta data
     * @return array
     */
    private function get_pricing($meta) {
        $adult_price = (float) ($meta['adult_price'] ?? $meta['base_price'] ?? 0);
        $child_price = (float) ($meta['child_price'] ?? 0);
        $infant_price = (float) ($meta['infant_price'] ?? 0);
        $discount = (float) ($meta['discount'] ?? 0);
        $sale_price = (float) ($meta['sale_price'] ?? 0);

        // Calculate discounted price
        $has_discount = $discount > 0;
        $display_price = $adult_price;
        
        if ($has_discount && $sale_price > 0) {
            $display_price = $sale_price;
        } elseif ($has_discount && $discount > 0) {
            $display_price = $adult_price * (1 - $discount / 100);
        }

        return [
            'adult' => $adult_price,
            'child' => $child_price,
            'infant' => $infant_price,
            'discount' => $discount,
            'sale_price' => $sale_price,
            'display_price' => $display_price,
            'has_discount' => $has_discount,
            'original_price' => $adult_price,
            'currency' => get_option('st_currency', 'MAD'),
            'currency_symbol' => ajtb_get_currency_symbol(),
        ];
    }

    /**
     * Parse tours_program meta
     *
     * @param mixed $program Raw program data
     * @return array
     */
    private function parse_program($program) {
        if (empty($program)) {
            return [];
        }

        $data = ajtb_maybe_unserialize($program);

        if (!is_array($data)) {
            return [];
        }

        $parsed = [];
        foreach ($data as $index => $item) {
            if (is_array($item)) {
                $parsed[] = [
                    'day' => $index + 1,
                    'title' => $item['title'] ?? '',
                    'content' => $item['content'] ?? $item['desc'] ?? '',
                    'image' => $item['image'] ?? '',
                ];
            } elseif (is_string($item)) {
                $parsed[] = [
                    'day' => $index + 1,
                    'title' => 'Jour ' . ($index + 1),
                    'content' => $item,
                    'image' => '',
                ];
            }
        }

        return $parsed;
    }

    /**
     * Parse FAQs meta
     *
     * @param mixed $faqs Raw FAQs data
     * @return array
     */
    private function parse_faqs($faqs) {
        if (empty($faqs)) {
            return [];
        }

        $data = ajtb_maybe_unserialize($faqs);

        if (!is_array($data)) {
            return [];
        }

        $parsed = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $parsed[] = [
                    'question' => $item['question'] ?? $item['title'] ?? '',
                    'answer' => $item['answer'] ?? $item['content'] ?? '',
                ];
            }
        }

        return $parsed;
    }

    /**
     * Get taxonomy terms
     *
     * @param string $taxonomy Taxonomy name
     * @return array
     */
    private function get_taxonomies($taxonomy) {
        $terms = get_the_terms($this->post_id, $taxonomy);

        if (!$terms || is_wp_error($terms)) {
            return [];
        }

        return array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'link' => get_term_link($term),
            ];
        }, $terms);
    }

    /**
     * Get excerpt
     *
     * @param WP_Post $post Post object
     * @return string
     */
    private function get_excerpt($post) {
        if (!empty($post->post_excerpt)) {
            return $post->post_excerpt;
        }

        return wp_trim_words(strip_tags($post->post_content), 30, '...');
    }

    /**
     * Get specific meta value
     *
     * @param string $key Meta key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get_meta($key, $default = '') {
        $meta = $this->get_all_meta();
        return isset($meta[$key]) ? $meta[$key] : $default;
    }
}
