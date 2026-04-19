<?php
/**
 * V1 Data Provider
 *
 * Bridges real tour data (WP + Laravel CRUD) into normalized payload
 * for the V1 single template, with premium-safe fallbacks.
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJTB_V1_Data_Provider
{
    /**
     * Build normalized V1 data.
     *
     * @param int $tour_id WP post ID.
     * @return array
     */
    public static function build(int $tour_id): array
    {
        $tour_id = (int) $tour_id;
        $wp_data = [];
        $laravel_days = [];
        $sections = [];
        $inclusions = [];
        $exclusions = [];
        $overview = '';
        $pricing = null;
        $flights = [];
        $departure_places = [];
        $departure_dates = [];
        $session_token = '';

        if (class_exists('AJTB_Tour_Repository')) {
            $wp_repo = new AJTB_Tour_Repository($tour_id);
            $wp_data = $wp_repo->get_tour_data();
            if (!is_array($wp_data)) {
                $wp_data = [];
            }
        }

        $open_activities = [];
        if (class_exists('AJTB_Laravel_Repository')) {
            $laravel_repo = new AJTB_Laravel_Repository($tour_id);
            if (class_exists('AJTB_Activity_Selections')) {
                $session_token = (string) (new AJTB_Activity_Selections())->get_session_token();
            }
            $laravel_days = $laravel_repo->get_days($session_token !== '' ? $session_token : null);
            $sections = $laravel_repo->get_sections();
            $inclusions = $laravel_repo->get_inclusions();
            $exclusions = $laravel_repo->get_exclusions();
            $overview = (string) $laravel_repo->get_overview();
            $pricing = $laravel_repo->get_current_pricing();
            $flights = $laravel_repo->get_flights();
            $departure_places = $laravel_repo->get_departure_places(true);
            $departure_dates = $laravel_repo->get_departure_dates(true);
            $open_activities = $laravel_repo->get_open_optional_activities();
        }

        $title = trim((string) get_the_title($tour_id));
        if ($title === '') {
            $title = __('Voyage Ajinsafro', 'ajinsafro-tour-bridge');
        }

        $destination = self::resolve_destination($wp_data, $flights, $title);
        $hero_images = self::resolve_images($wp_data);
        $dates_and_places = self::resolve_departures($departure_places, $departure_dates);

        $duration_days = self::resolve_duration_days($wp_data, $laravel_days);
        $duration_label = $duration_days > 0
            ? sprintf('%d jours / %d nuits', $duration_days, max(0, $duration_days - 1))
            : '5 jours / 4 nuits';

        $price_data = self::resolve_price($wp_data, $pricing, $dates_and_places['date_prices']);
        $rating_label = self::resolve_rating_label($wp_data);
        $guests_label = self::resolve_guests_label($wp_data);
        $overview_points = self::resolve_overview_points($overview, $wp_data);
        $policy_items = self::resolve_policy_items($sections, $wp_data, $exclusions);
        $coupon_items = self::resolve_coupons($sections);

        $normalized_days = self::normalize_days(
            $laravel_days,
            $wp_data,
            $hero_images['gallery_pool'],
            $dates_and_places['first_date']
        );

        $inclusions = self::normalize_list_items($inclusions);
        if (empty($inclusions) && !empty($wp_data['included']) && is_array($wp_data['included'])) {
            $inclusions = self::normalize_list_items($wp_data['included']);
        }
        $exclusions = self::normalize_list_items($exclusions);
        if (empty($exclusions) && !empty($wp_data['excluded']) && is_array($wp_data['excluded'])) {
            $exclusions = self::normalize_list_items($wp_data['excluded']);
        }

        $stats = self::build_stats($normalized_days);
        $group_size = isset($wp_data['max_people']) ? (int) $wp_data['max_people'] : 0;
        if ($group_size <= 0) {
            $group_size = 12;
        }
        $best_deals = self::resolve_best_deals($wp_data, $overview_points, $inclusions);

        return [
            'id' => $tour_id,
            'title' => $title,
            'destination' => $destination,
            'duration_label' => $duration_label,
            'group_size' => $group_size,
            'rating' => $rating_label,
            'hero' => [
                'main' => $hero_images['main'],
                'side' => $hero_images['side'],
                'all' => $hero_images['all'],
            ],
            'search' => [
                'departure_place' => $dates_and_places['first_place'],
                'departure_place_id' => $dates_and_places['first_place_id'],
                'departure_date' => $dates_and_places['first_date_label'] ?: 'Date a confirmer',
                'guests' => $guests_label,
                'guest_config' => [
                    'adults' => 2,
                    'children' => 0,
                    'max_adults' => max(1, $group_size),
                    'max_children' => 8,
                    'max_total' => max(1, $group_size),
                ],
                'places' => $dates_and_places['places'],
                'place_options' => $dates_and_places['place_options'],
                'dates' => $dates_and_places['dates'],
                'date_options' => $dates_and_places['date_options'],
                'date_prices' => $dates_and_places['date_prices'],
            ],
            'pricing' => $price_data,
            'overview_points' => $overview_points,
            'policies' => $policy_items,
            'inclusions' => $inclusions,
            'exclusions' => $exclusions,
            'days' => $normalized_days,
            'open_activities' => $open_activities,
            'stats' => $stats,
            'summary_rows' => self::build_summary_rows($normalized_days),
            'coupons' => $coupon_items,
            'best_deals' => $best_deals,
            'session_token' => $session_token,
        ];
    }

    private static function resolve_destination(array $wp_data, array $flights, string $title): string
    {
        if (!empty($wp_data['locations']) && is_array($wp_data['locations'])) {
            foreach ($wp_data['locations'] as $loc) {
                if (!empty($loc['city'])) {
                    return trim((string) $loc['city']);
                }
                if (!empty($loc['name'])) {
                    return trim((string) $loc['name']);
                }
            }
        }

        foreach ($flights as $flight) {
            if (!empty($flight['to_city'])) {
                return trim((string) $flight['to_city']);
            }
        }

        if (preg_match('/\b([A-Za-z]{4,})\b/u', $title, $m)) {
            return trim((string) $m[1]);
        }

        return 'Destination';
    }

    private static function resolve_images(array $wp_data): array
    {
        $fallback_main = AJTB_PLUGIN_URL . 'assets/images/tour-v1/hero-main.svg';
        $images = [];
        if (!empty($wp_data['hero_image_url'])) {
            $images[] = (string) $wp_data['hero_image_url'];
        }
        if (!empty($wp_data['featured_image']['url'])) {
            $images[] = (string) $wp_data['featured_image']['url'];
        }

        if (!empty($wp_data['hero_gallery']) && is_array($wp_data['hero_gallery'])) {
            foreach ($wp_data['hero_gallery'] as $img) {
                if (!empty($img['url'])) {
                    $images[] = (string) $img['url'];
                }
            }
        }
        if (!empty($wp_data['gallery']) && is_array($wp_data['gallery'])) {
            foreach ($wp_data['gallery'] as $img) {
                if (!empty($img['url'])) {
                    $images[] = (string) $img['url'];
                }
            }
        }

        $images = self::unique_non_empty($images);
        if (empty($images)) {
            $images = [$fallback_main];
        }

        $hero_main = $images[0];
        $side = array_slice($images, 1);

        return [
            'main' => $hero_main,
            'side' => $side,
            'all' => $images,
            'gallery_pool' => $images,
        ];
    }

    private static function resolve_departures(array $departure_places = [], array $departure_dates = []): array
    {
        $places = [];
        $place_options = [];
        $raw_dates = [];
        $date_options_by_value = [];
        $date_prices = [];

        foreach ($departure_places as $place_row) {
            if (!is_array($place_row)) {
                continue;
            }

            $name = isset($place_row['name']) ? trim((string) $place_row['name']) : '';
            if ($name === '') {
                continue;
            }

            $place_id = isset($place_row['id']) ? (int) $place_row['id'] : 0;
            $code = isset($place_row['code']) ? trim((string) $place_row['code']) : '';
            $places[] = $name;
            $place_options[] = [
                'id' => $place_id,
                'name' => $name,
                'code' => $code,
                'value' => (string) $place_id,
            ];
        }

        foreach ($departure_dates as $departure_row) {
            if (!is_array($departure_row)) {
                continue;
            }

            $raw_date = isset($departure_row['date']) ? trim((string) $departure_row['date']) : '';
            if ($raw_date === '') {
                continue;
            }

            $stock = null;
            if (array_key_exists('stock', $departure_row) && $departure_row['stock'] !== null && $departure_row['stock'] !== '') {
                $stock = (int) $departure_row['stock'];
            }

            $specific_price = null;
            if (array_key_exists('specific_price', $departure_row) && $departure_row['specific_price'] !== null && $departure_row['specific_price'] !== '') {
                $specific_price = (float) $departure_row['specific_price'];
            }

            $departure_place_id = null;
            if (array_key_exists('departure_place_id', $departure_row) && $departure_row['departure_place_id'] !== null && $departure_row['departure_place_id'] !== '') {
                $departure_place_id = (int) $departure_row['departure_place_id'];
            }

            $raw_dates[] = $raw_date;
            $date_options_by_value[$raw_date] = self::build_departure_date_option($raw_date, $stock, $specific_price);
            $date_prices[$raw_date] = [
                'specific_price' => $specific_price,
                'departure_place_id' => $departure_place_id,
            ];
        }

        $places = self::unique_non_empty($places);
        $raw_dates = self::unique_non_empty($raw_dates);
        sort($raw_dates);

        ksort($date_options_by_value);
        $first_date = !empty($raw_dates[0]) ? $raw_dates[0] : '';
        $first_place = !empty($place_options) ? (string) $place_options[0]['name'] : '';
        $first_place_id = !empty($place_options) ? (int) $place_options[0]['id'] : 0;

        return [
            'places' => $places,
            'place_options' => $place_options,
            'dates' => array_map([self::class, 'format_date_label'], $raw_dates),
            'date_options' => array_values($date_options_by_value),
            'date_prices' => $date_prices,
            'first_place' => $first_place,
            'first_place_id' => $first_place_id,
            'first_date' => $first_date,
            'first_date_label' => $first_date !== '' ? self::format_date_label($first_date) : '',
        ];
    }

    private static function build_departure_date_option(string $raw_date, ?int $stock, ?float $specific_price): array
    {
        $label = self::format_date_label($raw_date);
        $parts = [$label];

        if ($stock !== null) {
            $parts[] = sprintf('%d place%s', $stock, $stock > 1 ? 's' : '');
        }

        if ($specific_price !== null && $specific_price > 0) {
            $parts[] = number_format($specific_price, 0, ',', ' ') . ' MAD';
        }

        return [
            'value' => $raw_date,
            'label' => $label,
            'display' => implode(' - ', $parts),
            'stock' => $stock,
            'specific_price' => $specific_price,
        ];
    }

    private static function resolve_duration_days(array $wp_data, array $laravel_days): int
    {
        $days_count = count($laravel_days);
        if ($days_count > 0) {
            return $days_count;
        }
        $meta_duration = isset($wp_data['duration_day']) ? (int) $wp_data['duration_day'] : 0;
        if ($meta_duration > 0) {
            return $meta_duration;
        }
        if (!empty($wp_data['wp_program']['items']) && is_array($wp_data['wp_program']['items'])) {
            return count($wp_data['wp_program']['items']);
        }
        return 5;
    }

    private static function resolve_price(array $wp_data, ?array $pricing, array $date_prices = []): array
    {
        $adult_price = 0.0;
        $child_price = 0.0;
        $season_name = '';
        $period_label = '';
        if (is_array($pricing) && isset($pricing['adult_price'])) {
            $adult_price = (float) $pricing['adult_price'];
            $child_price = isset($pricing['child_price']) ? (float) $pricing['child_price'] : 0.0;
            $season_name = !empty($pricing['season_name']) ? trim((string) $pricing['season_name']) : '';
            if (!empty($pricing['start_date']) || !empty($pricing['end_date'])) {
                $start = !empty($pricing['start_date']) ? self::format_date_label((string) $pricing['start_date']) : '';
                $end = !empty($pricing['end_date']) ? self::format_date_label((string) $pricing['end_date']) : '';
                $period_label = trim($start . ($start !== '' && $end !== '' ? ' - ' : '') . $end);
            }
        }
        if ($adult_price <= 0 && !empty($wp_data['pricing']['display_price'])) {
            $adult_price = (float) $wp_data['pricing']['display_price'];
        }
        if ($adult_price <= 0) {
            $adult_price = 12900.0;
        }
        if ($child_price < 0) {
            $child_price = 0.0;
        }

        $default_amount = $adult_price;
        foreach ($date_prices as $date_info) {
            if (!is_array($date_info)) {
                continue;
            }
            if (isset($date_info['specific_price']) && $date_info['specific_price'] !== null && (float) $date_info['specific_price'] > 0) {
                $default_amount = (float) $date_info['specific_price'];
                break;
            }
        }

        $currency_symbol = !empty($wp_data['pricing']['currency_symbol'])
            ? (string) $wp_data['pricing']['currency_symbol']
            : ajtb_get_currency_symbol();
        $note = 'Tarif par adulte, hors options complementaires.';
        if ($season_name !== '') {
            $note = 'Tarif ' . $season_name . '.';
        }
        if ($period_label !== '') {
            $note .= ' Periode: ' . $period_label . '.';
        }

        return [
            'amount' => $default_amount,
            'display_amount' => number_format($default_amount, 0, ',', ' '),
            'currency_symbol' => $currency_symbol,
            'adult_price' => $adult_price,
            'child_price' => $child_price,
            'season_name' => $season_name,
            'period_label' => $period_label,
            'note' => $note,
        ];
    }

    private static function resolve_overview_points(string $overview, array $wp_data): array
    {
        $points = [];
        $raw = trim(strip_tags($overview));
        if ($raw !== '') {
            $split = preg_split('/[\.\n\r]+/', $raw);
            if (is_array($split)) {
                foreach ($split as $line) {
                    $line = trim($line);
                    if (strlen($line) >= 18) {
                        $points[] = $line . '.';
                    }
                    if (count($points) >= 4) {
                        break;
                    }
                }
            }
        }

        if (empty($points) && !empty($wp_data['excerpt'])) {
            $points[] = wp_strip_all_tags((string) $wp_data['excerpt']);
        }

        if (empty($points)) {
            $points = [
                'Programme accompagne avec support Ajinsafro a chaque etape du voyage.',
                'Selection des prestations selon la disponibilite confirmee.',
                'Experience optimisee pour un sejour fluide et confortable.',
            ];
        }

        return $points;
    }

    private static function resolve_rating_label(array $wp_data): string
    {
        $score = 0.0;
        if (!empty($wp_data['rating'])) {
            $score = (float) $wp_data['rating'];
        }
        if ($score <= 0 && !empty($wp_data['review_score'])) {
            $score = (float) $wp_data['review_score'];
        }
        if ($score > 0) {
            return number_format($score, 1, ',', ' ') . ' / 5 voyageurs';
        }
        return 'Experience Ajinsafro';
    }

    private static function resolve_guests_label(array $wp_data): string
    {
        $min = isset($wp_data['min_people']) ? (int) $wp_data['min_people'] : 0;
        $max = isset($wp_data['max_people']) ? (int) $wp_data['max_people'] : 0;

        if ($min > 0 && $max > 0 && $max >= $min) {
            return sprintf('%d a %d voyageurs', $min, $max);
        }
        if ($max > 0) {
            return sprintf('Jusqu a %d voyageurs', $max);
        }
        if ($min > 0) {
            return sprintf('A partir de %d voyageurs', $min);
        }
        return 'Voyageurs flexibles';
    }

    private static function resolve_policy_items(array $sections, array $wp_data, array $exclusions): array
    {
        $items = [];
        $section_keys = [
            'policies',
            'policy',
            'conditions',
            'terms',
            'cancellation_policy',
            'payment_policy',
        ];

        foreach ($section_keys as $key) {
            if (!empty($sections[$key]['content'])) {
                $items = array_merge($items, self::extract_lines((string) $sections[$key]['content'], 4));
            }
        }

        if (!empty($wp_data['cancellation_policy'])) {
            $items = array_merge($items, self::extract_lines((string) $wp_data['cancellation_policy'], 3));
        }

        if (empty($items) && !empty($exclusions)) {
            $items = array_slice(self::normalize_list_items($exclusions), 0, 4);
        }

        if (empty($items)) {
            $items = [
                'Confirmation sous reserve de disponibilite finale des prestations.',
                'Conditions d annulation et de modification communiquees avant paiement.',
                'L ordre des activites peut s ajuster selon les operations locales.',
            ];
        }

        return array_slice(self::unique_non_empty($items), 0, 6);
    }

    private static function resolve_coupons(array $sections): array
    {
        $candidates = ['coupons', 'offers', 'promotions', 'promo', 'deals'];
        $rows = [];
        foreach ($candidates as $key) {
            if (empty($sections[$key]['content'])) {
                continue;
            }
            $lines = self::extract_lines((string) $sections[$key]['content'], 12);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $code = '';
                $text = $line;
                $value = '';

                if (preg_match('/^([A-Z0-9_-]{4,})\s*[:\-]\s*(.+)$/', $line, $m)) {
                    $code = trim((string) $m[1]);
                    $text = trim((string) $m[2]);
                }
                if ($code === '') {
                    $parts = preg_split('/\s+/', $line);
                    if (is_array($parts) && !empty($parts[0]) && preg_match('/^[A-Z0-9_-]{4,}$/', $parts[0])) {
                        $code = trim((string) $parts[0]);
                        $text = trim(substr($line, strlen($parts[0])));
                    }
                }
                if (preg_match('/(-?\d[\d\s.,]*\s?(MAD|DHS|DH|%|€|\$))/i', $line, $v)) {
                    $value = trim((string) $v[1]);
                }

                $rows[] = [
                    'code' => $code !== '' ? $code : 'OFFRE',
                    'text' => $text !== '' ? $text : 'Offre disponible',
                    'value' => $value,
                ];
                if (count($rows) >= 3) {
                    break 2;
                }
            }
        }
        return $rows;
    }

    private static function resolve_best_deals(array $wp_data, array $overview_points, array $inclusions): array
    {
        $items = [];

        if (!empty($wp_data['highlights']) && is_array($wp_data['highlights'])) {
            $items = array_merge($items, self::normalize_list_items($wp_data['highlights']));
        }
        $items = array_merge($items, array_slice($inclusions, 0, 6));
        $items = array_merge($items, array_slice($overview_points, 0, 6));

        $items = self::unique_non_empty($items);
        if (empty($items)) {
            $items = [
                'Accompagnement Ajinsafro avant et pendant le voyage.',
                'Programme equilibre entre confort et decouvertes.',
            ];
        }
        return array_slice($items, 0, 4);
    }

    private static function normalize_days(array $laravel_days, array $wp_data, array $gallery_pool, string $first_date): array
    {
        $days = [];

        if (!empty($laravel_days)) {
            foreach ($laravel_days as $row) {
                $day_number = isset($row['day']) ? (int) $row['day'] : (count($days) + 1);
                if ($day_number < 1) {
                    $day_number = count($days) + 1;
                }

                $title = trim((string) ($row['title'] ?? $row['day_title'] ?? ''));
                if ($title === '') {
                    $title = 'Day ' . $day_number;
                }

                $description = self::normalize_rich_text((string) ($row['content_html'] ?? ''));
                if ($description === '') {
                    $description = trim((string) ($row['description'] ?? ''));
                }
                if ($description === '') {
                    $description = 'Programme detaille communique avant le depart.';
                }

                $activities = is_array($row['activities'] ?? null) ? $row['activities'] : [];
                $transfers_in = is_array($row['transfer'] ?? null) ? $row['transfer'] : [];
                $transfers_out = is_array($row['transfer_return'] ?? null) ? $row['transfer_return'] : [];
                $flights_out = is_array($row['flight'] ?? null) ? $row['flight'] : [];
                $flights_in = is_array($row['flight_return'] ?? null) ? $row['flight_return'] : [];
                $hotels = is_array($row['hotels'] ?? null) ? $row['hotels'] : [];
                $hotel = !empty($row['hotel']) && is_array($row['hotel']) ? $row['hotel'] : (!empty($hotels[0]) ? $hotels[0] : null);

                $gallery = self::day_gallery(
                    (string) ($row['image'] ?? ''),
                    $activities,
                    $hotel,
                    $transfers_in,
                    $gallery_pool
                );

                $meals = self::normalize_meals($row['meals'] ?? '');
                $date_label = self::day_date_label($first_date, $day_number);

                $days[] = [
                    'day_id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'day' => $day_number,
                    'date_label' => $date_label,
                    'title' => $title,
                    'description' => $description,
                    'meals' => $meals,
                    'gallery' => $gallery,
                    'activities' => $activities,
                    'flights_out' => $flights_out,
                    'flights_in' => $flights_in,
                    'transfers_in' => $transfers_in,
                    'transfers_out' => $transfers_out,
                    'hotel' => $hotel,
                    'notes' => trim((string) ($row['notes'] ?? '')),
                ];
            }
        }

        if (!empty($days)) {
            usort($days, static function ($a, $b) {
                return (int) $a['day'] <=> (int) $b['day'];
            });
            return array_values($days);
        }

        $wp_program_items = [];
        if (!empty($wp_data['wp_program']['items']) && is_array($wp_data['wp_program']['items'])) {
            $wp_program_items = $wp_data['wp_program']['items'];
        }
        if (empty($wp_program_items)) {
            return [];
        }

        $fallbacks = [
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-1.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-2.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-3.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-4.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-5.svg',
        ];

        $i = 0;
        foreach ($wp_program_items as $item) {
            $i++;
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                $title = 'Day ' . $i;
            }
            $desc = trim((string) ($item['desc'] ?? ''));
            if ($desc === '') {
                $desc = 'Programme detaille communique avant le depart.';
            }
            $img = !empty($gallery_pool[$i - 1]) ? $gallery_pool[$i - 1] : $fallbacks[min($i - 1, count($fallbacks) - 1)];
            $days[] = [
                'day_id' => 0,
                'day' => $i,
                'date_label' => self::day_date_label($first_date, $i),
                'title' => $title,
                'description' => $desc,
                'meals' => [],
                'gallery' => [$img],
                'activities' => [],
                'flights_out' => [],
                'flights_in' => [],
                'transfers_in' => [],
                'transfers_out' => [],
                'hotel' => null,
                'notes' => '',
            ];
        }

        return $days;
    }

    private static function day_gallery(string $day_image, array $activities, ?array $hotel, array $transfers, array $gallery_pool): array
    {
        $images = [];
        if ($day_image !== '') {
            $images[] = $day_image;
        }
        foreach ($activities as $activity) {
            if (!empty($activity['image_url'])) {
                $images[] = (string) $activity['image_url'];
            }
        }
        if (!empty($hotel['image_url'])) {
            $images[] = (string) $hotel['image_url'];
        }
        foreach ($transfers as $transfer) {
            if (!empty($transfer['image_url'])) {
                $images[] = (string) $transfer['image_url'];
            }
        }
        foreach ($gallery_pool as $extra) {
            $images[] = (string) $extra;
        }

        $images = self::unique_non_empty($images);
        $images = array_slice($images, 0, 3);

        $fallbacks = [
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-1.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-2.svg',
            AJTB_PLUGIN_URL . 'assets/images/tour-v1/day-3.svg',
        ];
        while (count($images) < 3) {
            $images[] = $fallbacks[count($images)];
        }
        return $images;
    }

    private static function normalize_meals($meals_raw): array
    {
        if (!is_string($meals_raw) || trim($meals_raw) === '') {
            return [];
        }

        $normalized = preg_split('/[,\n\r;|]+/', $meals_raw);
        if (!is_array($normalized)) {
            return [];
        }

        $meals = [];
        foreach ($normalized as $meal) {
            $meal = trim(strip_tags((string) $meal));
            if ($meal !== '') {
                $meals[] = $meal;
            }
        }
        return self::unique_non_empty($meals);
    }

    private static function build_stats(array $days): array
    {
        $flight_count = 0;
        $transfer_count = 0;
        $hotel_count = 0;
        $activity_count = 0;

        foreach ($days as $day) {
            $flight_count += count($day['flights_out'] ?? []) + count($day['flights_in'] ?? []);
            $transfer_count += count($day['transfers_in'] ?? []) + count($day['transfers_out'] ?? []);
            if (!empty($day['hotel'])) {
                $hotel_count++;
            }
            $activity_count += count($day['activities'] ?? []);
        }

        return [
            'days' => max(1, count($days)),
            'flights' => max(0, $flight_count),
            'transfers' => max(0, $transfer_count),
            'hotels' => max(0, $hotel_count),
            'activities' => max(0, $activity_count),
        ];
    }

    private static function build_summary_rows(array $days): array
    {
        $rows = [];
        foreach ($days as $day) {
            $parts = [];
            if (!empty($day['flights_out']) || !empty($day['flights_in'])) {
                $parts[] = 'Flight';
            }
            if (!empty($day['transfers_in']) || !empty($day['transfers_out'])) {
                $parts[] = 'Transfer';
            }
            if (!empty($day['hotel'])) {
                $parts[] = 'Hotel';
            }
            if (!empty($day['activities'])) {
                $parts[] = 'Activities';
            }
            if (!empty($day['meals'])) {
                $parts[] = 'Meals';
            }
            if (empty($parts)) {
                $parts[] = 'Programme details';
            }
            $rows[] = [
                'label' => 'Day ' . (int) ($day['day'] ?? 0),
                'text' => implode(' + ', $parts),
            ];
        }
        return $rows;
    }

    private static function day_date_label(string $first_date, int $day_number): string
    {
        if ($first_date === '' || $day_number < 1) {
            return 'Day ' . $day_number;
        }
        try {
            $base = new DateTimeImmutable($first_date);
            $label = $base->modify('+' . max(0, $day_number - 1) . ' day');
            return $label->format('d M, D');
        } catch (Throwable $e) {
            return 'Day ' . $day_number;
        }
    }

    private static function format_date_label(string $date): string
    {
        try {
            $dt = new DateTimeImmutable($date);
            return $dt->format('D, d M Y');
        } catch (Throwable $e) {
            return $date;
        }
    }

    private static function normalize_list_items(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $line = trim(strip_tags((string) $item));
            if ($line !== '') {
                $out[] = $line;
            }
        }
        return self::unique_non_empty($out);
    }

    /**
     * Convert rich HTML content into readable plain text while preserving line breaks.
     */
    private static function normalize_rich_text(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $break_tags = ['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'];
        $html = str_ireplace($break_tags, "\n", $html);
        $html = preg_replace('/<li\b[^>]*>/i', "- ", $html);
        if (!is_string($html)) {
            return '';
        }

        $text = wp_strip_all_tags($html);
        if (function_exists('html_entity_decode')) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $lines = preg_split('/\R+/', $text);
        if (!is_array($lines)) {
            return trim($text);
        }

        $clean = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $clean[] = $line;
            }
        }

        return trim(implode("\n", $clean));
    }

    private static function extract_lines(string $content, int $limit = 10): array
    {
        $clean = trim(wp_strip_all_tags($content));
        if ($clean === '') {
            return [];
        }
        $parts = preg_split('/[\r\n\.;|]+/', $clean);
        if (!is_array($parts)) {
            return [];
        }
        $out = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if (mb_strlen($part) < 4) {
                continue;
            }
            $out[] = $part;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    private static function unique_non_empty(array $items): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            $val = trim((string) $item);
            if ($val === '') {
                continue;
            }
            if (isset($seen[$val])) {
                continue;
            }
            $seen[$val] = true;
            $out[] = $val;
        }
        return $out;
    }
}
