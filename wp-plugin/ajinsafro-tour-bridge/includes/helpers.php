<?php
/**
 * Helper Functions
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Laravel API base URL for voyage flights (Vol Aller / Vol Retour).
 * Without this, Vol Aller (Jour 1) and Vol Retour (dernier jour) will not load on the front.
 *
 * In wp-config.php add: define('AJTB_LARAVEL_API_URL', 'https://your-laravel-domain.com');
 * Or use filter 'ajtb_laravel_api_url'. URL must not end with a trailing slash.
 *
 * @return string Empty if not configured (e.g. same-server DB only).
 */
function ajtb_laravel_api_url() {
    $url = defined('AJTB_LARAVEL_API_URL') ? AJTB_LARAVEL_API_URL : '';
    return (string) apply_filters('ajtb_laravel_api_url', $url);
}

/**
 * Get full table name for aj_* tables. Prevents double prefix.
 *
 * @param string $suffix Table name suffix, e.g. 'aj_tour_activity_selections', 'aj_tour_days', 'aj_activities'
 * @return string Full table name (e.g. {$wpdb->prefix}aj_tour_activity_selections)
 */
function ajtb_table($suffix) {
    global $wpdb;
    $p = $wpdb->prefix;
    if ($p !== '' && substr((string) $suffix, 0, strlen($p)) === $p) {
        return $suffix;
    }
    return $wpdb->prefix . ltrim($suffix, '_');
}

/**
 * Table name for flights (aj_tour_flights). Laravel may use a different prefix (e.g. cFdgeZ_).
 * In wp-config.php use one of:
 *   define('AJTB_FLIGHTS_TABLE', 'cFdgeZ_aj_tour_flights');
 *   define('AJTB_FLIGHTS_TABLE_PREFIX', 'cFdgeZ_');  // then table = cFdgeZ_aj_tour_flights
 *
 * @param int $tour_id Optional; passed to filter.
 * @return string Full table name.
 */
function ajtb_flights_table($tour_id = 0) {
    $default = ajtb_table('aj_tour_flights');
    if (defined('AJTB_FLIGHTS_TABLE') && AJTB_FLIGHTS_TABLE !== '') {
        return AJTB_FLIGHTS_TABLE;
    }
    if (defined('AJTB_FLIGHTS_TABLE_PREFIX') && AJTB_FLIGHTS_TABLE_PREFIX !== '') {
        $prefix = rtrim((string) AJTB_FLIGHTS_TABLE_PREFIX, '_');
        return $prefix . '_aj_tour_flights';
    }
    $from_option = get_option('ajtb_flights_table', '');
    if (is_string($from_option) && $from_option !== '') {
        return $from_option;
    }
    return (string) apply_filters('ajtb_flights_table', $default, $tour_id);
}

/**
 * Get currency symbol based on currency code
 *
 * @param string|null $currency Currency code (MAD, EUR, USD, etc.)
 * @return string Currency symbol
 */
function ajtb_get_currency_symbol($currency = null) {
    if ($currency === null) {
        $currency = get_option('st_currency', 'MAD');
    }

    $symbols = [
        'MAD' => 'DH',
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        'CHF' => 'CHF',
        'CAD' => 'CA$',
        'AED' => 'AED',
    ];

    return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
}

/**
 * Format price with currency
 *
 * @param float $price Price value
 * @param bool $with_symbol Include currency symbol
 * @return string Formatted price
 */
function ajtb_format_price($price, $with_symbol = true) {
    $formatted = number_format((float)$price, 0, ',', ' ');
    
    if ($with_symbol) {
        return $formatted . ' ' . ajtb_get_currency_symbol();
    }
    
    return $formatted;
}

/**
 * Get WordPress attachment image URL reliably (using _wp_attached_file meta, not guid).
 * Works even if attachment was created by Laravel with inconsistent guid.
 *
 * @param int $attachment_id WordPress attachment post ID
 * @param string $size Image size (thumbnail, medium, large, full)
 * @return string|null Image URL or null if not found
 */
function ajtb_get_attachment_image_url($attachment_id, $size = 'medium') {
    if (empty($attachment_id)) {
        return null;
    }
    
    $attachment_id = (int) $attachment_id;
    
    // Try WordPress function first (works for standard WP uploads)
    if (function_exists('wp_get_attachment_image_url')) {
        $url = wp_get_attachment_image_url($attachment_id, $size);
        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
    }
    
    // Fallback: build URL from _wp_attached_file meta (reliable for Laravel uploads)
    global $wpdb;
    $attached_file = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE post_id = %d AND meta_key = '_wp_attached_file' 
         LIMIT 1",
        $attachment_id
    ));
    
    if (!$attached_file) {
        return null;
    }
    
    // Get uploads base URL
    $uploads_data = function_exists('wp_upload_dir') ? wp_upload_dir() : null;
    $uploads_url = $uploads_data && isset($uploads_data['baseurl']) ? $uploads_data['baseurl'] : '';
    
    if (!$uploads_url) {
        // Fallback to config if wp_upload_dir fails
        $uploads_url = defined('WP_UPLOADS_URL') ? WP_UPLOADS_URL : '';
    }
    
    if (!$uploads_url) {
        return null;
    }
    
    // Try to get size-specific file if not 'full'
    if ($size !== 'full' && function_exists('image_get_intermediate_size')) {
        $size_info = image_get_intermediate_size($attachment_id, $size);
        if ($size_info && isset($size_info['file']) && !empty($size_info['file'])) {
            $file_path = dirname($attached_file) . '/' . $size_info['file'];
            $full_url = rtrim($uploads_url, '/') . '/' . ltrim($file_path, '/');
            // Verify URL is valid
            if (filter_var($full_url, FILTER_VALIDATE_URL)) {
                return $full_url;
            }
        }
    }
    
    // Return full size image
    $full_url = rtrim($uploads_url, '/') . '/' . ltrim($attached_file, '/');
    return filter_var($full_url, FILTER_VALIDATE_URL) ? $full_url : null;
}

/**
 * Get tour thumbnail URL
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @return string Image URL or placeholder
 */
function ajtb_get_tour_thumbnail($post_id, $size = 'large') {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    
    if ($thumbnail_id) {
        $image = ajtb_get_attachment_image_url($thumbnail_id, $size);
        if ($image) {
            return $image;
        }
    }

    // Fallback placeholder
    return AJTB_PLUGIN_URL . 'assets/images/placeholder-tour.jpg';
}

/**
 * Safely unserialize data
 *
 * @param mixed $data Data to unserialize
 * @return mixed Unserialized data or original
 */
function ajtb_maybe_unserialize($data) {
    if (!is_string($data)) {
        return $data;
    }

    // Check if serialized
    if (preg_match('/^([adObis]):/', $data)) {
        $unserialized = @unserialize($data);
        if ($unserialized !== false) {
            return $unserialized;
        }
    }

    return $data;
}

/**
 * Parse gallery meta to array of image data
 *
 * @param mixed $gallery_meta Gallery meta value
 * @return array Array of image data
 */
function ajtb_parse_gallery($gallery_meta) {
    $images = [];
    
    if (empty($gallery_meta)) {
        return $images;
    }

    // Unserialize if needed
    $gallery = ajtb_maybe_unserialize($gallery_meta);

    // Handle comma-separated IDs
    if (is_string($gallery)) {
        $ids = array_filter(array_map('trim', explode(',', $gallery)));
    } elseif (is_array($gallery)) {
        $ids = $gallery;
    } else {
        return $images;
    }

    foreach ($ids as $attachment_id) {
        $attachment_id = (int) $attachment_id;
        if ($attachment_id <= 0) {
            continue;
        }

        $url = wp_get_attachment_url($attachment_id);
        if (!$url) {
            continue;
        }

        $images[] = [
            'id' => $attachment_id,
            'url' => $url,
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'large' => wp_get_attachment_image_url($attachment_id, 'large'),
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
        ];
    }

    return $images;
}

/**
 * Parse list content (HTML or newlines) to array
 *
 * @param string $content Content to parse
 * @return array Array of items
 */
function ajtb_parse_list_content($content) {
    if (empty($content)) {
        return [];
    }

    $items = [];

    // Check for <li> items
    if (strpos($content, '<li>') !== false) {
        preg_match_all('/<li>(.*?)<\/li>/si', $content, $matches);
        if (!empty($matches[1])) {
            $items = array_map('trim', array_map('strip_tags', $matches[1]));
        }
    } else {
        // Split by newlines or <br>
        $content = str_replace(['<br>', '<br/>', '<br />'], "\n", $content);
        $content = strip_tags($content);
        $items = array_filter(array_map('trim', explode("\n", $content)));
    }

    return array_values($items);
}

/**
 * Get star rating HTML
 *
 * @param float $rating Rating value (0-5)
 * @param int $max Maximum stars
 * @return string HTML output
 */
function ajtb_get_star_rating($rating, $max = 5) {
    $rating = max(0, min($max, (float)$rating));
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = $max - $full_stars - ($half_star ? 1 : 0);

    $output = '<div class="ajtb-rating">';
    
    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $output .= '<svg class="star full" viewBox="0 0 24 24"><polygon fill="currentColor" points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>';
    }

    // Half star
    if ($half_star) {
        $output .= '<svg class="star half" viewBox="0 0 24 24"><defs><linearGradient id="half"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#e0e0e0"/></linearGradient></defs><polygon fill="url(#half)" points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>';
    }

    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $output .= '<svg class="star empty" viewBox="0 0 24 24"><polygon fill="#e0e0e0" points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>';
    }

    $output .= '<span class="rating-value">' . number_format($rating, 1) . '</span>';
    $output .= '</div>';

    return $output;
}

/**
 * Load a partial template
 *
 * @param string $partial Partial name (without .php)
 * @param array $args Variables to pass to template
 * @return void
 */
function ajtb_get_partial($partial, $args = []) {
    $file = AJTB_PLUGIN_DIR . 'templates/tour/partials/' . $partial . '.php';

    if (!file_exists($file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- Partial not found: ' . esc_html($partial) . ' -->';
        }
        return;
    }

    // Extract args to make them available in template
    if (!empty($args) && is_array($args)) {
        extract($args, EXTR_SKIP);
    }

    include $file;
}

/**
 * Sanitize and truncate text
 *
 * @param string $text Text to truncate
 * @param int $length Max length
 * @param string $suffix Suffix to add
 * @return string Truncated text
 */
function ajtb_truncate($text, $length = 150, $suffix = '...') {
    $text = wp_strip_all_tags($text);
    
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Check if current page is our custom template
 *
 * @return bool
 */
function ajtb_is_tour_single() {
    return is_singular(AJTB_POST_TYPE);
}

/**
 * Get tour meta value with default
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 * @return mixed Meta value or default
 */
function ajtb_get_meta($post_id, $key, $default = '') {
    $value = get_post_meta($post_id, $key, true);
    return ($value !== '' && $value !== false) ? $value : $default;
}

/**
 * Render HTML for one day's activities list + add block (for AJAX response).
 * Uses $wpdb->prefix via repository; no hardcoded table prefix.
 *
 * @param int $tour_id
 * @param int $day_id
 * @param array $day_activities List of activity items (title, description, activity_id, is_mandatory, is_included)
 * @param string $session_token
 * @param array $activities_catalog [['id'=>, 'title'=>], ...]
 * @return string HTML fragment (inner content for #aj-day-activities-{day_id})
 */
function ajtb_render_day_activities_html($tour_id, $day_id, $day_activities, $session_token, $activities_catalog = []) {
    $tour_id = (int) $tour_id;
    $day_id = (int) $day_id;
    $can_toggle = $tour_id > 0 && $day_id > 0 && $session_token !== '';
    $day_activity_ids = array_map(function ($a) {
        return (int) isset($a['activity_id']) ? $a['activity_id'] : 0;
    }, $day_activities);
    $included = array_filter($day_activities, function ($a) {
        return !empty($a['is_included']);
    });
    $included = array_values($included);

    $html = '<ul class="day-activities-list" data-day-id="' . esc_attr($day_id) . '">';
    if (!empty($included)) {
        foreach ($included as $act) {
            $act_id = (int) isset($act['activity_id']) ? $act['activity_id'] : 0;
            $day_activity_id = (int) isset($act['id']) ? $act['id'] : 0;
            $title = isset($act['title']) && (string) $act['title'] !== '' ? $act['title'] : __('Activité', 'ajinsafro-tour-bridge');
            $desc = isset($act['description']) && (string) $act['description'] !== '' ? $act['description'] : '';
            $is_mandatory = !empty($act['is_mandatory']);
            $show_remove = $can_toggle && !$is_mandatory;
            $act_price = null;
            if (isset($act['custom_price']) && $act['custom_price'] !== null) {
                $act_price = (float) $act['custom_price'];
            } elseif (isset($act['base_price']) && $act['base_price'] !== null) {
                $act_price = (float) $act['base_price'];
            }
            $act_image_url = isset($act['image_url']) ? $act['image_url'] : null;
            $act_start_time = isset($act['start_time']) ? $act['start_time'] : null;
            $act_end_time = isset($act['end_time']) ? $act['end_time'] : null;
            
            $client_added = !empty($act['client_added']) ? 'true' : 'false';
            $is_included_val = !empty($act['is_included']) ? '1' : '0';
            $html .= '<li class="day-activity-item day-activity-card-pro" data-activity-id="' . esc_attr($act_id) . '" data-day-activity-id="' . esc_attr($day_activity_id) . '" data-day-id="' . esc_attr($day_id) . '" data-is-mandatory="' . ($is_mandatory ? '1' : '0') . '" data-client-added="' . esc_attr($client_added) . '" data-is-included="' . esc_attr($is_included_val) . '">';
            $html .= '<div class="day-activity-item-content">';
            $html .= '<div class="day-activity-image-wrap">';
            if ($act_image_url) {
                $html .= '<div class="day-activity-image"><img src="' . esc_url($act_image_url) . '" alt="' . esc_attr($title) . '" loading="lazy"></div>';
            } else {
                $html .= '<div class="day-activity-image day-activity-image--placeholder"><span class="day-activity-image-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></span></div>';
            }
            $html .= '</div>';
            $html .= '<div class="day-activity-details">';
            $html .= '<div class="day-activity-header">';
            $html .= '<span class="activity-title">' . esc_html($title) . '</span>';
            if ($is_mandatory) {
                $html .= ' <span class="badge badge-mandatory">' . esc_html__('Obligatoire', 'ajinsafro-tour-bridge') . '</span>';
            }
            if ($act_price !== null) {
                $html .= ' <span class="activity-price">' . number_format($act_price, 0, ',', ' ') . ' DH</span>';
            }
            $html .= '</div>';
            if ($act_start_time || $act_end_time) {
                $html .= '<div class="activity-time">';
                if ($act_start_time) $html .= '<span>' . esc_html($act_start_time) . '</span>';
                if ($act_start_time && $act_end_time) $html .= '<span> – </span>';
                if ($act_end_time) $html .= '<span>' . esc_html($act_end_time) . '</span>';
                $html .= '</div>';
            }
            if ($desc !== '') {
                $html .= '<div class="activity-description">' . wp_kses_post($desc) . '</div>';
            }
            $html .= '</div>';
            $html .= '<div class="day-activity-actions">';
            if ($show_remove) {
                $html .= '<button type="button" class="ajtb-btn-edit-activity" data-day-activity-id="' . esc_attr($day_activity_id) . '" data-tour-id="' . esc_attr($tour_id) . '" data-day-id="' . esc_attr($day_id) . '" data-activity-id="' . esc_attr($act_id) . '" aria-label="' . esc_attr__('Modifier cette activité', 'ajinsafro-tour-bridge') . '">' . esc_html__('Modifier', 'ajinsafro-tour-bridge') . '</button>';
                $html .= '<button type="button" class="ajtb-btn-remove-activity" data-aj-action="remove" data-tour-id="' . esc_attr($tour_id) . '" data-day-id="' . esc_attr($day_id) . '" data-activity-id="' . esc_attr($act_id) . '" aria-label="' . esc_attr__('Retirer cette activité', 'ajinsafro-tour-bridge') . '">' . esc_html__('Retirer', 'ajinsafro-tour-bridge') . '</button>';
            }
            $html .= '</div></div></li>';
        }
    } else {
        if (!$can_toggle) {
            $html .= '<li class="day-activity-item day-no-activities">' . esc_html__('Aucune activité', 'ajinsafro-tour-bridge') . '</li>';
        }
    }
    /* CTA \"Add activity\" : toujours affichée quand les activités sont modifiables (même si des activités sont déjà ajoutées) */
    if ($can_toggle) {
        $html .= '<li class="day-activity-item day-activity-empty-card">';
        $html .= '<button type="button" class="ajtb-btn-open-activity-modal ajtb-btn-add-to-day" data-tour-id="' . esc_attr($tour_id) . '" data-day-id="' . esc_attr($day_id) . '">';
        $html .= '<span class="ajtb-btn-add-to-day-icon" aria-hidden="true">+</span>';
        $html .= esc_html__('Add activity', 'ajinsafro-tour-bridge');
        $html .= '</button>';
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

/**
 * Whether a flight array has any displayable content (from, to, or dates).
 * Any row from aj_tour_flights (has id or flight_type) is considered "has content" so we never hide saved flights.
 *
 * @param array|null $flight Flight row (from_city, to_city, depart_date_formatted, id, flight_type, etc.)
 * @return bool
 */
function ajtb_flight_has_content($flight) {
    if (empty($flight) || !is_array($flight)) {
        return false;
    }
    if (!empty($flight['id']) || (isset($flight['flight_type']) && (string) $flight['flight_type'] !== '')) {
        return true;
    }
    $from = isset($flight['from_city']) ? trim((string) $flight['from_city']) : trim((string) ($flight['depart_label'] ?? ''));
    $to = isset($flight['to_city']) ? trim((string) $flight['to_city']) : trim((string) ($flight['arrive_label'] ?? ''));
    $dep = isset($flight['depart_date_formatted']) ? trim((string) $flight['depart_date_formatted']) : '';
    $arr = isset($flight['arrive_date_formatted']) ? trim((string) $flight['arrive_date_formatted']) : '';
    return $from !== '' || $to !== '' || $dep !== '' || $arr !== '';
}

/**
 * Whether a list of flights (single row or array of rows) has at least one with content.
 * Use for multi-vol display (outbound/inbound/segments).
 *
 * @param array|null $flights Single flight row or array of flight rows
 * @return bool
 */
function ajtb_flights_have_content($flights) {
    if (empty($flights)) {
        return false;
    }
    $first = reset($flights);
    $is_list = is_array($first) && (isset($first['from_city']) || isset($first['flight_type']) || isset($first['depart_label']));
    $list = $is_list ? $flights : [$flights];
    foreach ($list as $f) {
        if (ajtb_flight_has_content($f)) {
            return true;
        }
    }
    return false;
}

/**
 * Compute global totals (days, flights, transfers, hotels, activities, meals) from tour itinerary.
 * Used for the sticky header bar (12 DAY PLAN / FLIGHTS / HOTEL / ACTIVITIES).
 *
 * @param array $tour Tour data with 'itinerary' key (array of days).
 * @return array ['days' => int, 'flights' => int, 'transfers' => int, 'hotels' => int, 'activities' => int, 'meals' => int]
 */
function ajtb_get_global_totals($tour) {
    $itinerary = isset($tour['itinerary']) && is_array($tour['itinerary']) ? $tour['itinerary'] : [];
    $total_days = count($itinerary);
    $out = [
        'days' => $total_days,
        'flights' => 0,
        'transfers' => 0,
        'hotels' => 0,
        'activities' => 0,
        'meals' => 0,
    ];
    foreach ($itinerary as $index => $day) {
        $flight_or_list = $day['flight'] ?? [];
        $first = is_array($flight_or_list) ? reset($flight_or_list) : null;
        $is_list = $first && is_array($first) && (isset($first['from_city']) || isset($first['flight_type']) || isset($first['depart_label']));
        $flight_list = $is_list ? $flight_or_list : [$flight_or_list];
        $return_or_list = $day['flight_return'] ?? [];
        $first_ret = is_array($return_or_list) ? reset($return_or_list) : null;
        $is_list_ret = $first_ret && is_array($first_ret) && (isset($first_ret['from_city']) || isset($first_ret['flight_type']) || isset($first_ret['depart_label']));
        $return_list = $is_list_ret ? $return_or_list : [$return_or_list];
        foreach ($flight_list as $f) {
            if (ajtb_flight_has_content($f)) {
                $out['flights']++;
            }
        }
        foreach ($return_list as $f) {
            if (ajtb_flight_has_content($f)) {
                $out['flights']++;
            }
        }
        $tr_arr = isset($day['transfer']) && is_array($day['transfer']) ? $day['transfer'] : [];
        $tr_dep = isset($day['transfer_return']) && is_array($day['transfer_return']) ? $day['transfer_return'] : [];
        $out['transfers'] += count($tr_arr) + count($tr_dep);
        $hotels_list = isset($day['hotels']) && is_array($day['hotels']) ? $day['hotels'] : (!empty($day['hotel']) ? [$day['hotel']] : []);
        $out['hotels'] += count($hotels_list);
        if (!empty($day['activities']) && is_array($day['activities'])) {
            foreach ($day['activities'] as $a) {
                if (!empty($a['is_included'])) {
                    $out['activities']++;
                }
            }
        }
        if (!empty(trim((string) ($day['meals'] ?? '')))) {
            $out['meals']++;
        }
    }
    return $out;
}

/**
 * Get flights grouped by day for Programme du Circuit. Reads from aj_tour_flights only.
 * Use this to ensure the front displays exactly what is in the Laravel CRUD (synced to WP).
 *
 * @param int $tour_id   WP post ID (tour).
 * @param int $last_day_number Last day of the tour (inbound shown on this day).
 * @return array ['dayFlights' => [1=>[rows], 2=>[rows], ...], 'outbound'=>[], 'inbound'=>[], 'segments_by_day'=>[], '_debug'=>[]]
 */
function ajinsafro_get_flights_for_program($tour_id, $last_day_number = 0) {
    $tour_id = (int) $tour_id;
    $last_day_number = (int) $last_day_number;
    if ($tour_id <= 0) {
        return ['dayFlights' => [], 'outbound' => [], 'inbound' => [], 'segments_by_day' => [], '_debug' => ['tour_id' => $tour_id]];
    }
    if (!class_exists('AJTB_Laravel_Repository')) {
        return ['dayFlights' => [], 'outbound' => [], 'inbound' => [], 'segments_by_day' => [], '_debug' => ['error' => 'AJTB_Laravel_Repository not loaded']];
    }
    $repo = new AJTB_Laravel_Repository($tour_id);
    return $repo->get_flights_for_program($last_day_number);
}

/**
 * Get hotels grouped by day_number for Programme du Circuit.
 *
 * @param int $tour_id WP post ID (tour).
 * @return array ['by_day' => [1=>[row, row], 2=>[...], ...], 'all' => [...]]
 */
function ajinsafro_get_hotels_grouped($tour_id) {
    $tour_id = (int) $tour_id;
    if ($tour_id <= 0 || !class_exists('AJTB_Laravel_Repository')) {
        return ['by_day' => [], 'all' => []];
    }
    $repo = new AJTB_Laravel_Repository($tour_id);
    return $repo->get_tour_hotels_grouped();
}

/**
 * Get transfers grouped by day_number and direction for Programme du Circuit.
 *
 * @param int $tour_id WP post ID (tour).
 * @return array ['by_day_direction' => [1=>['arrival'=>[], 'departure'=>[]], ...]]
 */
function ajinsafro_get_transfers_grouped($tour_id) {
    $tour_id = (int) $tour_id;
    if ($tour_id <= 0 || !class_exists('AJTB_Laravel_Repository')) {
        return ['by_day_direction' => []];
    }
    $repo = new AJTB_Laravel_Repository($tour_id);
    return $repo->get_tour_transfers_grouped();
}

/**
 * Render HTML for flights block (Flight Cards + Add/Remove). Used on initial load and AJAX response.
 *
 * @param int $tour_id
 * @param array $flights Displayed flights (after session selections)
 * @param array $all_flights All flights for tour (for "Add this flight" links)
 * @param string $session_token
 * @return string HTML fragment for #ajtb-flights-container
 */
function ajtb_render_flights_html($tour_id, $flights, $all_flights = [], $session_token = '') {
    $tour_id = (int) $tour_id;
    $can_toggle = $tour_id > 0 && $session_token !== '';
    $displayed_ids = array_column($flights, 'id');

    $html = '<div class="ajtb-flights-list" data-tour-id="' . esc_attr($tour_id) . '">';
    foreach ($flights as $f) {
        $dep_label = isset($f['depart_label']) ? $f['depart_label'] : '—';
        $arr_label = isset($f['arrive_label']) ? $f['arrive_label'] : '—';
        $dep_date = isset($f['depart_date_formatted']) ? $f['depart_date_formatted'] : '—';
        $arr_date = isset($f['arrive_date_formatted']) ? $f['arrive_date_formatted'] : '—';
        $dep_place = $dep_label;
        $arr_place = $arr_label;
        $cabin_bag = isset($f['cabin_baggage']) ? $f['cabin_baggage'] : '—';
        $checkin_bag = isset($f['checkin_baggage']) ? $f['checkin_baggage'] : '—';
        $tentative = !empty($f['is_tentative']);
        $flight_id = isset($f['id']) ? (int) $f['id'] : 0;

        $html .= '<div class="aj-flight-card" data-flight-id="' . esc_attr($flight_id) . '">';
        $html .= '<div class="aj-flight-header">';
        $html .= '<span class="aj-flight-title">✈ FLIGHT • ' . esc_html($dep_label) . ' to ' . esc_html($arr_label) . '</span>';
        if ($can_toggle) {
            $html .= ' <button type="button" class="ajtb-btn-remove-flight aj-flight-remove-btn" data-tour-id="' . esc_attr($tour_id) . '" data-flight-id="' . esc_attr($flight_id) . '" data-toggle-action="removed">' . esc_html__('Retirer', 'ajinsafro-tour-bridge') . '</button>';
        }
        $html .= '</div>';
        $html .= '<div class="aj-flight-body">';
        $html .= '<div class="aj-flight-col"><div class="aj-flight-icon"><span aria-hidden="true">✈</span></div></div>';
        $html .= '<div class="aj-flight-col aj-flight-center">';
        $html .= '<div class="aj-flight-dep"><div class="aj-flight-date">' . esc_html($dep_date) . '</div><div class="aj-flight-place">' . esc_html($dep_place) . '</div></div>';
        $html .= '<div class="aj-flight-arrow">→</div>';
        $html .= '<div class="aj-flight-arr"><div class="aj-flight-date">' . esc_html($arr_date) . '</div><div class="aj-flight-place">' . esc_html($arr_place) . '</div></div>';
        $html .= '</div>';
        $html .= '<div class="aj-flight-col aj-flight-baggage">';
        $html .= '<div>Cabin: ' . esc_html($cabin_bag) . '</div>';
        $html .= '<div>Check-in: ' . esc_html($checkin_bag) . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="aj-flight-badge-wrap">';
        if ($tentative) {
            $html .= '<span class="aj-flight-badge">' . esc_html__('Tentative Flight', 'ajinsafro-tour-bridge') . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }

    if ($can_toggle && !empty($all_flights)) {
        $addable = array_filter($all_flights, function ($f) use ($displayed_ids) {
            $id = isset($f['id']) ? (int) $f['id'] : 0;
            return $id && !in_array($id, $displayed_ids, true);
        });
        if (!empty($addable)) {
            $html .= '<div class="ajtb-flights-add">';
            $html .= '<span class="ajtb-flights-add-label">' . esc_html__('Ajouter un vol', 'ajinsafro-tour-bridge') . ':</span> ';
            foreach ($addable as $f) {
                $fid = (int) $f['id'];
                $dep = isset($f['depart_label']) ? $f['depart_label'] : '—';
                $arr = isset($f['arrive_label']) ? $f['arrive_label'] : '—';
                $html .= '<button type="button" class="ajtb-btn-add-flight" data-tour-id="' . esc_attr($tour_id) . '" data-flight-id="' . esc_attr($fid) . '" data-toggle-action="added">' . esc_html($dep) . ' → ' . esc_html($arr) . '</button>';
            }
            $html .= '</div>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Debug helper - only outputs in WP_DEBUG mode
 *
 * @param mixed $data Data to dump
 * @param string $label Optional label
 * @return void
 */
function ajtb_debug($data, $label = '') {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    echo '<!-- AJTB Debug';
    if ($label) {
        echo ' [' . esc_html($label) . ']';
    }
    echo ': ';
    echo esc_html(print_r($data, true));
    echo ' -->';
}

/**
 * Render a flight card (MakeMyTrip-style).
 *
 * @param array $flight Flight data
 * @param array $context Optional context: show_remove, show_edit, edit_url, capability, title, unavailable
 * @return string HTML
 */
function aj_render_flight_card($flight, $context = []) {
    if (!is_array($flight)) {
        $flight = [];
    }

    $dash = '—';
    $from = isset($flight['from_city']) ? (string) $flight['from_city'] : (string) ($flight['depart_label'] ?? '');
    $to = isset($flight['to_city']) ? (string) $flight['to_city'] : (string) ($flight['arrive_label'] ?? '');
    $from = trim($from) !== '' ? $from : $dash;
    $to = trim($to) !== '' ? $to : $dash;

    $dep_date = isset($flight['depart_date_formatted']) && trim((string) $flight['depart_date_formatted']) !== ''
        ? (string) $flight['depart_date_formatted']
        : (string) ($flight['depart_date'] ?? $dash);
    $arr_date = isset($flight['arrive_date_formatted']) && trim((string) $flight['arrive_date_formatted']) !== ''
        ? (string) $flight['arrive_date_formatted']
        : (string) ($flight['arrive_date'] ?? $dash);
    if (trim($dep_date) === '') { $dep_date = $dash; }
    if (trim($arr_date) === '') { $arr_date = $dep_date !== '' ? $dep_date : $dash; }

    $cabin_display = isset($flight['cabin_baggage_display'])
        ? (string) $flight['cabin_baggage_display']
        : (string) ($flight['cabin_kg'] ?? ($flight['cabin_baggage'] ?? $dash));
    $checkin_display = isset($flight['checkin_baggage_display'])
        ? (string) $flight['checkin_baggage_display']
        : (string) ($flight['checkin_kg'] ?? ($flight['checkin_baggage'] ?? $dash));
    if (trim($cabin_display) === '') { $cabin_display = $dash; }
    if (trim($checkin_display) === '') { $checkin_display = $dash; }

    // Flight Card v2: optional fields (fallbacks for display only)
    $dep_airport = isset($flight['depart_airport']) ? trim((string) $flight['depart_airport']) : '';
    $arr_airport = isset($flight['arrive_airport']) ? trim((string) $flight['arrive_airport']) : '';
    $origin_code = isset($flight['depart_code']) ? (string) $flight['depart_code'] : (isset($flight['from_code']) ? (string) $flight['from_code'] : '');
    if ($origin_code === '' && $dep_airport !== '' && strlen($dep_airport) <= 4) {
        $origin_code = $dep_airport;
    }
    if ($origin_code === '') {
        $origin_code = $from !== $dash ? mb_substr($from, 0, 3) : $dash;
    }
    $dest_code = isset($flight['arrive_code']) ? (string) $flight['arrive_code'] : (isset($flight['to_code']) ? (string) $flight['to_code'] : '');
    if ($dest_code === '' && $arr_airport !== '' && strlen($arr_airport) <= 4) {
        $dest_code = $arr_airport;
    }
    if ($dest_code === '') {
        $dest_code = $to !== $dash ? mb_substr($to, 0, 3) : $dash;
    }
    $origin_airport = $dep_airport !== '' ? $dep_airport : $from;
    $dest_airport = $arr_airport !== '' ? $arr_airport : $to;
    $dep_time = isset($flight['depart_time']) && trim((string) $flight['depart_time']) !== ''
        ? (string) $flight['depart_time'] : $dash;
    if ($dep_time !== $dash && preg_match('/^\d{4}-\d{2}-\d{2}/', $dep_time)) {
        $dep_time = date_i18n('H:i', strtotime($dep_time));
    }
    $arr_time = isset($flight['arrive_time']) && trim((string) $flight['arrive_time']) !== ''
        ? (string) $flight['arrive_time'] : $dash;
    if ($arr_time !== $dash && preg_match('/^\d{4}-\d{2}-\d{2}/', $arr_time)) {
        $arr_time = date_i18n('H:i', strtotime($arr_time));
    }
    $duration = isset($flight['duration']) ? trim((string) $flight['duration']) : (isset($flight['duration_formatted']) ? trim((string) $flight['duration_formatted']) : '');
    $stops = isset($flight['stops']) ? (int) $flight['stops'] : 0;
    $cabin_class_raw = isset($flight['cabin_class']) ? strtolower(trim((string) $flight['cabin_class'])) : '';
    $class_label = '';
    if ($cabin_class_raw === 'business' || $cabin_class_raw === 'premium') {
        $class_label = _x('Business', 'cabin class', 'ajinsafro-tour-bridge');
    } elseif ($cabin_class_raw !== '') {
        $class_label = _x('Éco', 'cabin class', 'ajinsafro-tour-bridge');
    }
    $bagage_inclus = !empty($flight['bagage_inclus']);

    $is_tentative = !empty($flight['is_tentative']) || !empty($context['is_tentative']);
    $is_unavailable = !empty($context['unavailable']);

    $capability = isset($context['capability']) ? (string) $context['capability'] : 'edit_posts';
    $can_manage = current_user_can($capability) || current_user_can('edit_posts');
    $show_remove = !empty($context['show_remove']) && $can_manage && !$is_unavailable;
    $edit_url = isset($context['edit_url']) ? (string) $context['edit_url'] : '';
    $show_edit = !empty($context['show_edit']) && $can_manage && !$is_unavailable;
    if ($edit_url !== '' && $can_manage && !$is_unavailable) {
        $show_edit = true;
    }

    $title_override = isset($context['title']) ? (string) $context['title'] : '';

    $subtitle_parts = [];
    if ($dep_date !== $dash) {
        $subtitle_parts[] = $dep_date;
    }
    if ($duration !== '') {
        $subtitle_parts[] = $duration;
    }
    $subtitle = implode(' • ', $subtitle_parts);
    if ($subtitle === '') {
        $subtitle = $dash;
    }

    $departure_place_name = isset($flight['departure_place_name']) ? trim((string) $flight['departure_place_name']) : '';
    $departure_place_code = isset($flight['departure_place_code']) ? trim((string) $flight['departure_place_code']) : '';
    $departure_place_label = '';
    if ($departure_place_name !== '' || $departure_place_code !== '') {
        $departure_place_label = $departure_place_name;
        if ($departure_place_code !== '') {
            $departure_place_label .= ($departure_place_label !== '' ? ' (' . $departure_place_code . ')' : $departure_place_code);
        }
    }

    $flight_departure_place_id = isset($flight['departure_place_id']) && $flight['departure_place_id'] !== '' && $flight['departure_place_id'] !== null ? (int) $flight['departure_place_id'] : '';
    ob_start();
    ?>
    <div class="aj-flight-card aj-flight-card--v2<?php echo $is_unavailable ? ' aj-flight-card--unavailable' : ''; ?>" data-flight-id="<?php echo esc_attr((int) ($flight['id'] ?? 0)); ?>" data-departure-place-id="<?php echo esc_attr($flight_departure_place_id); ?>" data-departure-place-name="<?php echo esc_attr($departure_place_name); ?>" data-departure-place-code="<?php echo esc_attr($departure_place_code); ?>">
        <div class="aj-flight-header">
            <div class="aj-flight-header__left">
                <span class="aj-flight-header__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                        <path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3-1 3 1v-1.5L13 19v-5.5z"></path>
                    </svg>
                </span>
                <span class="aj-flight-header__label"><?php esc_html_e('VOL', 'ajinsafro-tour-bridge'); ?></span>
            </div>
            <div class="aj-flight-header__main">
                <h4 class="aj-flight-header__title"><?php echo esc_html($from); ?> &rarr; <?php echo esc_html($to); ?></h4>
                <?php if ($subtitle !== $dash): ?>
                    <p class="aj-flight-header__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
                <?php if ($departure_place_label !== ''): ?>
                    <p class="aj-flight-header__departure-place"><?php echo esc_html(sprintf(__('Départ depuis : %s', 'ajinsafro-tour-bridge'), $departure_place_label)); ?></p>
                <?php endif; ?>
            </div>
            <div class="aj-flight-header__badge-wrap">
                <?php if ($stops === 0): ?>
                    <span class="aj-flight-badge aj-flight-badge--direct"><?php esc_html_e('Direct', 'ajinsafro-tour-bridge'); ?></span>
                <?php else: ?>
                    <span class="aj-flight-badge aj-flight-badge--stops"><?php echo esc_html(sprintf(_n('%d escale', '%d escales', $stops, 'ajinsafro-tour-bridge'), $stops)); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="aj-flight-timeline">
            <div class="aj-flight-timeline__col aj-flight-timeline__col--origin">
                <div class="aj-flight-time"><?php echo esc_html($dep_time); ?></div>
                <div class="aj-flight-place"><?php echo esc_html($origin_code); ?> &bull; <?php echo esc_html($origin_airport); ?></div>
            </div>
            <div class="aj-flight-timeline__center">
                <span class="aj-flight-timeline__dot" aria-hidden="true"></span>
                <span class="aj-flight-timeline__plane" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                        <path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3-1 3 1v-1.5L13 19v-5.5z"></path>
                    </svg>
                </span>
                <span class="aj-flight-timeline__dot" aria-hidden="true"></span>
                <?php if ($duration !== ''): ?>
                    <span class="aj-flight-timeline__duration"><?php echo esc_html($duration); ?></span>
                <?php endif; ?>
            </div>
            <div class="aj-flight-timeline__col aj-flight-timeline__col--dest">
                <div class="aj-flight-time"><?php echo esc_html($arr_time); ?></div>
                <div class="aj-flight-place"><?php echo esc_html($dest_code); ?> &bull; <?php echo esc_html($dest_airport); ?></div>
            </div>
        </div>

        <div class="aj-flight-footer">
            <div class="aj-flight-chips">
                <?php if ($cabin_display !== $dash): ?>
                    <span class="aj-chip"><?php echo esc_html(sprintf(__('Cabine %s', 'ajinsafro-tour-bridge'), $cabin_display)); ?></span>
                <?php endif; ?>
                <?php if ($checkin_display !== $dash): ?>
                    <span class="aj-chip"><?php echo esc_html(sprintf(__('Soute %s', 'ajinsafro-tour-bridge'), $checkin_display)); ?></span>
                <?php endif; ?>
                <?php if ($class_label !== ''): ?>
                    <span class="aj-chip"><?php echo esc_html(sprintf(__('Classe: %s', 'ajinsafro-tour-bridge'), $class_label)); ?></span>
                <?php endif; ?>
                <?php if ($bagage_inclus): ?>
                    <span class="aj-chip"><?php esc_html_e('Bagage inclus', 'ajinsafro-tour-bridge'); ?></span>
                <?php endif; ?>
            </div>
            <div class="aj-flight-actions">
                <button type="button" class="aj-btn aj-btn--secondary aj-flight-action-details"><?php esc_html_e('Détails', 'ajinsafro-tour-bridge'); ?></button>
                <?php if ($show_edit): ?>
                    <a href="<?php echo esc_url($edit_url !== '' ? $edit_url : '#'); ?>" class="aj-btn aj-btn--primary aj-flight-action-edit"><?php esc_html_e('Modifier', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <div class="aj-flight-more-wrap" aria-label="<?php esc_attr_e('Plus d\'options', 'ajinsafro-tour-bridge'); ?>">
                    <button type="button" class="aj-btn aj-btn--icon aj-flight-more" aria-label="<?php esc_attr_e('Menu', 'ajinsafro-tour-bridge'); ?>">&#8942;</button>
                </div>
                <?php if ($show_remove && !$show_edit): ?>
                    <button type="button" class="aj-flight-card__action aj-flight-card__remove" data-aj-flight-remove aria-label="<?php esc_attr_e('Retirer', 'ajinsafro-tour-bridge'); ?>"><?php esc_html_e('Retirer', 'ajinsafro-tour-bridge'); ?></button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_tentative): ?>
            <div class="aj-flight-card__badge-wrap">
                <span class="aj-flight-card__badge aj-flight-card__badge--tentative">
                    <span class="aj-flight-card__badge-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2">
                            <path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3-1 3 1v-1.5L13 19v-5.5z"></path>
                        </svg>
                    </span>
                    <?php esc_html_e('Tentative Flight', 'ajinsafro-tour-bridge'); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
