<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RatehawkHotelController extends Controller
{
    /** Message utilisateur lorsque l’API renvoie error = not_allowed_host (HTTP 403 ou corps JSON). */
    private const MSG_NOT_ALLOWED_HOST = 'L’accès API RateHawk est refusé pour cette adresse IP. Merci de demander la whitelist de l’IP publique du serveur auprès de RateHawk.';

    /**
     * Recherche hôtels (SERP région) — ETG / RateHawk API v3.
     * Ville → multicomplete → region_id → serp/region.
     *
     * @see https://docs.emergingtravel.com/docs/b2b-api/hotel-search/suggest-hotel-and-region/
     * @see https://docs.emergingtravel.com/docs/b2b-api/hotel-search/search-by-region/
     */
    public function index(Request $request)
    {
        $keyId = config('services.ratehawk.key_id');
        $apiKey = config('services.ratehawk.api_key');
        $baseUrlRaw = config('services.ratehawk.base_url');
        $baseUrl = is_string($baseUrlRaw) ? rtrim($baseUrlRaw, '/') : '';
        $timeout = max(10, (int) config('services.ratehawk.timeout', 45));

        $configured = $this->isRatehawkConfigured($keyId, $apiKey, $baseUrl);

        $defaults = $this->defaultSearchInputs();

        $request->merge([
            'region_id' => $request->filled('region_id') ? (int) $request->input('region_id') : null,
            'city' => trim((string) $request->input('city', '')),
            'checkin' => $request->input('checkin', $defaults['checkin']),
            'checkout' => $request->input('checkout', $defaults['checkout']),
            'adults' => $request->input('adults', $defaults['adults']),
        ]);

        $validated = $request->validate([
            'region_id' => ['nullable', 'integer', 'min:1'],
            'city' => ['nullable', 'string', 'max:200'],
            'checkin' => ['required', 'date_format:Y-m-d'],
            'checkout' => ['required', 'date_format:Y-m-d', 'after:checkin'],
            'adults' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $explicitRegionId = $validated['region_id'] ?? null;
        $city = $validated['city'] ?? '';
        $checkin = $validated['checkin'];
        $checkout = $validated['checkout'];
        $adults = max(1, min(6, (int) $validated['adults']));

        $regionId = $explicitRegionId;
        $resolvedLabel = null;
        $resolvedRegionMeta = null;

        $hotels = [];
        $totalHotels = null;
        $error = null;
        $apiDebug = null;
        $ratehawkAccessDeniedDebug = null;
        $ratehawkIpAccessDenied = false;
        $multicompleteLogSummary = null;

        // Recherche explicite : hidden search=1, ou ville / region_id présents (query GET)
        $searchRequested = $request->filled('search')
            || $request->boolean('search')
            || $explicitRegionId !== null
            || $city !== '';

        Log::info('[RateHawk] index request', [
            'search_param' => $request->query('search'),
            'search_bool' => $request->boolean('search'),
            'searchRequested' => $searchRequested,
            'city' => $city !== '' ? $city : null,
            'region_id_explicit' => $explicitRegionId,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => $adults,
        ]);

        if (! $configured) {
            $error = 'Configuration API RateHawk manquante : renseignez RATEHAWK_KEY_ID, RATEHAWK_API_KEY et RATEHAWK_API_BASE_URL dans votre fichier .env.';
            Log::error('[RateHawk] configuration manquante');
        } elseif ($searchRequested && $explicitRegionId === null && $city === '') {
            $error = 'Indiquez une ville ou un identifiant de région (option avancée).';
        } elseif ($searchRequested && $regionId === null && $city !== '') {
            $resolution = $this->getRegionIdFromCity($city, $baseUrl, (string) $keyId, (string) $apiKey, $timeout);
            $multicompleteLogSummary = $resolution['log_summary'] ?? null;

            if ($resolution['fetch_failed']) {
                $diag = $resolution['multicomplete_diagnostic'] ?? [];
                $apiErr = $diag['api_error'] ?? null;

                if ($this->isRatehawkNotAllowedHostError($apiErr)) {
                    $validation = $diag['validation'] ?? null;
                    $validationStr = is_string($validation) ? $validation : null;
                    $deniedIp = $this->extractIpFromRatehawkValidation($validationStr);
                    $httpStatus = isset($diag['http_status']) ? (int) $diag['http_status'] : null;
                    Log::warning('[RateHawk] multicomplete not_allowed_host (refus IP whitelist)', [
                        'city' => $city,
                        'http_status' => $httpStatus,
                        'error' => $apiErr,
                        'validation_error' => $validationStr,
                        'ip_detected' => $deniedIp,
                    ]);
                    $error = self::MSG_NOT_ALLOWED_HOST;
                    $ratehawkIpAccessDenied = true;
                    if (config('app.debug')) {
                        $apiDebug = ['multicomplete' => $diag];
                        $ratehawkAccessDeniedDebug = $this->buildRatehawkAccessDeniedDebug(
                            is_string($apiErr) ? $apiErr : 'not_allowed_host',
                            $validationStr,
                            $deniedIp,
                            $httpStatus
                        );
                    }
                } else {
                    Log::error('[RateHawk] multicomplete indisponible', [
                        'city' => $city,
                        'diagnostic' => $diag,
                    ]);
                    $error = 'Impossible de joindre l’API RateHawk (multicomplete). Réessayez plus tard.';
                    if (config('app.debug')) {
                        $apiDebug = [
                            'multicomplete' => $diag,
                        ];
                    }
                }
            } elseif ($resolution['region'] === null) {
                Log::info('[RateHawk] aucune région pour la ville', ['city' => $city, 'summary' => $multicompleteLogSummary]);
                $error = 'Aucune destination trouvée pour cette ville.';
            } else {
                $regionId = $resolution['region']['id'];
                $resolvedLabel = $resolution['region']['name'];
                $resolvedRegionMeta = $resolution['region'];
                Log::info('[RateHawk] region_id retenu', [
                    'city' => $city,
                    'region_id' => $regionId,
                    'name' => $resolvedLabel,
                    'type' => $resolution['region']['type'] ?? null,
                ]);
            }
        } elseif ($searchRequested && $regionId !== null && $explicitRegionId !== null && $city !== '') {
            $resolvedLabel = 'ID région #'.$regionId;
        }

        if ($configured && $searchRequested && $error === null && $regionId !== null) {
            $url = $baseUrl.'/api/b2b/v3/search/serp/region/';
            $payload = $this->buildSerpPayload($regionId, $checkin, $checkout, $adults);

            Log::info('[RateHawk] SERP payload', [
                'url' => $url,
                'region_id' => $regionId,
                'payload' => $payload,
            ]);

            try {
                $response = $this->ratehawkJsonPost($url, $payload, (string) $keyId, (string) $apiKey, $timeout);

                $json = $response->json();
                $rawBody = $response->body();
                $logBody = strlen($rawBody) > 12000 ? substr($rawBody, 0, 12000).'…[truncated]' : $rawBody;

                Log::info('[RateHawk] SERP réponse HTTP', [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body_excerpt' => $logBody,
                ]);

                if (! $response->successful() || ! is_array($json)) {
                    $httpStatusSerp = $response->status();
                    $parsedSerp = is_array($json) ? $json : null;
                    if ($parsedSerp === null && $rawBody !== '') {
                        $decodedSerp = json_decode($rawBody, true);
                        $parsedSerp = is_array($decodedSerp) ? $decodedSerp : null;
                    }

                    if ($httpStatusSerp === 403 && is_array($parsedSerp) && $this->isRatehawkNotAllowedHostError($parsedSerp['error'] ?? null)) {
                        $serpVal = data_get($parsedSerp, 'debug.validation_error');
                        $serpValStr = is_string($serpVal) ? $serpVal : null;
                        $deniedIpSerp = $this->extractIpFromRatehawkValidation($serpValStr);
                        Log::warning('[RateHawk] SERP HTTP 403 not_allowed_host (refus IP whitelist)', [
                            'region_id' => $regionId,
                            'http_status' => 403,
                            'error' => $parsedSerp['error'] ?? null,
                            'validation_error' => $serpValStr,
                            'ip_detected' => $deniedIpSerp,
                        ]);
                        $error = self::MSG_NOT_ALLOWED_HOST;
                        $ratehawkIpAccessDenied = true;
                        if (config('app.debug')) {
                            $ratehawkAccessDeniedDebug = $this->buildRatehawkAccessDeniedDebug(
                                is_string($parsedSerp['error'] ?? null) ? (string) $parsedSerp['error'] : 'not_allowed_host',
                                $serpValStr,
                                $deniedIpSerp,
                                403
                            );
                            $apiDebug = array_merge($apiDebug ?? [], ['serp_http' => $response->status(), 'serp_body_excerpt' => strlen($logBody) > 2000 ? substr($logBody, 0, 2000).'…' : $logBody]);
                        }
                    } else {
                        Log::error('[RateHawk] SERP HTTP invalide', [
                            'status' => $httpStatusSerp,
                            'body_excerpt' => $logBody,
                        ]);
                        $error = 'Impossible de récupérer les hôtels depuis RateHawk.';
                    }
                } else {
                    $apiStatus = $json['status'] ?? null;
                    $apiErr = $json['error'] ?? null;
                    $data = is_array($json['data'] ?? null) ? $json['data'] : [];
                    $rawHotels = is_array($data['hotels'] ?? null) ? $data['hotels'] : [];
                    $countHotels = count($rawHotels);

                    Log::info('[RateHawk] SERP JSON parsé', [
                        'api_status' => $apiStatus,
                        'api_error' => $apiErr,
                        'data_keys' => array_keys($data),
                        'hotels_count' => $countHotels,
                        'total_hotels' => $data['total_hotels'] ?? null,
                    ]);

                    if (config('app.debug')) {
                        $apiDebug = array_merge($apiDebug ?? [], [
                            'city' => $city !== '' ? $city : null,
                            'region_id' => $regionId,
                            'resolved_region' => $resolvedRegionMeta,
                            'hotels_in_response' => $countHotels,
                            'total_hotels' => $data['total_hotels'] ?? null,
                            'serp_status' => $apiStatus,
                            'serp_error' => $apiErr,
                            'validation' => data_get($json, 'debug.validation_error'),
                        ]);
                    }

                    if (($json['status'] ?? '') !== 'ok' || ($json['error'] ?? null) !== null) {
                        $serpValidation = data_get($json, 'debug.validation_error');
                        $serpValidationStr = is_string($serpValidation) ? $serpValidation : null;

                        if ($this->isRatehawkNotAllowedHostError($apiErr)) {
                            $deniedIp = $this->extractIpFromRatehawkValidation($serpValidationStr);
                            Log::warning('[RateHawk] SERP not_allowed_host (refus IP whitelist)', [
                                'region_id' => $regionId,
                                'http_status' => $response->status(),
                                'error' => $apiErr,
                                'validation_error' => $serpValidationStr,
                                'ip_detected' => $deniedIp,
                            ]);
                            $error = self::MSG_NOT_ALLOWED_HOST;
                            $ratehawkIpAccessDenied = true;
                            if (config('app.debug')) {
                                $ratehawkAccessDeniedDebug = $this->buildRatehawkAccessDeniedDebug(
                                    is_string($apiErr) ? $apiErr : 'not_allowed_host',
                                    $serpValidationStr,
                                    $deniedIp,
                                    $response->status()
                                );
                            }
                        } else {
                            Log::error('[RateHawk] SERP erreur métier', [
                                'error' => $apiErr,
                                'validation' => $serpValidation,
                            ]);
                            $error = 'Impossible de récupérer les hôtels depuis RateHawk.';
                        }
                    } else {
                        $totalHotels = isset($data['total_hotels']) ? (int) $data['total_hotels'] : $countHotels;
                        foreach ($rawHotels as $row) {
                            if (is_array($row)) {
                                $hotels[] = $this->normalizeHotel($row);
                            }
                        }
                        Log::info('[RateHawk] hôtels normalisés', [
                            'count' => count($hotels),
                            'total_hotels' => $totalHotels,
                        ]);

                        if ($countHotels === 0) {
                            Log::info('[RateHawk] liste hôtels vide (SERP ok)');
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::error('[RateHawk] SERP exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $error = 'Impossible de récupérer les hôtels depuis RateHawk.';
            }
        }

        return view('ratehawk.hotels', [
            'hotels' => $hotels,
            'totalHotels' => $totalHotels,
            'error' => $error,
            'configured' => $configured,
            'searchRequested' => $searchRequested,
            'apiDebug' => $apiDebug,
            'ratehawkAccessDeniedDebug' => $ratehawkAccessDeniedDebug,
            'ratehawkIpAccessDenied' => $ratehawkIpAccessDenied,
            'regionId' => $regionId,
            'city' => $city,
            'resolvedLabel' => $resolvedLabel,
            'resolvedRegionMeta' => $resolvedRegionMeta,
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => $adults,
            'placeholderImage' => asset('images/hotel-placeholder.svg'),
            'autocompleteUrl' => route('ratehawk.hotels.autocomplete', [], false),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $keyId = config('services.ratehawk.key_id');
        $apiKey = config('services.ratehawk.api_key');
        $baseUrlRaw = config('services.ratehawk.base_url');
        $baseUrl = is_string($baseUrlRaw) ? rtrim($baseUrlRaw, '/') : '';
        $timeout = max(5, min(20, (int) config('services.ratehawk.timeout', 45)));

        if (! $this->isRatehawkConfigured($keyId, $apiKey, $baseUrl)) {
            return response()->json(['suggestions' => [], 'error' => 'not_configured'], 503);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $data = $this->fetchMulticomplete($q, $baseUrl, (string) $keyId, (string) $apiKey, $timeout);
        if ($data === null) {
            return response()->json(['suggestions' => [], 'error' => 'request_failed']);
        }

        return response()->json(['suggestions' => $this->buildAutocompleteSuggestions($data)]);
    }

    /**
     * Résout une ville vers une région ETG via multicomplete.
     *
     * @return array{fetch_failed: bool, region: array{id: int, name: string, type: string|null}|null, log_summary: array<string, mixed>|null}
     */
    private function getRegionIdFromCity(string $city, string $baseUrl, string $keyId, string $apiKey, int $timeout): array
    {
        if ($city === '') {
            return ['fetch_failed' => false, 'region' => null, 'log_summary' => null, 'multicomplete_diagnostic' => null];
        }

        $result = $this->requestMulticomplete($city, $baseUrl, $keyId, $apiKey, $timeout);

        if (! $result['ok']) {
            return [
                'fetch_failed' => true,
                'region' => null,
                'log_summary' => null,
                'multicomplete_diagnostic' => $result['diagnostic'],
            ];
        }

        $data = $result['data'];

        $regionsCount = is_array($data['regions'] ?? null) ? count($data['regions']) : 0;
        $hotelsCount = is_array($data['hotels'] ?? null) ? count($data['hotels']) : 0;
        Log::info('[RateHawk] multicomplete brut', [
            'query' => $city,
            'regions_count' => $regionsCount,
            'hotels_count' => $hotelsCount,
        ]);

        $picked = $this->pickBestRegionFromMulticompleteData($data, $city);
        $logSummary = [
            'regions_count' => $regionsCount,
            'hotels_count' => $hotelsCount,
            'picked' => $picked,
        ];

        if ($picked === null) {
            return ['fetch_failed' => false, 'region' => null, 'log_summary' => $logSummary, 'multicomplete_diagnostic' => null];
        }

        return [
            'fetch_failed' => false,
            'region' => [
                'id' => $picked['id'],
                'name' => $picked['name'],
                'type' => $picked['type'] ?? null,
            ],
            'log_summary' => $logSummary,
            'multicomplete_diagnostic' => null,
        ];
    }

    /**
     * POST JSON vers l’API ETG avec Basic Auth (KEY_ID / API_KEY), comme la doc curl.
     *
     * @see https://docs.emergingtravel.com/docs/b2b-api/hotel-search/suggest-hotel-and-region/
     */
    private function ratehawkJsonPost(string $url, array $body, string $keyId, string $apiKey, int $timeout): Response
    {
        $connectTimeout = max(5, min((int) config('services.ratehawk.connect_timeout', 15), $timeout));

        return Http::withBasicAuth(trim($keyId), trim($apiKey))
            ->acceptJson()
            ->asJson()
            ->withOptions([
                'verify' => filter_var(config('services.ratehawk.verify_ssl', true), FILTER_VALIDATE_BOOL),
            ])
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->post($url, $body);
    }

    /**
     * Appel documenté : POST /api/b2b/v3/search/multicomplete/ avec { "query", "language" }.
     *
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, diagnostic: array<string, mixed>}
     */
    private function requestMulticomplete(string $query, string $baseUrl, string $keyId, string $apiKey, int $timeout): array
    {
        $url = rtrim($baseUrl, '/').'/api/b2b/v3/search/multicomplete/';
        $language = (string) config('services.ratehawk.language', 'fr');
        $payload = [
            'query' => $query,
            'language' => $language,
        ];
        $connectCfg = max(5, min((int) config('services.ratehawk.connect_timeout', 15), $timeout));

        Log::info('[RateHawk] multicomplete request', [
            'url' => $url,
            'method' => 'POST',
            'payload' => $payload,
            'timeout' => $timeout,
            'connect_timeout' => $connectCfg,
            'verify_ssl' => filter_var(config('services.ratehawk.verify_ssl', true), FILTER_VALIDATE_BOOL),
            'key_id_prefix' => trim($keyId) !== '' ? substr(trim($keyId), 0, 4).'…' : '(empty)',
        ]);

        try {
            $response = $this->ratehawkJsonPost($url, $payload, $keyId, $apiKey, $timeout);
            $status = $response->status();
            $body = (string) $response->body();
            $excerpt = strlen($body) > 4000 ? substr($body, 0, 4000).'…[truncated]' : $body;
            $json = $response->json();

            Log::info('[RateHawk] multicomplete response', [
                'http_status' => $status,
                'successful' => $response->successful(),
                'body_excerpt' => $excerpt,
            ]);

            if (! $response->successful()) {
                $parsedOnError = is_array($json) ? $json : null;
                if ($parsedOnError === null && $body !== '') {
                    $decodedErr = json_decode($body, true);
                    $parsedOnError = is_array($decodedErr) ? $decodedErr : null;
                }

                if ($status === 403 && is_array($parsedOnError) && $this->isRatehawkNotAllowedHostError($parsedOnError['error'] ?? null)) {
                    $validationErr = data_get($parsedOnError, 'debug.validation_error');
                    Log::warning('[RateHawk] multicomplete HTTP 403 not_allowed_host (refus IP whitelist)', [
                        'http_status' => 403,
                        'error' => $parsedOnError['error'] ?? null,
                        'validation_error' => $validationErr,
                    ]);

                    return [
                        'ok' => false,
                        'diagnostic' => [
                            'step' => 'api',
                            'http_status' => 403,
                            'api_status' => $parsedOnError['status'] ?? null,
                            'api_error' => $parsedOnError['error'] ?? null,
                            'validation' => $validationErr,
                            'debug' => $parsedOnError['debug'] ?? null,
                        ],
                    ];
                }

                Log::error('[RateHawk] multicomplete HTTP error', [
                    'http_status' => $status,
                    'hint' => $this->multicompleteHttpHint($status),
                    'body_excerpt' => $excerpt,
                ]);

                return [
                    'ok' => false,
                    'diagnostic' => [
                        'step' => 'http',
                        'http_status' => $status,
                        'hint' => $this->multicompleteHttpHint($status),
                        'body_excerpt' => $excerpt,
                    ],
                ];
            }

            if (! is_array($json)) {
                Log::error('[RateHawk] multicomplete JSON invalide', ['body_excerpt' => $excerpt]);

                return [
                    'ok' => false,
                    'diagnostic' => [
                        'step' => 'bad_json',
                        'body_excerpt' => $excerpt,
                    ],
                ];
            }

            $apiStatus = $json['status'] ?? null;
            $apiError = $json['error'] ?? null;

            if (($apiStatus !== 'ok') || ($apiError !== null && $apiError !== '')) {
                $validationErr = data_get($json, 'debug.validation_error');
                if ($this->isRatehawkNotAllowedHostError($apiError)) {
                    Log::warning('[RateHawk] multicomplete réponse not_allowed_host', [
                        'http_status' => $status,
                        'api_status' => $apiStatus,
                        'error' => $apiError,
                        'validation_error' => $validationErr,
                    ]);
                } else {
                    Log::error('[RateHawk] multicomplete API métier', [
                        'api_status' => $apiStatus,
                        'api_error' => $apiError,
                        'validation' => $validationErr,
                    ]);
                }

                return [
                    'ok' => false,
                    'diagnostic' => [
                        'step' => 'api',
                        'http_status' => $status,
                        'api_status' => $apiStatus,
                        'api_error' => $apiError,
                        'validation' => $validationErr,
                        'debug' => $json['debug'] ?? null,
                    ],
                ];
            }

            $data = $json['data'] ?? null;
            if (! is_array($data)) {
                return [
                    'ok' => false,
                    'diagnostic' => [
                        'step' => 'no_data',
                        'message' => 'Réponse « ok » mais « data » n’est pas un objet.',
                        'api_status' => $apiStatus,
                    ],
                ];
            }

            return ['ok' => true, 'data' => $data];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            Log::error('[RateHawk] multicomplete exception', [
                'exception' => $e::class,
                'message' => $msg,
            ]);

            return [
                'ok' => false,
                'diagnostic' => [
                    'step' => 'exception',
                    'exception' => $e::class,
                    'message' => $msg,
                    'hint' => $this->multicompleteExceptionHint($msg),
                ],
            ];
        }
    }

    private function isRatehawkNotAllowedHostError(mixed $apiError): bool
    {
        return is_string($apiError) && $apiError === 'not_allowed_host';
    }

    /**
     * Extrait une IPv4 du message debug.validation_error (ex. "IP 1.2.3.4 is not allowed").
     */
    private function extractIpFromRatehawkValidation(?string $validation): ?string
    {
        if ($validation === null || $validation === '') {
            return null;
        }

        if (preg_match('/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/', $validation, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * @return array{error: string, validation_error: string|null, http_status: int|null, ip: string|null}
     */
    private function buildRatehawkAccessDeniedDebug(string $apiError, ?string $validationError, ?string $ip, ?int $httpStatus = null): array
    {
        return [
            'error' => $apiError,
            'validation_error' => $validationError,
            'http_status' => $httpStatus,
            'ip' => $ip,
        ];
    }

    private function multicompleteHttpHint(int $status): string
    {
        return match ($status) {
            401, 403 => 'Vérifiez RATEHAWK_KEY_ID et RATEHAWK_API_KEY (Basic Auth, comme curl --user).',
            404 => 'Vérifiez RATEHAWK_API_BASE_URL (production https://api.worldota.net ou sandbox https://api-sandbox.worldota.net).',
            415 => 'Corps ou en-têtes refusés (attendu : JSON + Content-Type application/json).',
            429 => 'Trop de requêtes — réessayez plus tard.',
            500, 502, 503, 504 => 'Erreur ou indisponibilité côté serveur RateHawk.',
            default => 'Voir body_excerpt et la doc Emerging Travel (multicomplete).',
        };
    }

    private function multicompleteExceptionHint(string $message): string
    {
        $lower = mb_strtolower($message);

        if (str_contains($lower, 'ssl') || str_contains($lower, 'certificate') || str_contains($lower, 'tls')) {
            return 'Problème SSL : en local uniquement, essayez RATEHAWK_VERIFY_SSL=false dans .env, ou mettez à jour les certificats CA système.';
        }

        if (str_contains($lower, 'timed out') || str_contains($lower, 'operation timed out')) {
            return 'Timeout réseau : augmentez RATEHAWK_TIMEOUT ou vérifiez pare-feu / DNS / proxy.';
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null  clé `data` de la réponse JSON
     */
    private function fetchMulticomplete(string $query, string $baseUrl, string $keyId, string $apiKey, int $timeout): ?array
    {
        $result = $this->requestMulticomplete($query, $baseUrl, $keyId, $apiKey, $timeout);

        return $result['ok'] ? $result['data'] : null;
    }

    /**
     * Choisit la meilleure entrée région / destination (priorité City, correspondance nom).
     *
     * @param  array<string, mixed>  $data
     * @return array{id: int, name: string, type: string|null}|null
     */
    private function pickBestRegionFromMulticompleteData(array $data, string $originalQuery): ?array
    {
        $queryLower = mb_strtolower($originalQuery);
        $regions = $data['regions'] ?? null;

        if (is_array($regions) && $regions !== []) {
            $scored = [];
            foreach ($regions as $r) {
                if (! is_array($r)) {
                    continue;
                }
                $rid = $this->normalizeRegionNumericId($r['id'] ?? null);
                if ($rid === null) {
                    continue;
                }
                $name = (string) ($r['name'] ?? '');
                $type = isset($r['type']) ? (string) $r['type'] : '';
                $score = 0;
                if ($type === 'City') {
                    $score += 100;
                } elseif (str_contains($type, 'Multi-City')) {
                    $score += 80;
                } elseif (str_contains($type, 'Province') || str_contains($type, 'Multi-Region')) {
                    $score += 40;
                }
                if ($name !== '' && str_contains(mb_strtolower($name), $queryLower)) {
                    $score += 50;
                }
                $scored[] = ['id' => $rid, 'name' => $name, 'type' => $type !== '' ? $type : null, 'score' => $score];
            }
            if ($scored !== []) {
                usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
                $best = $scored[0];

                return ['id' => $best['id'], 'name' => $best['name'] !== '' ? $best['name'] : 'Région #'.$best['id'], 'type' => $best['type']];
            }
        }

        $hotels = $data['hotels'] ?? null;
        if (is_array($hotels) && $hotels !== []) {
            foreach ($hotels as $h) {
                if (! is_array($h)) {
                    continue;
                }
                if (! isset($h['region_id']) || ! is_numeric($h['region_id'])) {
                    continue;
                }
                $rid = (int) $h['region_id'];

                return [
                    'id' => $rid,
                    'name' => (string) ($h['name'] ?? $originalQuery),
                    'type' => 'Hotel (region_id)',
                ];
            }
        }

        return null;
    }

    private function normalizeRegionNumericId(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        if (is_int($id)) {
            return $id > 0 ? $id : null;
        }
        if (is_numeric($id)) {
            $n = (int) $id;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{region_id: int, label: string, type: string|null, country_code: string|null}>
     */
    private function buildAutocompleteSuggestions(array $data): array
    {
        $out = [];
        $seen = [];

        $regions = $data['regions'] ?? [];
        if (is_array($regions)) {
            foreach ($regions as $r) {
                if (! is_array($r)) {
                    continue;
                }
                $rid = $this->normalizeRegionNumericId($r['id'] ?? null);
                if ($rid === null || isset($seen[$rid])) {
                    continue;
                }
                $seen[$rid] = true;
                $out[] = [
                    'region_id' => $rid,
                    'label' => (string) ($r['name'] ?? ''),
                    'type' => isset($r['type']) ? (string) $r['type'] : null,
                    'country_code' => isset($r['country_code']) ? (string) $r['country_code'] : null,
                ];
            }
        }

        $hotels = $data['hotels'] ?? [];
        if (is_array($hotels)) {
            foreach ($hotels as $h) {
                if (! is_array($h)) {
                    continue;
                }
                if (! isset($h['region_id']) || ! is_numeric($h['region_id'])) {
                    continue;
                }
                $rid = (int) $h['region_id'];
                if ($rid <= 0 || isset($seen[$rid])) {
                    continue;
                }
                $seen[$rid] = true;
                $out[] = [
                    'region_id' => $rid,
                    'label' => (string) ($h['name'] ?? '').' — région #'.$rid,
                    'type' => 'Hotel',
                    'country_code' => null,
                ];
            }
        }

        return array_slice($out, 0, 12);
    }

    private function isRatehawkConfigured(mixed $keyId, mixed $apiKey, string $baseUrl): bool
    {
        $kid = is_string($keyId) || is_numeric($keyId) ? trim((string) $keyId) : '';
        $key = is_string($apiKey) ? trim($apiKey) : '';

        return $kid !== '' && $key !== '' && $baseUrl !== '';
    }

    /**
     * @return array{checkin: string, checkout: string, adults: int}
     */
    private function defaultSearchInputs(): array
    {
        $checkin = now()->addDay()->format('Y-m-d');
        $checkout = now()->addDays(4)->format('Y-m-d');

        return [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSerpPayload(int $regionId, string $checkin, string $checkout, int $adults): array
    {
        $residency = (string) config('services.ratehawk.default_residency', 'ma');
        $currency = (string) config('services.ratehawk.default_currency', 'MAD');
        $language = (string) config('services.ratehawk.language', 'fr');
        $limit = (int) config('services.ratehawk.hotels_limit', 30);

        $payload = [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'residency' => strtolower($residency),
            'language' => $language,
            'guests' => [
                [
                    'adults' => $adults,
                    'children' => [],
                ],
            ],
            'region_id' => $regionId,
            'currency' => strtoupper($currency),
            'filter' => [
                'star_rating' => [],
                'kind' => [],
                'meal_type' => [],
            ],
        ];

        if ($limit > 0) {
            $payload['hotels_limit'] = $limit;
        }

        return $payload;
    }

    /**
     * Structure stable pour la vue.
     *
     * @param  array<string, mixed>  $hotel
     * @return array{id: string, name: string, image: string|null, stars: float|null, address: string|null, region_name: string|null, price: float|null, currency: string, rating: float|null, meal: string|null, hid: int, raw: array<string, mixed>}
     */
    private function normalizeHotel(array $hotel): array
    {
        $hid = (int) ($hotel['hid'] ?? 0);
        $legacyId = (string) ($hotel['id'] ?? '');
        $id = $legacyId !== '' ? $legacyId : ($hid > 0 ? 'h:'.$hid : '');

        $name = trim((string) ($hotel['name'] ?? $hotel['hotel_name'] ?? ''));

        $price = null;
        $currency = (string) config('services.ratehawk.default_currency', 'MAD');
        $meal = null;

        $rates = $hotel['rates'] ?? [];
        if (is_array($rates) && $rates !== []) {
            $first = $rates[0];
            $meal = is_array($first) ? ($first['meal_data']['value'] ?? $first['meal'] ?? null) : null;
            $paymentTypes = is_array($first) ? data_get($first, 'payment_options.payment_types', []) : [];
            if (is_array($paymentTypes) && $paymentTypes !== []) {
                $pt = $paymentTypes[0];
                if (is_array($pt)) {
                    if (isset($pt['show_amount'])) {
                        $price = (float) str_replace(',', '', (string) $pt['show_amount']);
                    }
                    $currency = (string) ($pt['show_currency_code'] ?? $pt['currency_code'] ?? $currency);
                }
            }
        }

        if ($name === '') {
            $name = $hid > 0 ? 'Hôtel #'.$hid : 'Hôtel';
        }

        $image = data_get($hotel, 'images.0.url');
        if (! is_string($image) || $image === '') {
            $image = data_get($hotel, 'images.0');
        }
        if (is_array($image) && isset($image['url'])) {
            $image = (string) $image['url'];
        }
        if (! is_string($image) || ! filter_var($image, FILTER_VALIDATE_URL)) {
            $image = null;
        }

        $stars = null;
        if (isset($hotel['star_rating']) && is_numeric($hotel['star_rating'])) {
            $stars = (float) $hotel['star_rating'];
        } elseif (isset($hotel['stars']) && is_numeric($hotel['stars'])) {
            $stars = (float) $hotel['stars'];
        }

        $rating = $stars;
        if (isset($hotel['rating']) && is_numeric($hotel['rating'])) {
            $rating = (float) $hotel['rating'];
        }

        $address = null;
        if (isset($hotel['address']) && is_string($hotel['address'])) {
            $address = trim($hotel['address']);
        } elseif (isset($hotel['location']['address']) && is_string($hotel['location']['address'])) {
            $address = trim($hotel['location']['address']);
        }

        $regionName = null;
        if (isset($hotel['region']['name']) && is_string($hotel['region']['name'])) {
            $regionName = trim($hotel['region']['name']);
        } elseif (isset($hotel['region_name']) && is_string($hotel['region_name'])) {
            $regionName = trim($hotel['region_name']);
        }

        return [
            'id' => $id !== '' ? $id : (string) $hid,
            'name' => $name,
            'image' => $image,
            'stars' => $stars,
            'address' => $address !== '' ? $address : null,
            'region_name' => $regionName !== '' ? $regionName : null,
            'price' => $price,
            'currency' => $currency,
            'rating' => $rating,
            'meal' => is_string($meal) ? $meal : null,
            'hid' => $hid,
            'raw' => $hotel,
        ];
    }
}
