<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RapidapiHotelController extends Controller
{
    /**
     * Page de test : ville → destinations → recherche hôtels (RapidAPI Booking COM).
     */
    public function index(Request $request)
    {
        $cfg = config('services.rapidapi');
        $key = $cfg['key'] ?? null;
        $host = $cfg['host'] ?? '';
        $baseUrl = $cfg['base_url'] ?? '';
        $timeout = max(10, (int) ($cfg['timeout'] ?? 45));

        $configured = is_string($key) && trim($key) !== ''
            && is_string($host) && $host !== ''
            && is_string($baseUrl) && $baseUrl !== '';

        $defaults = $this->defaultSearchInputs();

        $request->merge([
            'city' => trim((string) $request->input('city', '')),
            'checkin' => $request->input('checkin', $defaults['checkin']),
            'checkout' => $request->input('checkout', $defaults['checkout']),
            'adults' => max(1, min(6, (int) $request->input('adults', $defaults['adults']))),
        ]);

        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:200'],
            'checkin' => ['required', 'date_format:Y-m-d'],
            'checkout' => ['required', 'date_format:Y-m-d', 'after:checkin'],
            'adults' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $city = $validated['city'] ?? '';
        $checkin = $validated['checkin'];
        $checkout = $validated['checkout'];
        $adults = (int) $validated['adults'];

        $error = null;
        $hotels = [];
        $destinationLabel = null;
        $destId = null;
        $destType = null;
        $apiDebug = null;

        $searchRequested = $request->filled('search')
            || $request->boolean('search')
            || $city !== '';

        if (! $configured) {
            $error = 'Configuration RapidAPI manquante : renseignez RAPIDAPI_KEY, RAPIDAPI_HOST et RAPIDAPI_BASE_URL dans votre fichier .env.';
        } elseif ($searchRequested && $city === '') {
            $error = 'Indiquez une ville pour lancer la recherche.';
        } elseif ($configured && $searchRequested && $city !== '') {
            $dest = $this->searchDestination($city);

            if (! $dest['success']) {
                $error = $dest['error'] ?? 'Impossible de résoudre la destination.';
                if (config('app.debug')) {
                    $apiDebug = ['destination' => $dest];
                }
            } else {
                $destId = $dest['dest_id'];
                $destType = $dest['dest_type'];
                $destinationLabel = $dest['name'] ?? $dest['label'];
                $search = $this->searchHotels(
                    $destId,
                    (string) $destType,
                    $checkin,
                    $checkout,
                    $adults
                );

                if (! $search['success']) {
                    $error = $search['error'] ?? 'Impossible de récupérer les hôtels.';
                    if (config('app.debug')) {
                        $apiDebug = array_merge($apiDebug ?? [], ['destination' => $dest, 'hotels_search' => $search]);
                    }
                } else {
                    $hotels = $search['hotels'];
                    if ($hotels === []) {
                        $error = 'Aucun hôtel trouvé pour cette recherche.';
                    }
                    if (config('app.debug')) {
                        $apiDebug = [
                            'destination' => $dest,
                            'hotels_count' => count($hotels),
                        ];
                    }
                }
            }
        }

        return view('rapidapi.hotels', [
            'error' => $error,
            'hotels' => $hotels,
            'city' => $city,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => $adults,
            'configured' => $configured,
            'searchRequested' => $searchRequested,
            'destinationLabel' => $destinationLabel,
            'destId' => $destId,
            'destType' => $destType,
            'apiDebug' => $apiDebug,
        ]);
    }

    /**
     * Fiche hôtel (RapidAPI détail).
     */
    public function show(Request $request, string $hotelId)
    {
        $cfg = config('services.rapidapi');
        $key = $cfg['key'] ?? null;
        $host = $cfg['host'] ?? '';
        $baseUrl = $cfg['base_url'] ?? '';

        $configured = is_string($key) && trim($key) !== ''
            && is_string($host) && $host !== ''
            && is_string($baseUrl) && $baseUrl !== '';

        $defaults = $this->defaultSearchInputs();
        $backQuery = array_filter([
            'search' => '1',
            'city' => $request->input('city'),
            'checkin' => $request->input('checkin', $defaults['checkin']),
            'checkout' => $request->input('checkout', $defaults['checkout']),
            'adults' => $request->input('adults', $defaults['adults']),
        ], fn ($v) => $v !== null && $v !== '');

        $backUrl = route('rapidapi.hotels.index', [], false).(count($backQuery) ? '?'.http_build_query($backQuery) : '');

        if (! $configured) {
            return view('rapidapi.hotel-show', [
                'configured' => false,
                'error' => 'Configuration RapidAPI manquante : renseignez RAPIDAPI_KEY, RAPIDAPI_HOST et RAPIDAPI_BASE_URL dans votre fichier .env.',
                'hotelId' => $hotelId,
                'detail' => null,
                'raw' => null,
                'backUrl' => $backUrl,
                'apiDebug' => null,
            ]);
        }

        $arrivalDate = (string) $request->input('checkin', $defaults['checkin']);
        $departureDate = (string) $request->input('checkout', $defaults['checkout']);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $arrivalDate)) {
            $arrivalDate = $defaults['checkin'];
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $departureDate)) {
            $departureDate = $defaults['checkout'];
        }
        $detailAdults = max(1, min(6, (int) $request->input('adults', $defaults['adults'])));

        $fetch = $this->fetchHotelDetails($hotelId, $arrivalDate, $departureDate, $detailAdults);
        $apiDebug = null;

        if (! $fetch['success']) {
            $apiDebug = [
                'url' => $fetch['request_url'] ?? null,
                'params' => $fetch['request_params'] ?? null,
                'http_status' => $fetch['http_status'] ?? null,
                'body_raw' => $fetch['body_raw'] ?? null,
                'parsed' => $fetch['raw'] ?? null,
                'error' => $fetch['error'] ?? null,
            ];

            $http = (int) ($fetch['http_status'] ?? 500);
            $viewStatus = ($http === 404 || $http === 410) ? 404 : 200;

            return response()->view('rapidapi.hotel-show', [
                'configured' => true,
                'error' => $fetch['error'] ?? 'Impossible de charger cet hôtel.',
                'hotelId' => $hotelId,
                'detail' => null,
                'raw' => $fetch['raw'] ?? null,
                'backUrl' => $backUrl,
                'apiDebug' => $apiDebug,
            ], $viewStatus);
        }

        $detailRow = is_array($fetch['data']) ? $fetch['data'] : [];
        $detailRow = $this->mergeHotelDetailContextFromApi($detailRow, is_array($fetch['raw'] ?? null) ? $fetch['raw'] : null);
        $detail = $this->normalizeHotelDetailForView($detailRow);

        if (config('app.debug')) {
            $apiDebug = [
                'url' => $fetch['request_url'] ?? null,
                'params' => $fetch['request_params'] ?? null,
                'http_status' => $fetch['http_status'] ?? null,
                'body_raw' => $fetch['body_raw'] ?? null,
                'parsed' => $fetch['raw'] ?? null,
            ];
        }

        return view('rapidapi.hotel-show', [
            'configured' => true,
            'error' => null,
            'hotelId' => $hotelId,
            'detail' => $detail,
            'raw' => $fetch['raw'],
            'backUrl' => $backUrl,
            'apiDebug' => $apiDebug,
        ]);
    }

    /**
     * GET endpoint détail hôtel (config : endpoint_hotel_details, hotel_detail_id_param).
     * booking-com15 getHotelDetails exige notamment arrival_date / departure_date (Y-m-d).
     *
     * @return array<string, mixed>
     */
    private function fetchHotelDetails(string $hotelId, string $arrivalDate, string $departureDate, int $adults): array
    {
        $endpoint = (string) config('services.rapidapi.endpoint_hotel_details', '/api/v1/hotels/getHotelDetails');
        $baseUrl = rtrim((string) config('services.rapidapi.base_url'), '/');
        $url = $baseUrl.$endpoint;
        $locale = (string) config('services.rapidapi.locale', 'fr');
        $paramName = (string) config('services.rapidapi.hotel_detail_id_param', 'hotel_id');

        $query = [
            $paramName => $hotelId,
            'locale' => $locale,
            'arrival_date' => $arrivalDate,
            'departure_date' => $departureDate,
            'adults' => $adults,
        ];

        $extras = config('services.rapidapi.hotel_detail_extras');
        if (is_string($extras) && trim($extras) !== '') {
            $query['extras'] = trim($extras);
        }

        Log::info('[RapidAPI] hotel details requête', [
            'url' => $url,
            'params' => $query,
        ]);

        try {
            $response = $this->rapidapiHttp()->get($url, $query);
            $rawBody = (string) $response->body();
            $status = $response->status();
            $json = $response->json();

            Log::info('[RapidAPI] hotel details réponse', [
                'url' => $url,
                'params' => $query,
                'http_status' => $status,
                'body_raw' => $rawBody,
            ]);

            $baseMeta = [
                'request_url' => $url,
                'request_params' => $query,
                'body_raw' => $rawBody,
                'http_status' => $status,
            ];

            if (! $response->successful()) {
                $msg = 'Détail hôtel indisponible (HTTP '.$status.').';
                if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
                    $msg = $json['message'];
                }

                return array_merge($baseMeta, [
                    'success' => false,
                    'data' => null,
                    'error' => $msg,
                    'raw' => is_array($json) ? $json : null,
                ]);
            }

            if (! is_array($json)) {
                return array_merge($baseMeta, [
                    'success' => false,
                    'data' => null,
                    'error' => 'Réponse détail : JSON invalide ou vide.',
                    'raw' => null,
                ]);
            }

            $apiErr = $this->rapidapiJsonErrorMessage($json);
            if ($apiErr !== null) {
                return array_merge($baseMeta, [
                    'success' => false,
                    'data' => null,
                    'error' => $apiErr,
                    'raw' => $json,
                ]);
            }

            $data = $this->extractHotelDetailRoot($json);
            if ($data === null) {
                $data = $json;
            }

            return array_merge($baseMeta, [
                'success' => true,
                'data' => is_array($data) ? $data : [],
                'error' => null,
                'raw' => $json,
            ]);
        } catch (Throwable $e) {
            Log::error('[RapidAPI] hotel details exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Erreur réseau lors du chargement du détail.',
                'raw' => null,
                'body_raw' => null,
                'request_url' => $url,
                'request_params' => $query,
                'http_status' => 0,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    private function extractHotelDetailRoot(array $json): ?array
    {
        $candidates = [
            data_get($json, 'data.hotel'),
            data_get($json, 'data.result'),
            data_get($json, 'data.data'),
            data_get($json, 'data'),
            data_get($json, 'result.hotels.0'),
            data_get($json, 'result.data'),
            data_get($json, 'result.0'),
            data_get($json, 'result'),
            $json['hotel'] ?? null,
        ];

        foreach ($candidates as $c) {
            if (is_array($c) && $c !== []) {
                if ($this->isListArray($c) && isset($c[0]) && is_array($c[0])) {
                    return $c[0];
                }

                if (! $this->isListArray($c)) {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Complète l’objet hôtel avec rawData / rooms présents au niveau racine JSON (réponses booking-com15).
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>|null  $jsonRoot
     * @return array<string, mixed>
     */
    private function mergeHotelDetailContextFromApi(array $row, ?array $jsonRoot): array
    {
        if ($jsonRoot === null || $jsonRoot === []) {
            return $row;
        }

        if (empty(data_get($row, 'rawData'))) {
            $rawData = data_get($jsonRoot, 'data.rawData')
                ?? data_get($jsonRoot, 'result.rawData')
                ?? data_get($jsonRoot, 'rawData');
            if (is_array($rawData)) {
                $row['rawData'] = $rawData;
            }
        }

        if (empty(data_get($row, 'rooms'))) {
            $rooms = data_get($jsonRoot, 'data.rooms')
                ?? data_get($jsonRoot, 'result.rooms')
                ?? data_get($jsonRoot, 'rooms');
            if (is_array($rooms)) {
                $row['rooms'] = $rooms;
            }
        }

        foreach (['composite_price_breakdown', 'product_price_breakdown'] as $priceKey) {
            if (empty(data_get($row, $priceKey))) {
                $block = data_get($jsonRoot, 'data.'.$priceKey) ?? data_get($jsonRoot, 'result.'.$priceKey);
                if (is_array($block)) {
                    $row[$priceKey] = $block;
                }
            }
        }

        if (empty(data_get($row, 'currency_code'))) {
            $cc = data_get($jsonRoot, 'data.currency_code') ?? data_get($jsonRoot, 'result.currency_code');
            if (is_string($cc) && $cc !== '') {
                $row['currency_code'] = $cc;
            }
        }

        return $row;
    }

    /**
     * Données prêtes pour la vue fiche.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeHotelDetailForView(array $row): array
    {
        $name = trim((string) $this->dataGetMany($row, [
            'hotel_name',
            'name',
            'property.name',
            'title',
        ], ''));

        $description = $this->dataGetMany($row, [
            'description',
            'description_translated',
            'property.description',
        ], null);
        if (is_string($description)) {
            $description = trim($description);
        } else {
            $description = null;
        }

        $city = trim((string) $this->dataGetMany($row, [
            'city',
            'city_name',
            'city_name_en',
            'city_trans',
        ], ''));

        $address = trim((string) $this->dataGetMany($row, [
            'address',
            'address_trans',
        ], ''));

        $rating = $this->normalizeHotelParseRating(
            $this->dataGetMany($row, ['review_score', 'reviewScore', 'rating'], null)
        );

        $ratingLabel = trim((string) $this->dataGetMany($row, [
            'review_score_word',
            'reviewScoreWord',
        ], ''));

        $stars = $this->normalizeHotelExtractStars($row);

        $bookingUrl = $this->dataGetMany($row, ['url', 'booking_url', 'deep_link_url', 'property.url'], null);
        $bookingUrl = is_string($bookingUrl) && filter_var($bookingUrl, FILTER_VALIDATE_URL) ? $bookingUrl : null;

        $photos = $this->extractHotelDetailPhotos($row);

        $price = $this->normalizeHotelDetailExtractPrice($row);
        $priceCurrency = $this->normalizeHotelDetailExtractPriceCurrency($row);

        $heroImage = $photos[0] ?? null;
        if ($heroImage === null || $heroImage === '') {
            $heroImage = $this->normalizeHotelImageFromRow($row);
        }

        // Log temporaire (fiche détail) — retirer ou passer en Log::debug une fois validé.
        Log::info('[RapidAPI] hotel detail vue (extraction)', [
            'photos_count' => count($photos),
            'first_photo' => $photos[0] ?? null,
            'hero_image' => $heroImage,
            'price' => $price,
            'currency' => $priceCurrency,
        ]);

        $facilities = $this->extractHotelDetailFacilities($row);

        return [
            'name' => $name !== '' ? $name : 'Hôtel',
            'description' => $description !== '' && $description !== null ? $description : null,
            'city' => $city,
            'address' => $address,
            'rating' => $rating,
            'rating_label' => $ratingLabel,
            'stars' => $stars,
            'photos' => $photos,
            'hero_image' => $heroImage,
            'price' => $price,
            'currency' => $priceCurrency,
            'booking_url' => $bookingUrl,
            'facilities' => $facilities,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelDetailExtractPrice(array $row): ?float
    {
        $paths = [
            'composite_price_breakdown.all_inclusive_amount_hotel_currency.value',
            'product_price_breakdown.all_inclusive_amount_hotel_currency.value',
            'composite_price_breakdown.gross_amount_hotel_currency.value',
        ];

        foreach ($paths as $path) {
            $value = data_get($row, $path);
            $float = $this->normalizeHotelPriceScalarToFloat($value);
            if ($float !== null && $float > 0) {
                return $float;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelDetailExtractPriceCurrency(array $row): string
    {
        $paths = [
            'composite_price_breakdown.all_inclusive_amount_hotel_currency.currency',
            'product_price_breakdown.all_inclusive_amount_hotel_currency.currency',
            'composite_price_breakdown.gross_amount_hotel_currency.currency',
            'currency_code',
            'currency',
            'currencycode',
        ];

        foreach ($paths as $path) {
            $code = data_get($row, $path);
            if (is_string($code)) {
                $code = strtoupper(trim($code));
                if (preg_match('/^[A-Z]{3}$/', $code) === 1) {
                    return $code;
                }
            }
        }

        return strtoupper((string) config('services.rapidapi.currency', 'EUR'));
    }

    /**
     * Photos fiche détail : rooms.*.photos puis rawData.photoUrls, dédupliquées.
     *
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function extractHotelDetailPhotos(array $row): array
    {
        $seen = [];
        $urls = [];

        $appendUrl = function (string $candidate) use (&$seen, &$urls): void {
            $candidate = trim($candidate);
            if ($candidate === '') {
                return;
            }
            $abs = $this->normalizeHotelImageUrlToAbsolute($candidate) ?? $candidate;
            if (! filter_var($abs, FILTER_VALIDATE_URL)) {
                return;
            }
            if (isset($seen[$abs])) {
                return;
            }
            $seen[$abs] = true;
            $urls[] = $abs;
        };

        $rooms = $row['rooms'] ?? null;
        if (! is_array($rooms)) {
            $rooms = [];
        } elseif (! $this->isListArray($rooms)) {
            $rooms = array_values($rooms);
        }

        foreach ($rooms as $room) {
            if (! is_array($room)) {
                continue;
            }
            $roomPhotos = $room['photos'] ?? null;
            if (! is_array($roomPhotos)) {
                continue;
            }
            $photoList = $this->isListArray($roomPhotos) ? $roomPhotos : array_values($roomPhotos);
            foreach ($photoList as $photo) {
                if (! is_array($photo)) {
                    continue;
                }
                $candidate = $this->dataGetMany($photo, [
                    'url_max1280',
                    'url_original',
                    'url_max750',
                    'url_max300',
                ], null);
                if (! is_string($candidate) || trim($candidate) === '') {
                    continue;
                }
                $appendUrl($candidate);
            }
        }

        $rawUrls = data_get($row, 'rawData.photoUrls');
        if (! is_array($rawUrls)) {
            $rawUrls = data_get($row, 'rawData.photo_urls');
        }
        if (is_array($rawUrls)) {
            foreach ($rawUrls as $u) {
                if (is_string($u)) {
                    $appendUrl($u);
                }
            }
        }

        foreach (['main_photo_url', 'max_photo_url', 'photoMainUrl', 'image_url'] as $k) {
            if (empty($row[$k]) || ! is_string($row[$k])) {
                continue;
            }
            $appendUrl($row[$k]);
        }

        return $urls;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function extractHotelDetailFacilities(array $row): array
    {
        $out = [];
        $fac = data_get($row, 'facilities');
        if (! is_array($fac)) {
            $fac = data_get($row, 'hotel_facilities');
        }
        if (! is_array($fac)) {
            return [];
        }
        foreach ($fac as $item) {
            if (is_string($item)) {
                $t = trim($item);
                if ($t !== '') {
                    $out[] = $t;
                }
            } elseif (is_array($item)) {
                $t = trim((string) ($item['name'] ?? $item['facility_name'] ?? ''));
                if ($t !== '') {
                    $out[] = $t;
                }
            }
        }

        return $out;
    }

    /**
     * Appelle searchDestination : premier résultat exploitable (dest_id, dest_type, name).
     *
     * @return array{success: bool, dest_id: mixed, dest_type: string|null, name: string|null, label: string|null, error: string|null, raw: array|null}
     */
    public function searchDestination(string $city): array
    {
        $url = rtrim((string) config('services.rapidapi.base_url'), '/')
            .(string) config('services.rapidapi.endpoint_locations', '/api/v1/hotels/searchDestination');

        try {
            $response = $this->rapidapiHttp()->get($url, [
                'query' => $city,
            ]);
            $json = $response->json();

            if (! $response->successful() || ! is_array($json)) {
                Log::warning('[RapidAPI] destinations HTTP invalide', [
                    'status' => $response->status(),
                    'body_excerpt' => substr((string) $response->body(), 0, 1500),
                ]);

                return [
                    'success' => false,
                    'dest_id' => null,
                    'dest_type' => null,
                    'name' => null,
                    'label' => null,
                    'error' => 'La recherche de destination a échoué (HTTP '.$response->status().').',
                    'raw' => is_array($json) ? $json : null,
                ];
            }

            $apiErr = $this->rapidapiJsonErrorMessage($json);
            if ($apiErr !== null) {
                Log::warning('[RapidAPI] destinations erreur API', ['message' => $apiErr, 'city' => $city]);

                return [
                    'success' => false,
                    'dest_id' => null,
                    'dest_type' => null,
                    'name' => null,
                    'label' => null,
                    'error' => $apiErr,
                    'raw' => $json,
                ];
            }

            $picked = $this->pickFirstDestinationFromLocationsResponse($json);
            if ($picked === null) {
                return [
                    'success' => false,
                    'dest_id' => null,
                    'dest_type' => null,
                    'name' => null,
                    'label' => null,
                    'error' => 'Aucune destination correspondante pour « '.$city.' ».',
                    'raw' => $json,
                ];
            }

            Log::info('[RapidAPI] destination choisie', [
                'city_query' => $city,
                'dest_id' => $picked['dest_id'],
                'dest_type' => $picked['dest_type'],
                'name' => $picked['name'],
            ]);

            return [
                'success' => true,
                'dest_id' => $picked['dest_id'],
                'dest_type' => $picked['dest_type'],
                'name' => $picked['name'],
                'label' => $picked['name'],
                'error' => null,
                'raw' => $json,
            ];
        } catch (Throwable $e) {
            Log::error('[RapidAPI] destinations exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'dest_id' => null,
                'dest_type' => null,
                'name' => null,
                'label' => null,
                'error' => 'Erreur réseau lors de la recherche de destination.',
                'raw' => null,
            ];
        }
    }

    /**
     * Recherche les hôtels (GET searchHotels) : dest_id, search_type, dates, adultes.
     *
     * @return array{success: bool, hotels: list<array<string, mixed>>, error: string|null, raw: mixed}
     */
    public function searchHotels(
        mixed $destId,
        string $destType,
        string $arrivalDate,
        string $departureDate,
        int $adults
    ): array {
        $endpoint = (string) config('services.rapidapi.endpoint_hotels_search', '/api/v1/hotels/searchHotels');
        $currency = (string) config('services.rapidapi.currency', 'EUR');
        $roomQty = max(1, (int) config('services.rapidapi.hotels_room_qty', 1));
        $pageNumber = (int) config('services.rapidapi.hotels_page_number', 1);
        if ($pageNumber <= 0) {
            $pageNumber = 1;
        }

        $languageCode = (string) config('services.rapidapi.hotels_language_code', 'fr');

        $url = rtrim((string) config('services.rapidapi.base_url'), '/').$endpoint;

        // RapidAPI est strict sur les types (query string typée côté validateur).
        $destIdStr = $this->rapidapiStringDestId($destId);
        $searchType = $this->formatSearchTypeForHotelsApi($destType);
        $adultsInt = max(1, $adults);

        $query = [
            'dest_id' => $destIdStr,
            'search_type' => $searchType,
            'arrival_date' => $arrivalDate,
            'departure_date' => $departureDate,
            'adults' => $adultsInt,
            'room_qty' => $roomQty,
            'page_number' => $pageNumber,
            'units' => 'metric',
            'languagecode' => $languageCode,
            'currency_code' => $currency,
        ];

        Log::info('[RapidAPI] searchHotels requête', [
            'url' => $url,
            'params' => $query,
            'param_types' => [
                'dest_id' => 'string',
                'search_type' => 'string',
                'adults' => 'int',
                'page_number' => 'int',
            ],
        ]);

        try {
            $response = $this->rapidapiHttp()->get($url, $query);
            $json = $response->json();

            if (! $response->successful() || ! is_array($json)) {
                Log::warning('[RapidAPI] hotels search HTTP invalide', [
                    'status' => $response->status(),
                    'body_excerpt' => substr((string) $response->body(), 0, 1500),
                ]);

                return [
                    'success' => false,
                    'hotels' => [],
                    'error' => 'La recherche d’hôtels a échoué (HTTP '.$response->status().').',
                    'raw' => is_array($json) ? $json : null,
                ];
            }

            $apiErr = $this->rapidapiJsonErrorMessage($json);
            if ($apiErr !== null) {
                Log::warning('[RapidAPI] hotels search erreur API', ['message' => $apiErr]);

                return [
                    'success' => false,
                    'hotels' => [],
                    'error' => $apiErr,
                    'raw' => $json,
                ];
            }

            $rows = $this->extractHotelsListFromSearchResponse($json);
            $normalized = [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $normalized[] = $this->normalizeHotelCard($row);
                }
            }

            Log::info('[RapidAPI] searchHotels résultat', [
                'hotels_count' => count($normalized),
            ]);

            return [
                'success' => true,
                'hotels' => $normalized,
                'error' => null,
                'raw' => $json,
            ];
        } catch (Throwable $e) {
            Log::error('[RapidAPI] hotels search exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'hotels' => [],
                'error' => 'Erreur réseau lors de la recherche d’hôtels.',
                'raw' => null,
            ];
        }
    }

    /**
     * dest_id API : chaîne (ex. "-553173").
     */
    private function rapidapiStringDestId(mixed $destId): string
    {
        if (is_int($destId) || is_float($destId)) {
            return (string) (int) $destId;
        }

        return (string) $destId;
    }

    /**
     * booking-com15 attend souvent search_type en majuscules (CITY, REGION, …).
     */
    private function formatSearchTypeForHotelsApi(string $destType): string
    {
        $t = trim($destType);
        if ($t === '') {
            return 'CITY';
        }

        return strtoupper(str_replace([' ', '-'], '_', $t));
    }

    /**
     * Message d’erreur si le JSON API signale un échec (même HTTP 200).
     *
     * @param  array<string, mixed>  $json
     */
    private function rapidapiJsonErrorMessage(array $json): ?string
    {
        if (array_key_exists('success', $json) && $json['success'] === false) {
            return $this->rapidapiExtractErrorText($json);
        }
        if (array_key_exists('status', $json) && $json['status'] === false) {
            return $this->rapidapiExtractErrorText($json);
        }
        if (isset($json['error']) && is_string($json['error']) && $json['error'] !== '') {
            return $json['error'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function rapidapiExtractErrorText(array $json): string
    {
        $msg = $json['message'] ?? $json['msg'] ?? $json['detail'] ?? null;
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }

        return is_string($msg) && $msg !== ''
            ? $msg
            : 'L’API a retourné une erreur.';
    }

    /**
     * Client HTTP RapidAPI (x-rapidapi-key, x-rapidapi-host, timeout, SSL).
     */
    private function rapidapiHttp(): PendingRequest
    {
        $cfg = config('services.rapidapi');
        $verify = filter_var($cfg['verify_ssl'] ?? true, FILTER_VALIDATE_BOOL);
        $timeout = max(10, (int) ($cfg['timeout'] ?? 45));

        return Http::withHeaders([
            'x-rapidapi-key' => (string) ($cfg['key'] ?? ''),
            'x-rapidapi-host' => (string) ($cfg['host'] ?? ''),
            'Accept' => 'application/json',
        ])
            ->withOptions(['verify' => $verify])
            ->timeout($timeout)
            ->connectTimeout(min(15, $timeout));
    }

    /**
     * Premier résultat de la liste API avec un dest_id valide.
     *
     * @param  array<string, mixed>  $json
     * @return array{dest_id: mixed, dest_type: string, name: string}|null
     */
    private function pickFirstDestinationFromLocationsResponse(array $json): ?array
    {
        $list = $this->flattenLocationsList($json);

        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $picked = $this->destinationFieldsFromItem($item);
            if ($picked !== null) {
                return $picked;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array<string, mixed>>
     */
    private function flattenLocationsList(array $json): array
    {
        $candidates = [
            data_get($json, 'data'),
            data_get($json, 'data.data'),
            $json['result'] ?? null,
            $json['results'] ?? null,
            $json['locations'] ?? null,
            is_array($json['data'] ?? null) ? $json['data'] : null,
        ];

        foreach ($candidates as $c) {
            if (is_array($c) && $c !== [] && $this->isListArray($c)) {
                return $c;
            }
        }

        if (isset($json['data']) && is_array($json['data']) && isset($json['data'][0])) {
            return $json['data'];
        }

        return [];
    }

    /**
     * @param  array<mixed>  $arr
     */
    private function isListArray(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{dest_id: mixed, dest_type: string, name: string}|null
     */
    private function destinationFieldsFromItem(array $item): ?array
    {
        $destId = $item['dest_id'] ?? $item['id'] ?? $item['city_ufi'] ?? null;
        if ($destId === null || $destId === '') {
            return null;
        }

        $destType = (string) ($item['dest_type'] ?? $item['search_type'] ?? $item['type'] ?? 'city');
        $name = trim((string) ($item['name'] ?? $item['label'] ?? $item['city_name'] ?? $item['title'] ?? 'Destination'));

        return [
            'dest_id' => is_numeric($destId) ? 0 + $destId : $destId,
            'dest_type' => $destType !== '' ? $destType : 'city',
            'name' => $name !== '' ? $name : 'Destination',
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array<string, mixed>>
     */
    private function extractHotelsListFromSearchResponse(array $json): array
    {
        $nestedHotels = data_get($json, 'data.hotels');
        if (is_array($nestedHotels) && $nestedHotels !== [] && $this->isListArray($nestedHotels)) {
            return $nestedHotels;
        }

        $candidates = [
            data_get($json, 'data.data'),
            data_get($json, 'result'),
            data_get($json, 'data.result'),
            $json['hotels'] ?? null,
        ];

        foreach ($candidates as $c) {
            if (is_array($c) && $c !== [] && $this->isListArray($c)) {
                return $c;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *   id: mixed,
     *   name: string,
     *   image: string,
     *   address: string,
     *   city: string,
     *   price: float|null,
     *   currency: string,
     *   rating: float|null,
     *   rating_label: string,
     *   stars: int|null,
     *   raw: array<string, mixed>
     * }
     */
    private function normalizeHotelCard(array $row): array
    {
        $id = $this->dataGetMany($row, ['hotel_id', 'id', 'property.id'], null);

        $name = trim((string) $this->dataGetMany($row, [
            'hotel_name',
            'name',
            'property.name',
            'title',
            'property_name',
        ], ''));
        if ($name === '') {
            $name = 'Hôtel';
        }

        $city = trim((string) $this->dataGetMany($row, [
            'city',
            'city_name',
            'city_trans',
            'city_name_en',
        ], ''));

        $address = trim((string) $this->dataGetMany($row, [
            'address',
            'address_trans',
            'district',
            'wishlistName',
            'zip',
        ], ''));

        $price = $this->normalizeHotelExtractPrice($row);
        $currency = $this->normalizeHotelExtractCurrency($row);

        $ratingRaw = $this->dataGetMany($row, [
            'review_score',
            'reviewScore',
            'reviewScoreFormatted',
            'rating',
            'review.score',
        ], null);
        $rating = $this->normalizeHotelParseRating($ratingRaw);

        $ratingLabel = trim((string) $this->dataGetMany($row, [
            'review_score_word',
            'reviewScoreWord',
            'reviewScoreLocalized',
        ], ''));

        $stars = $this->normalizeHotelExtractStars($row);

        $image = $this->normalizeHotelImageFromRow($row);

        return [
            'id' => $id,
            'name' => $name,
            'image' => $image,
            'address' => $address,
            'city' => $city,
            'price' => $price,
            'currency' => $currency,
            'rating' => $rating,
            'rating_label' => $ratingLabel,
            'stars' => $stars,
            'raw' => $row,
        ];
    }

    /**
     * Première valeur non vide selon plusieurs chemins {@see data_get()}.
     *
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $paths
     */
    private function dataGetMany(array $source, array $paths, mixed $default = null): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);
            if ($this->dataValueIsPresent($value)) {
                return $value;
            }
        }

        return $default;
    }

    private function dataValueIsPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelImageFromRow(array $row): string
    {
        $placeholder = '/images/hotel-placeholder.svg';

        $paths = [
            'main_photo_url',
            'max_photo_url',
            'photoMainUrl',
            'image_url',
            'thumbnail',
            'property.photoUrls.0',
            'property.photoUrls.0.url',
            'hotel_photos.0.url_original',
            'hotel_photos.0.url_max300',
            'raw.main_photo_url',
            'photo_url',
            'photo_main.url',
        ];

        foreach ($paths as $path) {
            $candidate = data_get($row, $path);
            if (! is_string($candidate) && ! is_numeric($candidate)) {
                continue;
            }
            $resolved = $this->normalizeHotelImageUrlToAbsolute((string) $candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $placeholder;
    }

    private function normalizeHotelImageUrlToAbsolute(string $url): ?string
    {
        $u = trim($url);
        if ($u === '') {
            return null;
        }

        if (str_starts_with($u, '//')) {
            $u = 'https:'.$u;
        }

        if (preg_match('#^https?://#i', $u) === 1) {
            return filter_var($u, FILTER_VALIDATE_URL) ? $u : null;
        }

        if (str_starts_with($u, '/')) {
            $candidate = 'https://cf.bstatic.com'.$u;

            return filter_var($candidate, FILTER_VALIDATE_URL) ? $candidate : null;
        }

        if (preg_match('#^[a-z0-9.-]+\.[a-z]{2,}(/|$)#i', $u) === 1) {
            $candidate = 'https://'.ltrim($u, '/');

            return filter_var($candidate, FILTER_VALIDATE_URL) ? $candidate : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelExtractPrice(array $row): ?float
    {
        $paths = [
            'min_total_price',
            'composite_price_breakdown.gross_amount.value',
            'composite_price_breakdown.net_amount.value',
            'price_breakdown.gross_price',
            'price_breakdown.all_inclusive_price',
            'grossPrice.value',
            'price',
        ];

        foreach ($paths as $path) {
            $value = data_get($row, $path);
            $float = $this->normalizeHotelPriceScalarToFloat($value);
            if ($float !== null && $float > 0) {
                return $float;
            }
        }

        return null;
    }

    private function normalizeHotelPriceScalarToFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $nested = $value['value'] ?? $value['amount'] ?? $value['gross'] ?? $value['gross_price'] ?? null;

            return $this->normalizeHotelPriceScalarToFloat($nested);
        }

        if (is_string($value)) {
            $clean = str_replace(["\xC2\xA0", ' '], '', $value);
            $clean = str_replace(',', '.', preg_replace('/[^0-9.,-]/', '', $clean));
            if ($clean !== '' && is_numeric($clean)) {
                return (float) $clean;
            }

            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelExtractCurrency(array $row): string
    {
        $default = strtoupper((string) config('services.rapidapi.currency', 'EUR'));

        $paths = [
            'currencycode',
            'currency',
            'composite_price_breakdown.gross_amount.currency',
            'grossPrice.currency',
            'currency_code',
            'composite_price_breakdown.net_amount.currency',
        ];

        foreach ($paths as $path) {
            $code = data_get($row, $path);
            if (! is_string($code)) {
                continue;
            }
            $code = strtoupper(trim($code));
            if (preg_match('/^[A-Z]{3}$/', $code) === 1) {
                return $code;
            }
        }

        return $default;
    }

    private function normalizeHotelParseRating(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $clean = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $value));
            if ($clean !== '' && is_numeric($clean)) {
                return (float) $clean;
            }
        }

        return null;
    }

    /**
     * Étoiles 1–5 uniquement (class, stars, property_class). Pas review_nr.
     * Valeur &gt; 7 ou hors 1–5 → null.
     *
     * @param  array<string, mixed>  $row
     */
    private function normalizeHotelExtractStars(array $row): ?int
    {
        foreach (['class', 'stars', 'property_class'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $n = $this->normalizeHotelParseStarCount($row[$key]);
            if ($n !== null) {
                return $n;
            }
        }

        return null;
    }

    private function normalizeHotelParseStarCount(mixed $value): ?int
    {
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $n = (int) round((float) $value);
        if ($n > 7) {
            return null;
        }
        if ($n < 1 || $n > 5) {
            return null;
        }

        return $n;
    }

    /**
     * @return array{checkin: string, checkout: string, adults: int}
     */
    private function defaultSearchInputs(): array
    {
        return [
            'checkin' => now()->addDay()->format('Y-m-d'),
            'checkout' => now()->addDays(4)->format('Y-m-d'),
            'adults' => 2,
        ];
    }
}
