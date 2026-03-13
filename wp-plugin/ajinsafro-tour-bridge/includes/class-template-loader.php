<?php
/**
 * Template Loader
 *
 * Handles overriding the single st_tours template
 * Uses template_include filter to load plugin template
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Template_Loader
{

    /**
     * Flag to prevent infinite loops
     * @var bool
     */
    private static $loading = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Use template_include with high priority to override after theme
        add_filter('template_include', [$this, 'override_single_template'], 999);
    }

    /**
     * Override single st_tours template
     *
     * @param string $template Original template path
     * @return string Modified template path
     */
    public function override_single_template($template)
    {
        // Prevent infinite loops
        if (self::$loading) {
            return $template;
        }

        // Only override single st_tours
        if (!is_singular(AJTB_POST_TYPE)) {
            return $template;
        }

        // Our plugin template
        $plugin_template = AJTB_PLUGIN_DIR . 'templates/single-st_tours.php';

        // Check if our template exists
        if (!file_exists($plugin_template)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB: Plugin template not found at ' . $plugin_template);
            }
            return $template;
        }

        // Set loading flag
        self::$loading = true;

        // Log template override
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB: Overriding single template for st_tours');
        }

        return $plugin_template;
    }

    /**
     * Reset loading flag (called after template is loaded)
     */
    public static function reset_loading()
    {
        self::$loading = false;
    }

    /**
     * Get tour data for template
     *
     * @param int|null $post_id Post ID (defaults to current post)
     * @return array Assembled tour data
     */
    public static function get_tour_data($post_id = null)
    {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return [];
        }

        // Get WordPress data
        $wp_repo = new AJTB_Tour_Repository($post_id);
        $wp_data = $wp_repo->get_tour_data();

        if (!$wp_data) {
            return [];
        }

        // Get Laravel data (with client activity selections if session token present)
        $laravel_repo = new AJTB_Laravel_Repository($post_id);
        $session_token = (new AJTB_Activity_Selections())->get_session_token();
        $laravel_data = $laravel_repo->get_all_data($session_token);
        $laravel_data['all_flights'] = $laravel_repo->get_raw_flights();

        // Merge data with priority to Laravel if available
        $merged = self::merge_tour_data($wp_data, $laravel_data);
        $merged['_session_token'] = $session_token;
        // Diagnostic: always attach so template can show in HTML comment when WP_DEBUG or ?ajtb_flights_debug=1
        $merged['_flights_debug'] = $laravel_repo->get_flights_debug_info();
        return $merged;
    }

    /**
     * Merge WordPress and Laravel data
     *
     * @param array $wp_data WordPress tour data
     * @param array $laravel_data Laravel extra data
     * @return array Merged data
     */
    private static function merge_tour_data($wp_data, $laravel_data)
    {
        // Itinerary = only from Laravel days (aj_tour_days). No WP tours_program in a day.
        // Fallback to WP programme is handled in template when itinerary is empty.
        $itinerary = !empty($laravel_data['days']) ? $laravel_data['days'] : [];

        // Determine inclusions (Laravel sections take priority)
        $sections = $laravel_data['sections'] ?? [];
        $inclusions = !empty($sections['inclusions']['content'])
            ? ajtb_parse_list_content($sections['inclusions']['content'])
            : $wp_data['included'];

        $exclusions = !empty($sections['exclusions']['content'])
            ? ajtb_parse_list_content($sections['exclusions']['content'])
            : $wp_data['excluded'];

        // Get overview from Laravel if available
        $overview = !empty($sections['overview']['content'])
            ? $sections['overview']['content']
            : '';

        // Merge pricing with seasonal rules
        $pricing = $wp_data['pricing'];
        if (!empty($laravel_data['pricing_rules'])) {
            $pricing['seasonal_rules'] = $laravel_data['pricing_rules'];

            // Check for current active season
            $laravel_repo = new AJTB_Laravel_Repository($wp_data['id']);
            $current_pricing = $laravel_repo->get_current_pricing();

            if ($current_pricing) {
                $pricing['current_season'] = $current_pricing;
                $pricing['display_price'] = $current_pricing['adult_price'];
                $pricing['adult'] = $current_pricing['adult_price'];
                $pricing['child'] = $current_pricing['child_price'];
                $pricing['infant'] = $current_pricing['infant_price'];
            }
        }

        return [
            // WP data (base)
            'id' => $wp_data['id'],
            'title' => $wp_data['title'],
            'content' => $wp_data['content'],
            'excerpt' => $wp_data['excerpt'],
            'permalink' => $wp_data['permalink'],

            // Images (featured_image = image hero principale, hero_image = même chose pour le partial hero)
            'featured_image' => $wp_data['featured_image'],
            'hero_image' => $wp_data['hero_image'] ?? $wp_data['featured_image'],
            'gallery' => $wp_data['gallery'],
            'hero_gallery' => $wp_data['hero_gallery'] ?? [], // 5 images pour la galerie hero (CRUD)

            // Location
            'address' => $wp_data['address'],
            'location_id' => $wp_data['location_id'] ?? 0,
            'location_ids' => $wp_data['location_ids'] ?? [],
            'locations' => $wp_data['locations'] ?? [],
            'map' => $wp_data['map'],

            // Pricing (merged)
            'pricing' => $pricing,

            // Duration & Capacity
            'duration_day' => $wp_data['duration_day'],
            'max_people' => $wp_data['max_people'],
            'min_people' => $wp_data['min_people'],

            // Tour type
            'type_tour' => $wp_data['type_tour'],

            // Content (merged - Laravel priority)
            'overview' => $overview,
            'itinerary' => $itinerary,
            'inclusions' => $inclusions,
            'exclusions' => $exclusions,
            'highlights' => $wp_data['highlights'],
            'faqs' => $wp_data['faqs'],

            // Reviews
            'rating' => $wp_data['rating'],

            // Extras
            'external_booking_link' => $wp_data['external_booking_link'],
            'video' => $wp_data['video'],
            'cancellation_policy' => $wp_data['cancellation_policy'],

            // Taxonomies
            'categories' => $wp_data['categories'],
            'tour_types' => $wp_data['tour_types'],

            // Flags
            'is_featured' => $wp_data['is_featured'],

            // Laravel specific
            'has_laravel_data' => $laravel_data['has_data'] ?? false,
            'laravel_sections' => $sections,

            // Source tracking
            '_sources' => [
                'itinerary' => !empty($laravel_data['days']) ? 'laravel' : 'wordpress',
                'inclusions' => !empty($sections['inclusions']['content']) ? 'laravel' : 'wordpress',
                'exclusions' => !empty($sections['exclusions']['content']) ? 'laravel' : 'wordpress',
                'pricing' => !empty($laravel_data['pricing_rules']) ? 'laravel' : 'wordpress',
            ],
            // Client activity selections (front add/remove)
            'activities_catalog' => $laravel_data['activities_catalog'] ?? [],
            // WP Programme (tours_program_style + tours_program). Priority over Laravel when items non-empty.
            'wp_program' => $wp_data['wp_program'] ?? ['style' => 'style1', 'items' => []],
            // Flights (displayed after session selections) and all flights (for "Add this flight" links)
            'flights' => $laravel_data['flights'] ?? [],
            'all_flights' => $laravel_data['all_flights'] ?? [],
            // Laravel voyage_flights: Vol Aller (Jour 1) + Vol Retour (dernier jour)
            'outboundFlight' => $laravel_data['laravel_voyage_flights']['outbound'] ?? null,
            'inboundFlight' => $laravel_data['laravel_voyage_flights']['inbound'] ?? null,
        ];
    }
}
