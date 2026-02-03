<?php

namespace Ajinsafro\Admin;

use Ajinsafro\Core\Options;

class Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_menu()
    {
        add_menu_page(
            __('Ajinsafro Core', 'ajinsafro-core'),
            __('Ajinsafro Core', 'ajinsafro-core'),
            'manage_options',
            'ajinsafro-settings',
            [$this, 'render_page'],
            'dashicons-admin-generic',
            30
        );
    }

    public function register_settings()
    {
        register_setting('ajinsafro_settings_group', Options::OPTION_KEY, [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    public function sanitize_settings($input)
    {
        $sanitized = [];

        // Laravel → WP settings
        $sanitized['laravel_base_url'] = esc_url_raw(trim($input['laravel_base_url'] ?? ''));
        $sanitized['booking_checkout_base_url'] = esc_url_raw(trim($input['booking_checkout_base_url'] ?? ''));
        $sanitized['hmac_secret'] = sanitize_text_field($input['hmac_secret'] ?? '');
        $sanitized['enable_sync'] = !empty($input['enable_sync']);
        
        // WP → Laravel settings
        $sanitized['enable_laravel_sync'] = !empty($input['enable_laravel_sync']);
        $sanitized['laravel_sync_base_url'] = esc_url_raw(trim($input['laravel_sync_base_url'] ?? ''));
        $sanitized['laravel_webhook_token'] = sanitize_text_field($input['laravel_webhook_token'] ?? '');
        
        // Other settings
        $sanitized['cache_ttl_seconds'] = absint($input['cache_ttl_seconds'] ?? 300);
        $sanitized['auto_inject_builder'] = !empty($input['auto_inject_builder']);
        $sanitized['auto_inject_position'] = in_array($input['auto_inject_position'] ?? 'after', ['before', 'after']) 
            ? $input['auto_inject_position'] 
            : 'after';

        return $sanitized;
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings
        if (isset($_POST['ajinsafro_save_settings'])) {
            check_admin_referer('ajinsafro_settings_nonce');
            
            $options = [
                'laravel_base_url' => $_POST['laravel_base_url'] ?? '',
                'booking_checkout_base_url' => $_POST['booking_checkout_base_url'] ?? '',
                'hmac_secret' => $_POST['hmac_secret'] ?? '',
                'enable_sync' => isset($_POST['enable_sync']),
                'enable_laravel_sync' => isset($_POST['enable_laravel_sync']),
                'laravel_sync_base_url' => $_POST['laravel_sync_base_url'] ?? '',
                'laravel_webhook_token' => $_POST['laravel_webhook_token'] ?? '',
                'cache_ttl_seconds' => absint($_POST['cache_ttl_seconds'] ?? 300),
                'auto_inject_builder' => isset($_POST['auto_inject_builder']),
                'auto_inject_position' => $_POST['auto_inject_position'] ?? 'after',
            ];

            Options::update_all($this->sanitize_settings($options));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'ajinsafro-core') . '</p></div>';
        }

        $options = Options::get();

        include AJINSAFRO_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
