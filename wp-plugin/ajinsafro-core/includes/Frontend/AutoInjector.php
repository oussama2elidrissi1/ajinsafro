<?php

namespace Ajinsafro\Frontend;

use Ajinsafro\Core\Options;

class AutoInjector
{
    private Shortcode $shortcode;

    public function __construct(Shortcode $shortcode)
    {
        $this->shortcode = $shortcode;
        
        // Hook into the_content with priority 20 (after most other filters)
        add_filter('the_content', [$this, 'maybe_inject_builder'], 20);
    }

    /**
     * Maybe inject Package Builder into tour content
     */
    public function maybe_inject_builder($content)
    {
        // Only proceed if auto-injection is enabled
        if (!Options::get('auto_inject_builder', true)) {
            return $content;
        }

        // Skip if in admin, feed, or not main query
        if (is_admin() || is_feed()) {
            return $content;
        }

        // Only on single tour pages
        if (!is_singular('st_tours')) {
            return $content;
        }

        // Only in the main loop
        if (!in_the_loop() || !is_main_query()) {
            return $content;
        }

        // Check if content already contains the shortcode (prevent duplication)
        if ($this->has_shortcode_in_content($content)) {
            return $content;
        }

        // Check if tour has Laravel voyage ID
        $post_id = get_the_ID();
        $laravel_voyage_id = get_post_meta($post_id, '_aj_laravel_voyage_id', true);

        if (empty($laravel_voyage_id)) {
            // No Laravel ID, don't inject
            return $content;
        }

        // Get the builder output
        $builder_output = $this->get_builder_output();

        if (empty($builder_output)) {
            return $content;
        }

        // Inject based on position setting
        $position = Options::get('auto_inject_position', 'after');

        if ($position === 'before') {
            return $builder_output . $content;
        } else {
            return $content . $builder_output;
        }
    }

    /**
     * Check if content already contains the package builder shortcode
     */
    private function has_shortcode_in_content($content)
    {
        // Check for [aj_package_builder] shortcode
        if (stripos($content, '[aj_package_builder') !== false) {
            return true;
        }

        // Also check if shortcode has already been processed (might be in HTML)
        if (stripos($content, 'aj-package-builder') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get the Package Builder output
     */
    private function get_builder_output()
    {
        // Use do_shortcode to render the shortcode
        // This ensures exactly the same output as manual shortcode usage
        return do_shortcode('[aj_package_builder]');
    }
}
