<?php
/**
 * Laravel Extras Repository
 *
 * Handles retrieval of extra tour data from Laravel tables
 * (aj_tour_days, aj_tour_inclusions, aj_tour_prices, etc.)
 *
 * @package AjinsafroBridge\Repositories
 */

namespace AjinsafroBridge\Repositories;

class LaravelExtrasRepository
{
    /**
     * WordPress database instance
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Laravel table prefix
     * @var string
     */
    private string $prefix;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Get Laravel prefix (default: aj_)
        $this->prefix = defined('AJBRIDGE_LARAVEL_PREFIX') 
            ? AJBRIDGE_LARAVEL_PREFIX 
            : 'aj_';
    }

    /**
     * Get table name with WordPress and Laravel prefixes
     *
     * @param string $table Table name without prefix
     * @return string Full table name (e.g., wp_aj_tour_days)
     */
    private function table(string $table): string
    {
        return $this->wpdb->prefix . $this->prefix . $table;
    }

    /**
     * Check if table exists
     *
     * @param string $tableName Full table name
     * @return bool
     */
    private function tableExists(string $tableName): bool
    {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $tableName
            )
        );

        return $result === $tableName;
    }

    /**
     * Get tour days/itinerary
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getDays(int $postId): array
    {
        $table = $this->table('tour_days');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE wp_post_id = %d 
                     ORDER BY day_number ASC",
                    $postId
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'day_number' => (int) $row['day_number'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'meals_included' => $this->jsonDecode($row['meals_included'] ?? '[]'),
                    'accommodation' => $row['accommodation'] ?? '',
                    'transport' => $row['transport'] ?? '',
                    'distance_km' => (float) ($row['distance_km'] ?? 0),
                    'duration_hours' => (float) ($row['duration_hours'] ?? 0),
                    'highlights' => $this->jsonDecode($row['highlights'] ?? '[]'),
                    'image_url' => $row['image_url'] ?? '',
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getDays', $e);
            return [];
        }
    }

    /**
     * Get tour inclusions
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getInclusions(int $postId): array
    {
        $table = $this->table('tour_inclusions');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE wp_post_id = %d 
                     AND type = 'inclusion'
                     ORDER BY sort_order ASC",
                    $postId
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'icon' => $row['icon'] ?? 'check',
                    'category' => $row['category'] ?? 'general',
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getInclusions', $e);
            return [];
        }
    }

    /**
     * Get tour exclusions
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getExclusions(int $postId): array
    {
        $table = $this->table('tour_inclusions');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE wp_post_id = %d 
                     AND type = 'exclusion'
                     ORDER BY sort_order ASC",
                    $postId
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'icon' => $row['icon'] ?? 'times',
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getExclusions', $e);
            return [];
        }
    }

    /**
     * Get tour prices (seasonal pricing)
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getPrices(int $postId): array
    {
        $table = $this->table('tour_prices');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE wp_post_id = %d 
                     ORDER BY start_date ASC",
                    $postId
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'season_name' => $row['season_name'] ?? '',
                    'start_date' => $row['start_date'] ?? '',
                    'end_date' => $row['end_date'] ?? '',
                    'adult_price' => (float) ($row['adult_price'] ?? 0),
                    'child_price' => (float) ($row['child_price'] ?? 0),
                    'infant_price' => (float) ($row['infant_price'] ?? 0),
                    'single_supplement' => (float) ($row['single_supplement'] ?? 0),
                    'group_discount' => (float) ($row['group_discount'] ?? 0),
                    'min_group_size' => (int) ($row['min_group_size'] ?? 0),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getPrices', $e);
            return [];
        }
    }

    /**
     * Get tour badges
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getBadges(int $postId): array
    {
        $table = $this->table('tour_badges');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} 
                     WHERE wp_post_id = %d 
                     AND is_active = 1
                     ORDER BY sort_order ASC",
                    $postId
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'label' => $row['label'] ?? '',
                    'color' => $row['color'] ?? '#007bff',
                    'bg_color' => $row['bg_color'] ?? '#e9ecef',
                    'icon' => $row['icon'] ?? '',
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getBadges', $e);
            return [];
        }
    }

    /**
     * Get tour departure dates
     *
     * @param int $postId WordPress post ID
     * @param bool $futureOnly Only return future dates
     * @return array
     */
    public function getDepartureDates(int $postId, bool $futureOnly = true): array
    {
        $table = $this->table('tour_departures');

        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $sql = "SELECT * FROM {$table} WHERE wp_post_id = %d";

            if ($futureOnly) {
                $sql .= " AND departure_date >= CURDATE()";
            }

            $sql .= " ORDER BY departure_date ASC";

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $postId),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'departure_date' => $row['departure_date'] ?? '',
                    'return_date' => $row['return_date'] ?? '',
                    'available_spots' => (int) ($row['available_spots'] ?? 0),
                    'max_spots' => (int) ($row['max_spots'] ?? 0),
                    'price_modifier' => (float) ($row['price_modifier'] ?? 0),
                    'status' => $row['status'] ?? 'available',
                    'notes' => $row['notes'] ?? '',
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logError('getDepartureDates', $e);
            return [];
        }
    }

    /**
     * Get all Laravel extras for a tour
     *
     * @param int $postId WordPress post ID
     * @return array
     */
    public function getAllExtras(int $postId): array
    {
        return [
            'days' => $this->getDays($postId),
            'inclusions' => $this->getInclusions($postId),
            'exclusions' => $this->getExclusions($postId),
            'prices' => $this->getPrices($postId),
            'badges' => $this->getBadges($postId),
            'departures' => $this->getDepartureDates($postId),
        ];
    }

    /**
     * Check if Laravel extras exist for a tour
     *
     * @param int $postId
     * @return bool
     */
    public function hasExtras(int $postId): bool
    {
        $days = $this->getDays($postId);
        $inclusions = $this->getInclusions($postId);
        $prices = $this->getPrices($postId);

        return !empty($days) || !empty($inclusions) || !empty($prices);
    }

    /**
     * Safely decode JSON
     *
     * @param string $json
     * @return array
     */
    private function jsonDecode(string $json): array
    {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Log error for debugging
     *
     * @param string $method
     * @param \Exception $e
     * @return void
     */
    private function logError(string $method, \Exception $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Ajinsafro Bridge [%s]: %s in %s on line %d',
                $method,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }
}
