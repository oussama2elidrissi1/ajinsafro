<?php
/**
 * Assets Handler
 *
 * Enqueues CSS and JS only on st_tours single and archive pages
 *
 * @package AjinsafroBridge\Hooks
 */

namespace AjinsafroBridge\Hooks;

class Assets
{
    /**
     * Enqueue assets for tour pages only
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        // Only enqueue on tour pages
        if (!$this->isTourPage()) {
            return;
        }

        $this->enqueueStyles();
        $this->enqueueScripts();
    }

    /**
     * Check if current page is a tour page
     *
     * @return bool
     */
    private function isTourPage(): bool
    {
        // Single tour
        if (is_singular(AJBRIDGE_POST_TYPE)) {
            return true;
        }

        // Tour archive
        if (is_post_type_archive(AJBRIDGE_POST_TYPE)) {
            return true;
        }

        // Tour taxonomy archive
        if (is_tax(['st_tour_type', 'tours_cat', 'tour_tag'])) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue stylesheets
     *
     * @return void
     */
    private function enqueueStyles(): void
    {
        // Single tour CSS
        if (is_singular(AJBRIDGE_POST_TYPE)) {
            wp_enqueue_style(
                'ajbridge-tour-single',
                AJBRIDGE_PLUGIN_URL . 'assets/css/tour-single.css',
                [],
                AJBRIDGE_VERSION
            );
        }

        // Archive CSS (for archive and taxonomy pages)
        if (is_post_type_archive(AJBRIDGE_POST_TYPE) || is_tax(['st_tour_type', 'tours_cat', 'tour_tag'])) {
            wp_enqueue_style(
                'ajbridge-tour-archive',
                AJBRIDGE_PLUGIN_URL . 'assets/css/tour-archive.css',
                [],
                AJBRIDGE_VERSION
            );
        }
    }

    /**
     * Enqueue JavaScript files
     *
     * @return void
     */
    private function enqueueScripts(): void
    {
        // Single tour JS
        if (is_singular(AJBRIDGE_POST_TYPE)) {
            wp_enqueue_script(
                'ajbridge-tour-single',
                AJBRIDGE_PLUGIN_URL . 'assets/js/tour-single.js',
                ['jquery'],
                AJBRIDGE_VERSION,
                true // Load in footer
            );

            // Pass data to JS
            wp_localize_script('ajbridge-tour-single', 'ajbridgeTour', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ajbridge_tour_nonce'),
                'postId' => get_the_ID(),
                'currency' => get_option('st_currency', 'MAD'),
            ]);
        }

        // Archive JS (for archive and taxonomy pages)
        if (is_post_type_archive(AJBRIDGE_POST_TYPE) || is_tax(['st_tour_type', 'tours_cat', 'tour_tag'])) {
            wp_enqueue_script(
                'ajbridge-tour-archive',
                AJBRIDGE_PLUGIN_URL . 'assets/js/tour-archive.js',
                ['jquery'],
                AJBRIDGE_VERSION,
                true
            );

            wp_localize_script('ajbridge-tour-archive', 'ajbridgeArchive', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ajbridge_archive_nonce'),
            ]);
        }
    }
}
