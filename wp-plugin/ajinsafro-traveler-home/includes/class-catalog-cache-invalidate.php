<?php
/**
 * REST endpoint so Laravel can ask WordPress to drop cached post objects after direct DB writes.
 *
 * Configure the same secret in Laravel (.env WP_CATALOG_INVALIDATE_SECRET) and either:
 *   define('AJTH_LARAVEL_INVALIDATE_SECRET', '...'); in wp-config.php
 * or
 *   update_option('ajth_laravel_invalidate_secret', '...');
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return string
 */
function ajth_get_catalog_invalidate_secret()
{
    if (defined('AJTH_LARAVEL_INVALIDATE_SECRET') && is_string(AJTH_LARAVEL_INVALIDATE_SECRET) && AJTH_LARAVEL_INVALIDATE_SECRET !== '') {
        return AJTH_LARAVEL_INVALIDATE_SECRET;
    }

    return (string) get_option('ajth_laravel_invalidate_secret', '');
}

/**
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function ajth_catalog_invalidate_permission($request)
{
    $expected = ajth_get_catalog_invalidate_secret();
    if ($expected === '') {
        return new WP_Error('ajth_invalidate_disabled', 'Catalog invalidate is not configured.', ['status' => 403]);
    }

    $sent = $request->get_header('X-Ajth-Secret');
    if (! is_string($sent) || $sent === '') {
        $sent = (string) $request->get_param('secret');
    }
    if ($sent === '' || ! hash_equals($expected, $sent)) {
        return new WP_Error('ajth_invalidate_forbidden', 'Invalid secret.', ['status' => 403]);
    }

    return true;
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function ajth_catalog_invalidate_posts_rest($request)
{
    $ids = $request->get_param('post_ids');
    if (! is_array($ids)) {
        return new WP_Error('bad_request', 'post_ids must be an array.', ['status' => 400]);
    }

    $cleared = [];
    foreach ($ids as $raw) {
        $id = (int) $raw;
        if ($id <= 0) {
            continue;
        }
        clean_post_cache($id);
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($id, 'posts');
            wp_cache_delete($id, 'post_meta');
        }
        $cleared[] = $id;
    }

    return new WP_REST_Response([
        'ok' => true,
        'cleared' => $cleared,
    ], 200);
}

add_action('rest_api_init', function () {
    register_rest_route('ajth/v1', '/invalidate-posts', [
        'methods' => 'POST',
        'callback' => 'ajth_catalog_invalidate_posts_rest',
        'permission_callback' => 'ajth_catalog_invalidate_permission',
    ]);
});
