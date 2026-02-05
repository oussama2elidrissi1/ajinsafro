<?php
/**
 * Plugin Name: Ajinsafro Sync Webhook
 * Description: Notifies Laravel when st_tours posts are updated (bidirectional sync)
 * Version: 1.0.0
 * Author: Ajinsafro
 */

if (!defined('ABSPATH')) exit;

/**
 * Notify Laravel on tour save
 */
add_action('save_post_st_tours', function($post_id, $post, $update) {
    // Prevent autosave/revision notifications
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if ($post->post_status === 'auto-draft') return;

    // Prevent infinite loops (if this save came from Laravel)
    if (get_transient('ajsync_skip_notify_' . $post_id)) {
        delete_transient('ajsync_skip_notify_' . $post_id);
        return;
    }

    // Send webhook to Laravel
    ajsync_notify_laravel($post_id, 'updated');
}, 10, 3);

/**
 * Send webhook to Laravel
 */
function ajsync_notify_laravel($wp_post_id, $action = 'updated') {
    $laravel_url = get_option('ajsync_laravel_url', '');
    $webhook_secret = get_option('ajsync_webhook_secret', '');

    if (empty($laravel_url) || empty($webhook_secret)) {
        error_log('Ajinsafro Sync: Laravel URL or secret not configured');
        return false;
    }

    $endpoint = rtrim($laravel_url, '/') . '/api/wp-sync/tour-updated';

    $body = json_encode([
        'wp_post_id' => $wp_post_id,
        'action' => $action,
        'timestamp' => time(),
    ]);

    $signature = hash_hmac('sha256', $body, $webhook_secret);

    $response = wp_remote_post($endpoint, [
        'timeout' => 15,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Signature' => $signature,
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        error_log('Ajinsafro Sync: Webhook failed - ' . $response->get_error_message());
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    
    if ($code !== 200) {
        error_log('Ajinsafro Sync: Webhook returned ' . $code);
        return false;
    }

    return true;
}

/**
 * Admin settings page
 */
add_action('admin_menu', function() {
    add_options_page(
        'Ajinsafro Sync Settings',
        'Ajinsafro Sync',
        'manage_options',
        'ajsync-settings',
        'ajsync_settings_page'
    );
});

function ajsync_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['ajsync_save'])) {
        check_admin_referer('ajsync_settings');
        
        update_option('ajsync_laravel_url', sanitize_text_field($_POST['laravel_url']));
        update_option('ajsync_webhook_secret', sanitize_text_field($_POST['webhook_secret']));
        
        echo '<div class="notice notice-success"><p>Paramètres sauvegardés</p></div>';
    }

    $laravel_url = get_option('ajsync_laravel_url', '');
    $webhook_secret = get_option('ajsync_webhook_secret', '');
    ?>
    <div class="wrap">
        <h1>Ajinsafro Sync - Configuration</h1>
        <form method="post">
            <?php wp_nonce_field('ajsync_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="laravel_url">Laravel URL</label></th>
                    <td>
                        <input type="url" id="laravel_url" name="laravel_url" 
                               value="<?php echo esc_attr($laravel_url); ?>" 
                               class="regular-text" 
                               placeholder="https://admin.ajinsafro.com">
                        <p class="description">URL de base de votre application Laravel (sans /api)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="webhook_secret">Webhook Secret</label></th>
                    <td>
                        <input type="text" id="webhook_secret" name="webhook_secret" 
                               value="<?php echo esc_attr($webhook_secret); ?>" 
                               class="regular-text">
                        <p class="description">Secret HMAC partagé avec Laravel (WP_WEBHOOK_SECRET dans .env)</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="ajsync_save" class="button-primary" value="Sauvegarder">
            </p>
        </form>

        <hr>
        
        <h2>Test de connexion</h2>
        <p>
            <button type="button" class="button" onclick="ajsyncTestConnection()">Tester la connexion</button>
            <span id="ajsync-test-result"></span>
        </p>

        <script>
        function ajsyncTestConnection() {
            var result = document.getElementById('ajsync-test-result');
            result.innerHTML = ' <span style="color: #999;">Test en cours...</span>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ajsync_test')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = ' <span style="color: green;">✓ Connexion OK</span>';
                    } else {
                        result.innerHTML = ' <span style="color: red;">✗ ' + data.error + '</span>';
                    }
                });
        }
        </script>
    </div>
    <?php
}

/**
 * AJAX test endpoint
 */
add_action('wp_ajax_ajsync_test', function() {
    $laravel_url = get_option('ajsync_laravel_url', '');
    $webhook_secret = get_option('ajsync_webhook_secret', '');

    if (empty($laravel_url) || empty($webhook_secret)) {
        wp_send_json(['success' => false, 'error' => 'Configuration manquante']);
    }

    $endpoint = rtrim($laravel_url, '/') . '/api/wp-sync/tour-updated';
    
    $body = json_encode(['test' => true, 'wp_post_id' => 0]);
    $signature = hash_hmac('sha256', $body, $webhook_secret);

    $response = wp_remote_post($endpoint, [
        'timeout' => 10,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Signature' => $signature,
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        wp_send_json(['success' => false, 'error' => $response->get_error_message()]);
    }

    $code = wp_remote_retrieve_response_code($response);
    wp_send_json(['success' => $code === 200 || $code === 400, 'code' => $code]);
});
