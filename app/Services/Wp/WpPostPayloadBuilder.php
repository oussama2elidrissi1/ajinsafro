<?php

namespace App\Services\Wp;

/**
 * Builds a full payload for WordPress wp_posts INSERT compatible with MySQL strict mode.
 * All NOT NULL columns without default are set to safe values so INSERT never fails.
 */
class WpPostPayloadBuilder
{
    /**
     * Default values for WordPress NOT NULL columns that have no DB default.
     * Used so INSERT works with MySQL strict (no "Field doesn't have a default value").
     */
    protected static function notNullDefaults(): array
    {
        return [
            'to_ping' => '',
            'pinged' => '',
            'post_password' => '',
            'post_content_filtered' => '',
            'guid' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'post_mime_type' => '',
            'comment_count' => 0,
        ];
    }

    /**
     * Build a full wp_posts row array for INSERT.
     * Merges: NOT NULL defaults → $defaults → $data. Ensures post_excerpt is never null.
     *
     * @param array $data   Post fields (post_title, post_name, post_content, post_type, etc.)
     * @param array $defaults Optional defaults (e.g. post_date, post_modified, post_status)
     * @return array Full row safe for DB::table('posts')->insert() or Model::create()
     */
    public static function buildWpPostPayload(array $data, array $defaults = []): array
    {
        $merged = array_merge(
            static::notNullDefaults(),
            $defaults,
            $data
        );

        if (!array_key_exists('post_excerpt', $merged) || $merged['post_excerpt'] === null) {
            $merged['post_excerpt'] = '';
        }

        return $merged;
    }
}
