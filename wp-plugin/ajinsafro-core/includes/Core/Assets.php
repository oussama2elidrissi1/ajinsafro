<?php

namespace Ajinsafro\Core;

class Assets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
    }

    public function enqueue_frontend()
    {
        if (!is_singular('st_tours')) {
            return;
        }

        wp_enqueue_style(
            'ajinsafro-package-builder',
            AJINSAFRO_PLUGIN_URL . 'assets/css/package-builder.css',
            [],
            AJINSAFRO_VERSION
        );

        wp_enqueue_script(
            'ajinsafro-package-builder',
            AJINSAFRO_PLUGIN_URL . 'assets/js/package-builder.js',
            ['jquery'],
            AJINSAFRO_VERSION,
            true
        );

        wp_localize_script('ajinsafro-package-builder', 'ajinsafroData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajinsafro_package'),
            'strings' => [
                'loading' => __('Loading...', 'ajinsafro-core'),
                'error' => __('An error occurred. Please try again.', 'ajinsafro-core'),
                'bookNow' => __('Book Now', 'ajinsafro-core'),
            ],
        ]);
    }

    public function enqueue_admin($hook)
    {
        if ($hook !== 'toplevel_page_ajinsafro-settings') {
            return;
        }

        wp_enqueue_style(
            'ajinsafro-admin',
            AJINSAFRO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AJINSAFRO_VERSION
        );
    }
}
