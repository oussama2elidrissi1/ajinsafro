<?php
/**
 * Location Service - ensure/create Traveler locations (country + city).
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Location_Service {

    /**
     * Resolve or create a location (country + optional city).
     *
     * @param string $country_code ISO-3166 alpha-2 country code.
     * @param string $city_name Optional city name.
     * @return array{id:int,name:string,country_code:string,created:bool}
     */
    public function ensure_location($country_code, $city_name = '') {
        $country_code = strtoupper(trim((string) $country_code));
        $city_name = trim((string) $city_name);

        if (!preg_match('/^[A-Z]{2}$/', $country_code)) {
            throw new InvalidArgumentException('country_code must contain 2 letters.');
        }

        $country = $this->ensure_country_location($country_code);
        $created = !empty($country['created']);
        $final = $country;

        if ($city_name !== '') {
            $city = $this->find_location_by_parent_and_title((int) $country['id'], $city_name);
            if ($city) {
                $final = [
                    'id' => (int) $city['ID'],
                    'name' => (string) $city['post_title'],
                    'created' => false,
                ];
            } else {
                $final = $this->create_location_post((int) $country['id'], $city_name);
            }
            $created = $created || !empty($final['created']);
        }

        return [
            'id' => (int) $final['id'],
            'name' => (string) $final['name'],
            'country_code' => $country_code,
            'created' => (bool) $created,
        ];
    }

    /**
     * Ensure a country location exists at root level.
     *
     * @param string $country_code
     * @return array{id:int,name:string,created:bool}
     */
    public function ensure_country_location($country_code) {
        $country_code = strtoupper(trim((string) $country_code));
        $labels = $this->country_labels($country_code);

        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title
                 FROM {$wpdb->posts}
                 WHERE post_type = %s
                   AND post_parent = 0
                   AND post_status <> %s",
                'location',
                'trash'
            ),
            ARRAY_A
        );

        if (!empty($rows)) {
            $needle = array_fill_keys(array_map([$this, 'normalize_title'], $labels), true);
            foreach ($rows as $row) {
                $candidate = $this->normalize_title((string) $row['post_title']);
                if (isset($needle[$candidate])) {
                    return [
                        'id' => (int) $row['ID'],
                        'name' => (string) $row['post_title'],
                        'created' => false,
                    ];
                }
            }
        }

        return $this->create_location_post(0, $labels[0]);
    }

    /**
     * Find location by parent and normalized title.
     *
     * @param int $parent_id
     * @param string $title
     * @return array|null
     */
    public function find_location_by_parent_and_title($parent_id, $title) {
        $parent_id = (int) $parent_id;
        $title = trim((string) $title);
        if ($title === '') {
            return null;
        }

        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title
                 FROM {$wpdb->posts}
                 WHERE post_type = %s
                   AND post_parent = %d
                   AND post_status <> %s",
                'location',
                $parent_id,
                'trash'
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return null;
        }

        $key = $this->normalize_title($title);
        foreach ($rows as $row) {
            if ($this->normalize_title((string) $row['post_title']) === $key) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Create location post with explicit non-null WP fields.
     *
     * @param int $parent_id
     * @param string $title
     * @return array{id:int,name:string,created:bool}
     */
    public function create_location_post($parent_id, $title) {
        $parent_id = (int) $parent_id;
        $title = trim((string) $title);

        if ($title === '') {
            throw new InvalidArgumentException('Location title cannot be empty.');
        }

        $slug = sanitize_title($title);
        if ($slug === '') {
            $slug = 'location';
        }
        $slug = wp_unique_post_slug($slug, 0, 'publish', 'location', $parent_id);

        $post_date = current_time('mysql');
        $post_date_gmt = gmdate('Y-m-d H:i:s');

        $postarr = [
            'post_author' => (int) get_current_user_id() ?: 1,
            'post_date' => $post_date,
            'post_date_gmt' => $post_date_gmt,
            'post_content' => '',
            'post_title' => $title,
            'post_excerpt' => '',
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_password' => '',
            'post_name' => $slug,
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => $post_date,
            'post_modified_gmt' => $post_date_gmt,
            'post_content_filtered' => '',
            'post_parent' => $parent_id,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'location',
            'post_mime_type' => '',
            'comment_count' => 0,
        ];

        $location_id = wp_insert_post($postarr, true, true);
        if (is_wp_error($location_id)) {
            $this->log('error', 'Failed to create location via wp_insert_post', [
                'title' => $title,
                'parent_id' => $parent_id,
                'error' => $location_id->get_error_message(),
            ]);
            throw new RuntimeException($location_id->get_error_message());
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            ['guid' => home_url('/?post_type=location&p=' . (int) $location_id)],
            ['ID' => (int) $location_id],
            ['%s'],
            ['%d']
        );
        clean_post_cache((int) $location_id);

        return [
            'id' => (int) $location_id,
            'name' => $title,
            'created' => true,
        ];
    }

    /**
     * Build candidate country labels (fr/en + fallback map + country code).
     *
     * @param string $country_code
     * @return array
     */
    private function country_labels($country_code) {
        $country_code = strtoupper((string) $country_code);
        $labels = [];

        if (class_exists('Locale')) {
            $fr = \Locale::getDisplayRegion('-' . $country_code, 'fr');
            $en = \Locale::getDisplayRegion('-' . $country_code, 'en');
            if (is_string($fr) && $fr !== '') {
                $labels[] = $fr;
            }
            if (is_string($en) && $en !== '') {
                $labels[] = $en;
            }
        }

        $fallback = [
            'MA' => ['Maroc', 'Morocco'],
            'FR' => ['France'],
            'ES' => ['Espagne', 'Spain'],
            'IT' => ['Italie', 'Italy'],
            'PT' => ['Portugal'],
            'US' => ['United States', 'Etats-Unis', 'USA'],
            'GB' => ['United Kingdom', 'Royaume-Uni'],
        ];
        if (!empty($fallback[$country_code])) {
            foreach ($fallback[$country_code] as $label) {
                $labels[] = $label;
            }
        }

        $labels[] = $country_code;

        $clean = [];
        foreach ($labels as $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $clean[] = $label;
            }
        }

        $clean = array_values(array_unique($clean));
        if (empty($clean)) {
            return [$country_code];
        }

        return $clean;
    }

    /**
     * Normalize location title for matching.
     *
     * @param string $title
     * @return string
     */
    private function normalize_title($title) {
        $title = mb_strtolower(trim((string) $title), 'UTF-8');
        $title = remove_accents($title);
        $title = preg_replace('/\s+/', ' ', $title);
        return trim((string) $title);
    }

    /**
     * Write plugin logs to Woo logger when available, fallback to error_log.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, $context = []) {
        $line = '[AJTB Location] ' . $message;
        if (!empty($context)) {
            $line .= ' ' . wp_json_encode($context);
        }

        if (function_exists('wc_get_logger')) {
            wc_get_logger()->log($level, $line, ['source' => 'ajtb-location']);
            return;
        }

        error_log($line);
    }
}
