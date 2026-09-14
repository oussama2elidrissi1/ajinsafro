<?php
// WordPress transport/template boundary. No network, database or WordPress installation.
define('HO_TEST_REAL_CATALOG', true);
require __DIR__.'/hajj-omra-detail.php';
define('AJTH_URL', '/wp-content/plugins/ajinsafro-traveler-home/');
function add_action(...$args) {}
function add_filter(...$args) {}
function get_header() {}
function get_footer() {}
function get_page_by_path($path) { return null; }
function get_query_var($name) { return $GLOBALS['ho_wp_slug'] ?? ''; }
function get_locale() { return 'fr_FR'; }
function trailingslashit($value) { return rtrim($value, '/').'/'; }
function untrailingslashit($value) { return rtrim($value, '/'); }
function sanitize_title($value) { return trim(strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $value)), '-'); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function sanitize_email($value) { return filter_var($value, FILTER_SANITIZE_EMAIL); }
function absint($value) { return abs((int) $value); }
function wp_date($format, $timestamp) { return date($format, $timestamp); }
function wp_verify_nonce($value, $action) { return $value === 'fixture-nonce'; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function ajth_laravel_api_base_url() { return 'https://api.test/api'; }
function ajth_fetch_laravel_catalog_json($path, $key, $ttl) {
    $GLOBALS['ho_wp_reads'][] = [$path, $key, $ttl];
    return ['data' => str_ends_with($path, '/packages') ? [$GLOBALS['ho_wp_offer']] : $GLOBALS['ho_wp_offer']];
}
function add_query_arg($key, $value, $url) {
    $parts = explode('?', $url, 2); parse_str($parts[1] ?? '', $query); $query[$key] = $value;
    return $parts[0].'?'.http_build_query($query);
}
function wp_remote_post($url, $options) {
    $GLOBALS['ho_wp_post'] = [$url, $options];
    return ['status' => 201, 'body' => json_encode(['success' => true, 'message' => 'تم تسجيل طلبكم.'])];
}
function wp_remote_retrieve_response_code($response) { return $response['status']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
class WP_Error {
    public function __construct(public $code, public $message, public $data = null) {}
    public function get_error_message() { return $this->message; }
}
class WP_Post {}
require AJTH_DIR.'includes/hajj-omra-catalog.php';
