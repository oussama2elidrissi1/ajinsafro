<?php
/**
 * Template Override Handler
 *
 * Overrides single-st_tours and archive-st_tours templates
 * with plugin templates while maintaining Traveler theme fallback.
 *
 * @package AjinsafroBridge\Hooks
 */

namespace AjinsafroBridge\Hooks;

use AjinsafroBridge\Services\TourAssembler;

class TemplateOverride
{
    /**
     * TourAssembler instance
     * @var TourAssembler
     */
    private TourAssembler $assembler;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assembler = new TourAssembler();
    }

    /**
     * Override template based on post type
     *
     * @param string $template Original template path
     * @return string Modified or original template path
     */
    public function overrideTemplate(string $template): string
    {
        // Check for single st_tours
        if (is_singular(AJBRIDGE_POST_TYPE)) {
            return $this->handleSingleTemplate($template);
        }

        // Check for archive st_tours
        if (is_post_type_archive(AJBRIDGE_POST_TYPE)) {
            return $this->handleArchiveTemplate($template);
        }

        // Check for taxonomy archive (tours categories if any)
        if (is_tax() && $this->isTourTaxonomy()) {
            return $this->handleArchiveTemplate($template);
        }

        return $template;
    }

    /**
     * Handle single tour template override
     *
     * @param string $template Original template
     * @return string Plugin template or original
     */
    private function handleSingleTemplate(string $template): string
    {
        $plugin_template = AJBRIDGE_PLUGIN_DIR . 'templates/tour/single.php';

        // Check if plugin template exists
        if (file_exists($plugin_template)) {
            // Prepare tour data and make it globally available
            $this->prepareTourData();

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ajinsafro Bridge: Using single template override');
            }

            return $plugin_template;
        }

        // Fallback to theme template
        return $template;
    }

    /**
     * Handle archive tour template override
     *
     * @param string $template Original template
     * @return string Plugin template or original
     */
    private function handleArchiveTemplate(string $template): string
    {
        $plugin_template = AJBRIDGE_PLUGIN_DIR . 'templates/tour/archive.php';

        // Check if plugin template exists
        if (file_exists($plugin_template)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ajinsafro Bridge: Using archive template override');
            }

            return $plugin_template;
        }

        // Fallback to theme template
        return $template;
    }

    /**
     * Prepare tour data for single template
     *
     * Sets up global $tourData variable for use in templates
     *
     * @return void
     */
    private function prepareTourData(): void
    {
        global $tourData;

        $post_id = get_the_ID();

        if ($post_id) {
            // Get assembled tour data
            $tourData = $this->assembler->getTourData($post_id);

            // Allow filtering of tour data
            $tourData = apply_filters('ajbridge_tour_data', $tourData, $post_id);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ajinsafro Bridge: Tour data prepared for post ID ' . $post_id);
            }
        }
    }

    /**
     * Check if current taxonomy belongs to tours
     *
     * @return bool
     */
    private function isTourTaxonomy(): bool
    {
        $taxonomy = get_queried_object();

        if (!$taxonomy || !isset($taxonomy->taxonomy)) {
            return false;
        }

        // Traveler theme tour taxonomies
        $tour_taxonomies = [
            'st_tour_type',
            'tours_cat',
            'tour_tag',
        ];

        return in_array($taxonomy->taxonomy, $tour_taxonomies);
    }
}
