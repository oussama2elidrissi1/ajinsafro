<?php

namespace Ajinsafro\Frontend;

use Ajinsafro\Core\Options;
use Ajinsafro\Core\LaravelApi;

class Shortcode
{
    public function __construct()
    {
        add_shortcode('aj_package_builder', [$this, 'render']);
    }

    public function render($atts)
    {
        // Only on single st_tours
        if (!is_singular('st_tours')) {
            return '<p>' . __('This shortcode only works on tour pages.', 'ajinsafro-core') . '</p>';
        }

        $post_id = get_the_ID();
        $laravel_voyage_id = get_post_meta($post_id, '_aj_laravel_voyage_id', true);

        if (empty($laravel_voyage_id)) {
            return '<p>' . __('No Laravel voyage linked to this tour.', 'ajinsafro-core') . '</p>';
        }

        $atts = shortcode_atts([
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
        ], $atts);

        // Get package state from cache or API
        $cache_key = 'ajinsafro_package_' . $laravel_voyage_id;
        $cache_ttl = Options::get('cache_ttl_seconds', 300);
        
        $package_state = $cache_ttl > 0 ? get_transient($cache_key) : false;

        if (false === $package_state) {
            $package_state = $this->fetch_package_state($laravel_voyage_id, $atts);
            
            if (is_wp_error($package_state)) {
                return '<div class="aj-error">' . esc_html($package_state->get_error_message()) . '</div>';
            }

            if ($cache_ttl > 0) {
                set_transient($cache_key, $package_state, $cache_ttl);
            }
        }

        ob_start();
        include AJINSAFRO_PLUGIN_DIR . 'templates/frontend/package-builder.php';
        return ob_get_clean();
    }

    private function fetch_package_state($voyage_id, $params)
    {
        $laravel_base = Options::get('laravel_base_url');
        
        if (empty($laravel_base)) {
            return new \WP_Error('no_config', __('Laravel API URL not configured.', 'ajinsafro-core'));
        }

        $url = trailingslashit($laravel_base) . 'api/public/tours/' . $voyage_id . '/package-state';
        $url = add_query_arg([
            'pax_adults' => absint($params['adults']),
            'pax_children' => absint($params['children']),
            'pax_infants' => absint($params['infants']),
        ], $url);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('api_error', sprintf(__('API returned error code: %d', 'ajinsafro-core'), $code));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['success']) || !$data['success']) {
            return new \WP_Error('api_failed', __('API request failed.', 'ajinsafro-core'));
        }

        return $data['data'] ?? [];
    }
}
