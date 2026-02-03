<?php

namespace Ajinsafro\Ajax;

use Ajinsafro\Core\Options;

class Handler
{
    public function __construct()
    {
        // Package state
        add_action('wp_ajax_aj_package_state', [$this, 'get_package_state']);
        add_action('wp_ajax_nopriv_aj_package_state', [$this, 'get_package_state']);

        // Package action (add/remove/modify)
        add_action('wp_ajax_aj_package_action', [$this, 'package_action']);
        add_action('wp_ajax_nopriv_aj_package_action', [$this, 'package_action']);

        // Create checkout
        add_action('wp_ajax_aj_create_checkout', [$this, 'create_checkout']);
        add_action('wp_ajax_nopriv_aj_create_checkout', [$this, 'create_checkout']);
    }

    public function get_package_state()
    {
        check_ajax_referer('ajinsafro_package', 'nonce');

        $voyage_id = absint($_POST['voyage_id'] ?? 0);
        $adults = absint($_POST['adults'] ?? 2);
        $children = absint($_POST['children'] ?? 0);
        $infants = absint($_POST['infants'] ?? 0);

        if (!$voyage_id) {
            wp_send_json_error(['message' => __('Invalid voyage ID', 'ajinsafro-core')]);
        }

        // Rate limiting (simple)
        $this->check_rate_limit('package_state_' . $voyage_id);

        $laravel_base = Options::get('laravel_base_url');
        
        if (empty($laravel_base)) {
            wp_send_json_error(['message' => __('API not configured', 'ajinsafro-core')]);
        }

        $url = trailingslashit($laravel_base) . 'api/public/tours/' . $voyage_id . '/package-state';
        $url = add_query_arg([
            'pax_adults' => $adults,
            'pax_children' => $children,
            'pax_infants' => $infants,
        ], $url);

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200 || !isset($data['success']) || !$data['success']) {
            wp_send_json_error(['message' => __('API request failed', 'ajinsafro-core')]);
        }

        wp_send_json_success($data['data'] ?? []);
    }

    public function package_action()
    {
        check_ajax_referer('ajinsafro_package', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $action_data = $_POST['action_data'] ?? [];

        if (empty($session_id) || empty($action)) {
            wp_send_json_error(['message' => __('Invalid parameters', 'ajinsafro-core')]);
        }

        // Rate limiting
        $this->check_rate_limit('package_action_' . $session_id);

        $laravel_base = Options::get('laravel_base_url');
        
        if (empty($laravel_base)) {
            wp_send_json_error(['message' => __('API not configured', 'ajinsafro-core')]);
        }

        $url = trailingslashit($laravel_base) . 'api/public/package/session/' . $session_id . '/action';

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'action' => $action,
                'item_id' => $action_data['item_id'] ?? null,
                'new_option' => $action_data['new_option'] ?? null,
                'add_data' => $action_data['add_data'] ?? null,
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200 || !isset($data['success']) || !$data['success']) {
            wp_send_json_error(['message' => __('API request failed', 'ajinsafro-core')]);
        }

        wp_send_json_success($data['data'] ?? []);
    }

    public function create_checkout()
    {
        check_ajax_referer('ajinsafro_package', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id'] ?? '');

        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Invalid session', 'ajinsafro-core')]);
        }

        // Rate limiting
        $this->check_rate_limit('create_checkout_' . $session_id);

        $laravel_base = Options::get('laravel_base_url');
        
        if (empty($laravel_base)) {
            wp_send_json_error(['message' => __('API not configured', 'ajinsafro-core')]);
        }

        $url = trailingslashit($laravel_base) . 'api/public/checkout/create';

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'session_id' => $session_id,
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200 || !isset($data['success']) || !$data['success']) {
            wp_send_json_error(['message' => __('API request failed', 'ajinsafro-core')]);
        }

        wp_send_json_success($data['data'] ?? []);
    }

    private function check_rate_limit($key)
    {
        $transient_key = 'ajinsafro_rate_' . md5($key . $_SERVER['REMOTE_ADDR']);
        $count = get_transient($transient_key);

        if (false === $count) {
            set_transient($transient_key, 1, 60); // 1 minute
        } else {
            if ($count >= 30) { // Max 30 requests per minute
                wp_send_json_error(['message' => __('Rate limit exceeded', 'ajinsafro-core')], 429);
            }
            set_transient($transient_key, $count + 1, 60);
        }
    }
}
