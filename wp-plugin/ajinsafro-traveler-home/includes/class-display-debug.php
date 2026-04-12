<?php
/**
 * Display / runtime diagnostics (REST) — compare Laravel DB vs WP runtime, title raw vs filtered, templates, caches.
 *
 * Auth: same secret as catalog invalidate (X-Ajth-Secret header) OR logged-in user with manage_options.
 *
 * Endpoints (base: /wp-json/ajth/v1/):
 * - GET  runtime-db?verify_post_id=14353  — DB_NAME, $wpdb->prefix, optional post_title from DB
 * - GET  post-title-compare?post_id=14353 — post_title raw, the_title filtered, match flag
 * - GET  resolve-template?post_type=st_cars — existing single-*.php paths on disk (child + parent theme)
 * - GET  the-title-filters — registered the_title callbacks (priority + name)
 * - POST flush-caches — wp_cache_flush + best-effort extras
 *
 * Optional wp-config.php:
 *   define('AJTH_FORCE_RAW_POST_TITLE', true);
 * Forces singular titles to use post_title for st_cars / st_activity (last filter, priority 99999).
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return bool|WP_Error
 */
function ajth_display_debug_permission(WP_REST_Request $request)
{
    if (current_user_can('manage_options')) {
        return true;
    }

    $expected = function_exists('ajth_get_catalog_invalidate_secret') ? ajth_get_catalog_invalidate_secret() : '';
    if ($expected === '') {
        return new WP_Error('ajth_debug_forbidden', 'Configure AJTH_LARAVEL_INVALIDATE_SECRET or ajth_laravel_invalidate_secret.', ['status' => 403]);
    }

    $sent = $request->get_header('X-Ajth-Secret');
    if (! is_string($sent) || $sent === '') {
        $sent = (string) $request->get_param('secret');
    }

    if ($sent === '' || ! hash_equals($expected, $sent)) {
        return new WP_Error('ajth_debug_forbidden', 'Invalid secret.', ['status' => 403]);
    }

    return true;
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ajth_rest_runtime_db(WP_REST_Request $request)
{
    global $wpdb;

    $verifyId = absint($request->get_param('verify_post_id'));
    $row = [
        'db_name' => defined('DB_NAME') ? DB_NAME : null,
        'table_prefix' => $wpdb->prefix,
        'db_host' => defined('DB_HOST') ? DB_HOST : null,
        'charset_collate' => $wpdb->charset,
    ];

    if ($verifyId > 0) {
        $row['verify_post_id'] = $verifyId;
        $row['verify_post_title_from_db'] = $wpdb->get_var($wpdb->prepare(
            "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
            $verifyId
        ));
        $row['verify_post_type_from_db'] = $wpdb->get_var($wpdb->prepare(
            "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d",
            $verifyId
        ));
    }

    return new WP_REST_Response($row, 200);
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function ajth_rest_post_title_compare(WP_REST_Request $request)
{
    $postId = absint($request->get_param('post_id'));
    if ($postId < 1) {
        return new WP_Error('bad_request', 'post_id required', ['status' => 400]);
    }

    $post = get_post($postId);
    if (! $post) {
        return new WP_Error('not_found', 'Post not found', ['status' => 404]);
    }

    $raw = $post->post_title;
    // Re-run only the same filter chain WordPress uses in get_the_title() for this ID.
    $filtered = apply_filters('the_title', $raw, $postId);

    return new WP_REST_Response([
        'post_id' => $postId,
        'post_type' => $post->post_type,
        'post_title_raw' => $raw,
        'the_title_filtered' => $filtered,
        'filtered_equals_raw' => ($filtered === $raw),
        'note' => 'If raw matches Laravel SQL but filtered differs, a the_title filter alters display.',
    ], 200);
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ajth_rest_resolve_template(WP_REST_Request $request)
{
    $postType = sanitize_key($request->get_param('post_type'));
    if ($postType === '') {
        $postType = 'st_cars';
    }

    $stylesheet = get_stylesheet_directory();
    $templateRoot = get_template_directory();
    $candidates = [
        "{$stylesheet}/single-{$postType}.php",
        "{$templateRoot}/single-{$postType}.php",
        "{$stylesheet}/singular.php",
        "{$templateRoot}/singular.php",
        "{$stylesheet}/single.php",
        "{$templateRoot}/single.php",
    ];

    $resolved = [];
    foreach ($candidates as $path) {
        $resolved[] = [
            'path' => $path,
            'exists' => file_exists($path),
        ];
    }

    return new WP_REST_Response([
        'post_type' => $postType,
        'stylesheet' => $stylesheet,
        'template_directory' => $templateRoot,
        'child_theme' => get_stylesheet(),
        'parent_theme' => get_template(),
        'single_template_candidates' => $resolved,
    ], 200);
}

/**
 * @return WP_REST_Response
 */
function ajth_rest_the_title_filters()
{
    global $wp_filter;

    $out = [];
    if (empty($wp_filter['the_title'])) {
        return new WP_REST_Response(['the_title' => []], 200);
    }

    $hook = $wp_filter['the_title'];
    if (! isset($hook->callbacks) || ! is_array($hook->callbacks)) {
        return new WP_REST_Response(['the_title' => [], 'note' => 'Could not read callbacks'], 200);
    }

    foreach ($hook->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $cb) {
            $fn = $cb['function'];
            if (is_string($fn)) {
                $name = $fn;
            } elseif (is_array($fn) && count($fn) === 2) {
                if (is_object($fn[0])) {
                    $name = get_class($fn[0]).'::'.$fn[1];
                } else {
                    $name = (string) $fn[0].'::'.$fn[1];
                }
            } elseif ($fn instanceof Closure) {
                $name = 'Closure';
            } else {
                $name = 'callable';
            }
            $out[] = [
                'priority' => (int) $priority,
                'callback' => $name,
            ];
        }
    }

    return new WP_REST_Response(['the_title' => $out], 200);
}

/**
 * @return WP_REST_Response
 */
function ajth_rest_flush_caches()
{
    $report = ['wp_cache_flush' => false];

    if (function_exists('wp_cache_flush')) {
        $report['wp_cache_flush'] = (bool) wp_cache_flush();
    }

    if (function_exists('wp_cache_flush_runtime')) {
        wp_cache_flush_runtime();
        $report['wp_cache_flush_runtime'] = true;
    }

    wp_suspend_cache_invalidation(false);

    if (function_exists('opcache_reset')) {
        $report['opcache_reset_attempted'] = @opcache_reset();
    }

    return new WP_REST_Response([
        'ok' => true,
        'report' => $report,
        'note' => 'Page cache plugins (WP Rocket, etc.) and CDN must be purged separately.',
    ], 200);
}

/**
 * Optional: force front-end title to DB post_title for catalog CPTs (after all other filters).
 *
 * @param string $title
 * @param int    $post_id
 * @return string
 */
function ajth_force_raw_post_title($title, $post_id)
{
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return $title;
    }

    if (is_admin() || ! $post_id) {
        return $title;
    }

    if (! defined('AJTH_FORCE_RAW_POST_TITLE') || ! AJTH_FORCE_RAW_POST_TITLE) {
        return $title;
    }

    $pt = get_post_type($post_id);
    if (! in_array($pt, ['st_cars', 'st_activity'], true)) {
        return $title;
    }

    $post = get_post($post_id);
    if (! $post) {
        return $title;
    }

    return $post->post_title;
}

add_action('rest_api_init', function () {
    register_rest_route('ajth/v1', '/runtime-db', [
        'methods' => 'GET',
        'callback' => 'ajth_rest_runtime_db',
        'permission_callback' => 'ajth_display_debug_permission',
    ]);
    register_rest_route('ajth/v1', '/post-title-compare', [
        'methods' => 'GET',
        'callback' => 'ajth_rest_post_title_compare',
        'permission_callback' => 'ajth_display_debug_permission',
    ]);
    register_rest_route('ajth/v1', '/resolve-template', [
        'methods' => 'GET',
        'callback' => 'ajth_rest_resolve_template',
        'permission_callback' => 'ajth_display_debug_permission',
    ]);
    register_rest_route('ajth/v1', '/the-title-filters', [
        'methods' => 'GET',
        'callback' => 'ajth_rest_the_title_filters',
        'permission_callback' => 'ajth_display_debug_permission',
    ]);
    register_rest_route('ajth/v1', '/flush-caches', [
        'methods' => 'POST',
        'callback' => 'ajth_rest_flush_caches',
        'permission_callback' => 'ajth_display_debug_permission',
    ]);
});

add_filter('the_title', 'ajth_force_raw_post_title', 99999, 2);
