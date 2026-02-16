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
        'location_id',
        'id_location',
        'multi_location',
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
        'tours_program_style',
        'included',
        'excluded',
        'tour_external_booking_link',
        
        // Gallery & Hero
        'gallery',
        'st_gallery',
        '_tour_hero_image_id',
        '_tour_hero_gallery_ids',
        
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
        $gallery = $this->get_gallery($meta);
        $hero_image = $this->get_hero_image($meta, $gallery);
        $hero_gallery = $this->get_hero_gallery($meta);
        $location_ids = $this->extract_location_ids($meta);
        $locations = $this->resolve_locations($location_ids);

        return [
            // Basic post data
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => $this->get_excerpt($post),
            'permalink' => get_permalink($post),
            'slug' => $post->post_name,

            // Images: hero = image principale (hero > featured > gallery[0]). featured_image = same for compatibility.
            'featured_image' => $hero_image,
            'hero_image' => $hero_image,
            'hero_image_url' => $hero_image['url'] ?? '',
            'gallery' => $gallery,
            'hero_gallery' => $hero_gallery, // 5 images spécifiques pour la galerie hero

            // Location
            'address' => $meta['address'] ?? '',
            'location_id' => !empty($location_ids) ? (int) $location_ids[0] : 0,
            'location_ids' => $location_ids,
            'locations' => $locations,
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

            // WP Programme (items list: 08:00... + description). Used when non-empty; else fallback to Laravel itinerary.
            'wp_program' => $this->get_wp_program(),

            // Raw meta for custom access
            '_meta' => $meta,
        ];
    }

    /**
     * Get WP Traveler programme (tours_program_style + tours_program).
     * Safe unserialize; normalizes to [{title, desc}]. Returns {style, items}.
     *
     * @return array{style: string, items: array<array{title: string, desc: string}>}
     */
    public function get_wp_program() {
        $meta = $this->get_all_meta();
        $style = isset($meta['tours_program_style']) ? sanitize_text_field($meta['tours_program_style']) : '';
        if ($style === '') {
            $style = 'style1';
        }
        $raw = isset($meta['tours_program']) ? $meta['tours_program'] : '';
        if ($raw === '' || $raw === null) {
            return ['style' => $style, 'items' => []];
        }
        $data = ajtb_maybe_unserialize($raw);
        if (!is_array($data)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB get_wp_program: tours_program is not array for post ' . $this->post_id);
            }
            return ['style' => $style, 'items' => []];
        }
        $items = [];
        foreach ($data as $index => $item) {
            if (is_array($item)) {
                $title = isset($item['title']) ? (string) $item['title'] : '';
                $desc = isset($item['desc']) ? (string) $item['desc'] : (isset($item['description']) ? (string) $item['description'] : (isset($item['content']) ? (string) $item['content'] : ''));
                $items[] = ['title' => $title, 'desc' => $desc];
            } elseif (is_string($item)) {
                $items[] = ['title' => '', 'desc' => $item];
            }
        }
        return ['style' => $style, 'items' => $items];
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
     * Get the main display image (hero): image principale > featured > first gallery > placeholder.
     * Used for Hero section, cards, og:image. Does NOT use gallery first as automatic fallback for hero.
     *
     * @param array $meta Post meta
     * @param array $gallery Parsed gallery (from get_gallery)
     * @return array { id, url, large, medium, alt }
     */
    private function get_hero_image($meta, $gallery) {
        $placeholder = [
            'id' => 0,
            'url' => '',
            'large' => '',
            'medium' => '',
            'alt' => '',
        ];

        // 1) Image à la une WordPress (_thumbnail_id) – priorité (upload Laravel ou WP définit ça)
        $thumbnail_id = get_post_thumbnail_id($this->post_id);
        if ($thumbnail_id) {
            $url = get_the_post_thumbnail_url($this->post_id, 'full');
            if (empty($url)) {
                $attachment_post = get_post($thumbnail_id);
                if ($attachment_post && $attachment_post->post_type === 'attachment' && !empty($attachment_post->guid)) {
                    $url = $attachment_post->guid;
                }
            }
            if (!empty($url)) {
                return [
                    'id' => $thumbnail_id,
                    'url' => $url,
                    'large' => wp_get_attachment_image_url($thumbnail_id, 'large') ?: $url,
                    'medium' => wp_get_attachment_image_url($thumbnail_id, 'medium') ?: $url,
                    'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true),
                ];
            }
        }

        // 2) Image principale custom (_tour_hero_image_id) – fallback
        $hero_id = isset($meta['_tour_hero_image_id']) ? (int) $meta['_tour_hero_image_id'] : 0;
        if ($hero_id > 0) {
            $url = wp_get_attachment_image_url($hero_id, 'full');
            if (empty($url)) {
                $attachment_post = get_post($hero_id);
                if ($attachment_post && $attachment_post->post_type === 'attachment' && !empty($attachment_post->guid)) {
                    $url = $attachment_post->guid;
                }
            }
            if (!empty($url)) {
                return [
                    'id' => $hero_id,
                    'url' => $url,
                    'large' => wp_get_attachment_image_url($hero_id, 'large') ?: $url,
                    'medium' => wp_get_attachment_image_url($hero_id, 'medium') ?: $url,
                    'alt' => get_post_meta($hero_id, '_wp_attachment_image_alt', true),
                ];
            }
        }

        // 3) First image of gallery
        if (!empty($gallery) && !empty($gallery[0]['url'])) {
            $first = $gallery[0];
            return [
                'id' => isset($first['id']) ? (int) $first['id'] : 0,
                'url' => $first['url'],
                'large' => $first['large'] ?? $first['url'],
                'medium' => $first['medium'] ?? $first['url'],
                'alt' => $first['alt'] ?? '',
            ];
        }

        return [
            'id' => 0,
            'url' => AJTB_PLUGIN_URL . 'assets/images/placeholder-tour.jpg',
            'large' => AJTB_PLUGIN_URL . 'assets/images/placeholder-tour.jpg',
            'medium' => AJTB_PLUGIN_URL . 'assets/images/placeholder-tour.jpg',
            'alt' => '',
        ];
    }

    /**
     * Get featured image data (legacy: now points to hero resolution).
     *
     * @return array
     */
    private function get_featured_image() {
        $meta = $this->get_all_meta();
        $gallery = $this->get_gallery($meta);
        return $this->get_hero_image($meta, $gallery);
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
     * Get hero gallery (5 images for hero gallery display)
     *
     * @param array $meta Meta data
     * @return array Array of image data (max 5)
     */
    private function get_hero_gallery($meta) {
        $hero_gallery_ids = $meta['_tour_hero_gallery_ids'] ?? '';
        if (empty($hero_gallery_ids)) {
            return [];
        }
        
        $ids = is_array($hero_gallery_ids) 
            ? $hero_gallery_ids 
            : array_filter(array_map('trim', explode(',', $hero_gallery_ids)));
        
        $hero_gallery = [];
        foreach ($ids as $id) {
            $id = (int) trim($id);
            if ($id <= 0) continue;
            
            $url = wp_get_attachment_image_url($id, 'full');
            if (empty($url)) {
                $attachment_post = get_post($id);
                if ($attachment_post && $attachment_post->post_type === 'attachment' && !empty($attachment_post->guid)) {
                    $url = $attachment_post->guid;
                }
            }
            
            if (!empty($url)) {
                $hero_gallery[] = [
                    'id' => $id,
                    'url' => $url,
                    'large' => wp_get_attachment_image_url($id, 'large') ?: $url,
                    'medium' => wp_get_attachment_image_url($id, 'medium') ?: $url,
                    'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail') ?: $url,
                    'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_the_title($this->post_id),
                ];
            }
            
            // Max 5 images
            if (count($hero_gallery) >= 5) {
                break;
            }
        }
        
        return $hero_gallery;
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

    /**
     * Extract location IDs from Traveler location metas.
     *
     * @param array $meta
     * @return array
     */
    private function extract_location_ids($meta) {
        $ids = [];

        foreach (['st_location_id', 'location_id', 'id_location'] as $key) {
            if (!isset($meta[$key])) {
                continue;
            }
            $id = (int) $meta[$key];
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if (!empty($meta['multi_location'])) {
            $multi_ids = $this->parse_multi_location_ids($meta['multi_location']);
            foreach ($multi_ids as $id) {
                $ids[] = $id;
            }
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * Parse multi_location value from Traveler format: "_12_,_15_" or CSV.
     *
     * @param string $value
     * @return array
     */
    private function parse_multi_location_ids($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $ids = [];
        if (preg_match_all('/_(\d+)_/', $value, $matches) && !empty($matches[1])) {
            foreach ($matches[1] as $id) {
                $ids[] = (int) $id;
            }
            return $ids;
        }

        foreach (explode(',', $value) as $raw) {
            $id = (int) trim($raw);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Resolve location IDs to displayable paths (Country > City).
     *
     * @param array $location_ids
     * @return array
     */
    private function resolve_locations($location_ids) {
        $location_ids = array_values(array_unique(array_map('intval', (array) $location_ids)));
        if (empty($location_ids)) {
            return [];
        }

        $posts = get_posts([
            'post_type' => 'location',
            'post__in' => $location_ids,
            'orderby' => 'post__in',
            'post_status' => ['publish', 'private', 'draft', 'pending', 'future'],
            'posts_per_page' => count($location_ids),
        ]);

        if (empty($posts)) {
            return [];
        }

        $by_id = [];
        foreach ($posts as $post) {
            $by_id[(int) $post->ID] = $post;
        }

        $resolved = [];
        $path_cache = [];
        foreach ($location_ids as $location_id) {
            if (empty($by_id[$location_id])) {
                continue;
            }

            $post = $by_id[$location_id];
            $parts = $this->get_location_path_parts((int) $post->ID, $path_cache);
            if (empty($parts)) {
                $parts = [(string) $post->post_title];
            }

            $country = $parts[0];
            $city = count($parts) > 1 ? $parts[count($parts) - 1] : '';
            $resolved[] = [
                'id' => (int) $post->ID,
                'name' => (string) $post->post_title,
                'parent_id' => (int) $post->post_parent,
                'country' => $country,
                'city' => $city,
                'path_parts' => $parts,
                'path' => implode(' > ', $parts),
            ];
        }

        return $resolved;
    }

    /**
     * Build full location hierarchy path for one location.
     *
     * @param int $location_id
     * @param array $cache
     * @param int $depth
     * @return array
     */
    private function get_location_path_parts($location_id, &$cache, $depth = 0) {
        $location_id = (int) $location_id;
        if ($location_id <= 0 || $depth > 10) {
            return [];
        }

        if (isset($cache[$location_id])) {
            return $cache[$location_id];
        }

        $post = get_post($location_id);
        if (!$post || $post->post_type !== 'location') {
            $cache[$location_id] = [];
            return [];
        }

        $parts = [(string) $post->post_title];
        $parent_id = (int) $post->post_parent;
        if ($parent_id > 0) {
            $parent_parts = $this->get_location_path_parts($parent_id, $cache, $depth + 1);
            if (!empty($parent_parts)) {
                $parts = array_merge($parent_parts, $parts);
            }
        }

        $cache[$location_id] = $parts;
        return $parts;
    }
}
