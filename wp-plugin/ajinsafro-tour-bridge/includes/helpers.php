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
 * Get tour thumbnail URL
 *
 * @param int $post_id Post ID
 * @param string $size Image size
 * @return string Image URL or placeholder
 */
function ajtb_get_tour_thumbnail($post_id, $size = 'large') {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    
    if ($thumbnail_id) {
        $image = wp_get_attachment_image_url($thumbnail_id, $size);
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
