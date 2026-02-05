<?php
/**
 * Tour Assembler Service
 *
 * Combines WordPress tour data with Laravel extras into
 * a unified $tourData array for use in templates.
 *
 * @package AjinsafroBridge\Services
 */

namespace AjinsafroBridge\Services;

use AjinsafroBridge\Repositories\TourRepository;
use AjinsafroBridge\Repositories\LaravelExtrasRepository;

class TourAssembler
{
    /**
     * WordPress tour repository
     * @var TourRepository
     */
    private TourRepository $tourRepo;

    /**
     * Laravel extras repository
     * @var LaravelExtrasRepository
     */
    private LaravelExtrasRepository $laravelRepo;

    /**
     * Cache for assembled tour data
     * @var array
     */
    private array $cache = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tourRepo = new TourRepository();
        $this->laravelRepo = new LaravelExtrasRepository();
    }

    /**
     * Get fully assembled tour data
     *
     * @param int $postId WordPress post ID
     * @return array|null Assembled tour data or null if not found
     */
    public function getTourData(int $postId): ?array
    {
        // Check cache first
        if (isset($this->cache[$postId])) {
            return $this->cache[$postId];
        }

        // Get WordPress tour data
        $wpData = $this->tourRepo->getById($postId);

        if (!$wpData) {
            return null;
        }

        // Get Laravel extras
        $laravelData = $this->laravelRepo->getAllExtras($postId);

        // Assemble final data
        $tourData = $this->assemble($wpData, $laravelData);

        // Cache result
        $this->cache[$postId] = $tourData;

        return $tourData;
    }

    /**
     * Assemble WordPress and Laravel data
     *
     * @param array $wpData WordPress tour data
     * @param array $laravelData Laravel extras data
     * @return array Assembled data
     */
    private function assemble(array $wpData, array $laravelData): array
    {
        // Determine itinerary source (Laravel days take precedence)
        $itinerary = $this->resolveItinerary($wpData, $laravelData);

        // Determine inclusions/exclusions source
        $inclusions = $this->resolveInclusions($wpData, $laravelData);
        $exclusions = $this->resolveExclusions($wpData, $laravelData);

        // Determine pricing (Laravel seasonal prices supplement WP base price)
        $pricing = $this->resolvePricing($wpData, $laravelData);

        return [
            // WP data section
            'wp' => [
                'id' => $wpData['id'],
                'title' => $wpData['title'],
                'content' => $wpData['content'],
                'excerpt' => $wpData['excerpt'],
                'permalink' => $wpData['permalink'],
                'slug' => $wpData['slug'],
                'featured_image' => $wpData['featured_image'],
                'gallery' => $wpData['gallery'],
                'address' => $wpData['address'],
                'location_id' => $wpData['location_id'],
                'map' => [
                    'lat' => $wpData['map_lat'],
                    'lng' => $wpData['map_lng'],
                    'zoom' => $wpData['map_zoom'],
                ],
                'duration_day' => $wpData['duration_day'],
                'duration' => $wpData['duration'],
                'max_people' => $wpData['max_people'],
                'min_people' => $wpData['min_people'],
                'type_tour' => $wpData['type_tour'],
                'rate_review' => $wpData['rate_review'],
                'review_score' => $wpData['review_score'],
                'is_featured' => $wpData['is_featured'],
                'external_booking_link' => $wpData['external_booking_link'],
                'video' => $wpData['video'],
                'cancellation_policy' => $wpData['cancellation_policy'],
                'faqs' => $wpData['faqs'],
                'categories' => $wpData['categories'],
                'tour_types' => $wpData['tour_types'],
                'tags' => $wpData['tags'],
            ],

            // Laravel data section
            'laravel' => [
                'days' => $laravelData['days'] ?? [],
                'inclusions' => $laravelData['inclusions'] ?? [],
                'exclusions' => $laravelData['exclusions'] ?? [],
                'prices' => $laravelData['prices'] ?? [],
                'badges' => $laravelData['badges'] ?? [],
                'departures' => $laravelData['departures'] ?? [],
                'has_extras' => $this->laravelRepo->hasExtras($wpData['id']),
            ],

            // Resolved/merged data for templates
            'itinerary' => $itinerary,
            'inclusions' => $inclusions,
            'exclusions' => $exclusions,
            'pricing' => $pricing,

            // Computed helpers
            'has_gallery' => !empty($wpData['gallery']),
            'has_itinerary' => !empty($itinerary),
            'has_inclusions' => !empty($inclusions),
            'has_exclusions' => !empty($exclusions),
            'has_seasonal_pricing' => !empty($laravelData['prices']),
            'has_departures' => !empty($laravelData['departures']),
            'has_badges' => !empty($laravelData['badges']),
            'has_video' => !empty($wpData['video']),
            'has_faqs' => !empty($wpData['faqs']),
            'has_map' => !empty($wpData['map_lat']) && !empty($wpData['map_lng']),

            // Source tracking (for debugging)
            '_sources' => [
                'itinerary' => !empty($laravelData['days']) ? 'laravel' : 'wordpress',
                'inclusions' => !empty($laravelData['inclusions']) ? 'laravel' : 'wordpress',
                'exclusions' => !empty($laravelData['exclusions']) ? 'laravel' : 'wordpress',
            ],
        ];
    }

    /**
     * Resolve itinerary from Laravel days or WP tours_program
     *
     * @param array $wpData
     * @param array $laravelData
     * @return array
     */
    private function resolveItinerary(array $wpData, array $laravelData): array
    {
        // Prefer Laravel days if available
        if (!empty($laravelData['days'])) {
            return $laravelData['days'];
        }

        // Fall back to WP tours_program
        $program = $wpData['tours_program'] ?? [];

        if (empty($program)) {
            return [];
        }

        // Normalize WP program format to match Laravel format
        if (is_array($program)) {
            return array_map(function ($item, $index) {
                // Handle both associative and indexed arrays
                if (isset($item['title'])) {
                    return [
                        'id' => $index + 1,
                        'day_number' => $index + 1,
                        'title' => $item['title'] ?? '',
                        'description' => $item['content'] ?? $item['desc'] ?? '',
                        'meals_included' => [],
                        'accommodation' => '',
                        'transport' => '',
                        'distance_km' => 0,
                        'duration_hours' => 0,
                        'highlights' => [],
                        'image_url' => $item['image'] ?? '',
                    ];
                }

                // Simple string items
                return [
                    'id' => $index + 1,
                    'day_number' => $index + 1,
                    'title' => is_string($item) ? $item : 'Jour ' . ($index + 1),
                    'description' => '',
                    'meals_included' => [],
                    'accommodation' => '',
                    'transport' => '',
                    'distance_km' => 0,
                    'duration_hours' => 0,
                    'highlights' => [],
                    'image_url' => '',
                ];
            }, $program, array_keys($program));
        }

        return [];
    }

    /**
     * Resolve inclusions from Laravel or WP
     *
     * @param array $wpData
     * @param array $laravelData
     * @return array
     */
    private function resolveInclusions(array $wpData, array $laravelData): array
    {
        // Prefer Laravel inclusions if available
        if (!empty($laravelData['inclusions'])) {
            return $laravelData['inclusions'];
        }

        // Fall back to WP included field
        $included = $wpData['included'] ?? '';

        if (empty($included)) {
            return [];
        }

        // Parse WP included (usually HTML list or newline-separated)
        return $this->parseListToArray($included, 'inclusion');
    }

    /**
     * Resolve exclusions from Laravel or WP
     *
     * @param array $wpData
     * @param array $laravelData
     * @return array
     */
    private function resolveExclusions(array $wpData, array $laravelData): array
    {
        // Prefer Laravel exclusions if available
        if (!empty($laravelData['exclusions'])) {
            return $laravelData['exclusions'];
        }

        // Fall back to WP excluded field
        $excluded = $wpData['excluded'] ?? '';

        if (empty($excluded)) {
            return [];
        }

        // Parse WP excluded
        return $this->parseListToArray($excluded, 'exclusion');
    }

    /**
     * Resolve pricing information
     *
     * @param array $wpData
     * @param array $laravelData
     * @return array
     */
    private function resolvePricing(array $wpData, array $laravelData): array
    {
        // Base pricing from WordPress
        $pricing = [
            'base' => [
                'adult' => $wpData['adult_price'] ?? $wpData['base_price'] ?? 0,
                'child' => $wpData['child_price'] ?? 0,
                'infant' => $wpData['infant_price'] ?? 0,
                'has_discount' => $wpData['has_discount'] ?? false,
                'discount' => $wpData['discount'] ?? 0,
                'sale_price' => $wpData['sale_price'] ?? 0,
            ],
            'seasonal' => [],
            'current_season' => null,
        ];

        // Add Laravel seasonal pricing if available
        if (!empty($laravelData['prices'])) {
            $pricing['seasonal'] = $laravelData['prices'];

            // Determine current season
            $today = date('Y-m-d');
            foreach ($laravelData['prices'] as $season) {
                if (
                    isset($season['start_date']) &&
                    isset($season['end_date']) &&
                    $today >= $season['start_date'] &&
                    $today <= $season['end_date'] &&
                    ($season['is_active'] ?? true)
                ) {
                    $pricing['current_season'] = $season;
                    break;
                }
            }
        }

        // Compute display price (use seasonal if current, otherwise base)
        if ($pricing['current_season']) {
            $pricing['display'] = [
                'adult' => $pricing['current_season']['adult_price'],
                'child' => $pricing['current_season']['child_price'],
                'infant' => $pricing['current_season']['infant_price'],
                'source' => 'seasonal',
                'season_name' => $pricing['current_season']['season_name'] ?? '',
            ];
        } else {
            $displayAdult = $pricing['base']['has_discount'] && $pricing['base']['sale_price'] > 0
                ? $pricing['base']['sale_price']
                : $pricing['base']['adult'];

            $pricing['display'] = [
                'adult' => $displayAdult,
                'child' => $pricing['base']['child'],
                'infant' => $pricing['base']['infant'],
                'source' => 'base',
                'season_name' => '',
            ];
        }

        // Currency
        $pricing['currency'] = get_option('st_currency', 'MAD');
        $pricing['currency_symbol'] = $this->getCurrencySymbol($pricing['currency']);

        return $pricing;
    }

    /**
     * Parse HTML list or newline text to array
     *
     * @param string $content
     * @param string $type 'inclusion' or 'exclusion'
     * @return array
     */
    private function parseListToArray(string $content, string $type = 'inclusion'): array
    {
        $items = [];

        // Remove common HTML wrappers
        $content = strip_tags($content, '<li>');

        // Check for <li> items
        if (strpos($content, '<li>') !== false) {
            preg_match_all('/<li>(.*?)<\/li>/si', $content, $matches);
            if (!empty($matches[1])) {
                $items = array_map('trim', $matches[1]);
            }
        } else {
            // Split by newlines or <br>
            $content = str_replace(['<br>', '<br/>', '<br />'], "\n", $content);
            $items = array_filter(array_map('trim', explode("\n", $content)));
        }

        // Convert to structured format
        $icon = $type === 'inclusion' ? 'check' : 'times';

        return array_values(array_map(function ($item, $index) use ($icon) {
            return [
                'id' => $index + 1,
                'title' => strip_tags($item),
                'description' => '',
                'icon' => $icon,
            ];
        }, $items, array_keys($items)));
    }

    /**
     * Get currency symbol
     *
     * @param string $currency
     * @return string
     */
    private function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'MAD' => 'DH',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];

        return $symbols[$currency] ?? $currency;
    }

    /**
     * Get tour data for archive list
     *
     * @param array $args Query arguments
     * @return array
     */
    public function getArchiveData(array $args = []): array
    {
        $result = $this->tourRepo->getArchive($args);

        // Enrich each tour with minimal Laravel data
        $result['tours'] = array_map(function ($tour) {
            // Only get badges for archive view (lightweight)
            $badges = $this->laravelRepo->getBadges($tour['id']);

            return [
                'wp' => $tour,
                'laravel' => [
                    'badges' => $badges,
                ],
                'has_badges' => !empty($badges),
            ];
        }, $result['tours']);

        return $result;
    }

    /**
     * Clear cache
     *
     * @param int|null $postId Specific post ID or null for all
     * @return void
     */
    public function clearCache(?int $postId = null): void
    {
        if ($postId) {
            unset($this->cache[$postId]);
        } else {
            $this->cache = [];
        }
    }
}
