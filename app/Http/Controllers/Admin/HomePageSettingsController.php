<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;
use RuntimeException;

class HomePageSettingsController extends Controller
{
    public function edit()
    {
        $settings = $this->readHomeSettings();
        $header   = $this->readHeaderSettings();
        $destinationsByRegion = $this->readDestinationsByRegion();
        $tab      = request()->query('tab', 'header');

        return view('admin.settings.home-page.index', [
            'settings' => $settings,
            'header'   => $header,
            'destinationsByRegion' => $destinationsByRegion,
            'tab'      => $tab,
        ]);
    }

    public function update(Request $request)
    {
        try {
            $videoFile = $request->file('hero_video_file');
            $hasVideoUpload = $videoFile instanceof UploadedFile;
            $phpUploadError = $_FILES['hero_video_file']['error'] ?? null;
            $phpUploadName = $_FILES['hero_video_file']['name'] ?? null;
            $phpUploadSize = $_FILES['hero_video_file']['size'] ?? null;

            Log::info('Home page settings update started', [
                'has_video_upload' => $hasVideoUpload,
                'request_has_file' => $request->hasFile('hero_video_file'),
                'all_files_keys' => array_keys($request->allFiles()),
                'php_upload_name' => $phpUploadName,
                'php_upload_size' => $phpUploadSize,
                'php_upload_error' => $phpUploadError,
            ]);

            if ($videoFile instanceof UploadedFile && $videoFile->isValid()) {
                Log::info('Home page hero video upload detected', [
                    'size' => $videoFile->getSize(),
                    'original_name' => $videoFile->getClientOriginalName(),
                    'is_valid' => $videoFile->isValid(),
                    'mime' => $videoFile->getMimeType(),
                ]);
            } elseif ($videoFile instanceof UploadedFile) {
                Log::warning('Home page hero video upload invalid file state', [
                    'original_name' => $videoFile->getClientOriginalName(),
                    'is_valid' => $videoFile->isValid(),
                    'error_code' => $videoFile->getError(),
                    'error_message' => $videoFile->getErrorMessage(),
                ]);
            }

            $validated = $request->validate([
                'hero.type' => ['required', Rule::in(['image', 'video'])],
                'hero.image_url' => ['nullable', 'url', 'max:2048'],
                'hero.video_url' => ['nullable', 'string'],
                'hero.image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
                'hero_video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-m4v', 'max:51200'],
                'hero.title' => ['required', 'string', 'max:255'],
                'hero.subtitle' => ['nullable', 'string', 'max:500'],
                'hero.cta_text' => ['nullable', 'string', 'max:120'],
                'hero.cta_url' => ['nullable', 'url', 'max:2048'],
                'hero.overlay' => ['required', 'numeric', 'min:0', 'max:1'],

                'sections.search' => ['nullable', 'boolean'],
                'sections.last_minute' => ['nullable', 'boolean'],
                'sections.accommodations' => ['nullable', 'boolean'],
                'sections.regions' => ['nullable', 'boolean'],
                'sections.good_spots' => ['nullable', 'boolean'],
                'sections.promotions' => ['nullable', 'boolean'],
                'sections.newsletter' => ['nullable', 'boolean'],
                'sections.whatsapp_banner' => ['nullable', 'boolean'],
                'sections.cruises' => ['nullable', 'boolean'],
                'sections' => ['nullable', 'array'],
                'sections.*' => ['nullable'],
                'section_order' => ['nullable', 'array'],
                'section_order.*' => ['nullable', 'string', 'max:80'],
                'custom_sections' => ['nullable', 'array'],
                'custom_sections.*.title' => ['nullable', 'string', 'max:255'],
                'custom_sections.*.content' => ['nullable', 'string', 'max:50000'],

                'search.shortcode' => ['nullable', 'string', 'max:1000'],

                'last_minute.title' => ['required', 'string', 'max:255'],
                'last_minute.count' => ['required', 'integer', 'min:1', 'max:20'],
                'last_minute.featured_only' => ['nullable', 'boolean'],

                'regions' => ['nullable', 'array'],
                'regions.*.title' => ['nullable', 'string', 'max:255'],
                'regions.*.image_url' => ['nullable', 'url', 'max:2048'],
                'regions.*.link_url' => ['nullable', 'url', 'max:2048'],
                'regions_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],

                'good_spots' => ['nullable', 'array'],
                'good_spots.*.title' => ['nullable', 'string', 'max:255'],
                'good_spots.*.subtitle' => ['nullable', 'string', 'max:255'],
                'good_spots.*.icon' => ['nullable', 'string', 'max:100'],
                'good_spots.*.image_url' => ['nullable', 'url', 'max:2048'],
                'good_spots.*.link_url' => ['nullable', 'url', 'max:2048'],
                'good_spots_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
                'good_spots_title' => ['nullable', 'string', 'max:255'],

                'accommodations.title' => ['nullable', 'string', 'max:255'],
                'accommodations.count' => ['nullable', 'integer', 'min:1', 'max:20'],

                'holiday_theme.enabled' => ['nullable', 'boolean'],
                'holiday_theme.eyebrow' => ['nullable', 'string', 'max:255'],
                'holiday_theme.title_line_1' => ['nullable', 'string', 'max:255'],
                'holiday_theme.title_line_2' => ['nullable', 'string', 'max:255'],
                'holiday_theme.title_line_3' => ['nullable', 'string', 'max:255'],
                'holiday_theme.subtitle' => ['nullable', 'string', 'max:1000'],
                'holiday_theme.left_image_url' => ['nullable', 'string', 'max:2048'],
                'holiday_theme.deco_image_url' => ['nullable', 'string', 'max:2048'],
                'holiday_theme.button_text' => ['nullable', 'string', 'max:120'],
                'holiday_theme.button_url' => ['nullable', 'string', 'max:2048'],
                'holiday_theme.items' => ['nullable', 'array'],
                'holiday_theme.items.*.title' => ['nullable', 'string', 'max:255'],
                'holiday_theme.items.*.image_url' => ['nullable', 'string', 'max:2048'],
                'holiday_theme.items.*.button_text' => ['nullable', 'string', 'max:120'],
                'holiday_theme.items.*.button_url' => ['nullable', 'string', 'max:2048'],
                'holiday_theme.items.*.tags' => ['nullable', 'string', 'max:1000'],
                'holiday_theme.items.*.active' => ['nullable', 'boolean'],
                'holiday_theme.items.*.order' => ['nullable', 'integer', 'min:0'],
                'holiday_theme_left_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
                'holiday_theme_deco_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
                'holiday_theme_item_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],

                'promotions.title' => ['nullable', 'string', 'max:255'],
                'promotions.items' => ['nullable', 'array'],
                'promotions.items.*.badge_text' => ['nullable', 'string', 'max:100'],
                'promotions.items.*.badge_bg' => ['nullable', 'string', 'max:20'],
                'promotions.items.*.badge_color' => ['nullable', 'string', 'max:20'],
                'promotions.items.*.title' => ['nullable', 'string', 'max:255'],
                'promotions.items.*.text' => ['nullable', 'string', 'max:500'],
                'promotions.items.*.style' => ['nullable', 'string', 'max:20'],
                'promotions.items.*.url' => ['nullable', 'string', 'max:500'],
                'promotions.items.*.display_type' => ['nullable', Rule::in(['css', 'image'])],
                'promotions.items.*.background_color' => ['nullable', 'string', 'max:30'],
                'promotions.items.*.background_gradient' => ['nullable', 'string', 'max:255'],
                'promotions.items.*.image_url' => ['nullable', 'string', 'max:2048'],
                'promotions.items.*.overlay_enabled' => ['nullable', 'boolean'],
                'promotions.items.*.overlay_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'promotions.items.*.text_color' => ['nullable', 'string', 'max:30'],
                'promotions.items.*.button_label' => ['nullable', 'string', 'max:120'],
                'promotions.items.*.locale' => ['nullable', 'array'],
                'promotions.items.*.locale.fr' => ['nullable', 'array'],
                'promotions.items.*.locale.ar' => ['nullable', 'array'],
                'promotions.items.*.locale.fr.badge' => ['nullable', 'string', 'max:100'],
                'promotions.items.*.locale.fr.title' => ['nullable', 'string', 'max:255'],
                'promotions.items.*.locale.fr.description' => ['nullable', 'string', 'max:500'],
                'promotions.items.*.locale.ar.badge' => ['nullable', 'string', 'max:100'],
                'promotions.items.*.locale.ar.title' => ['nullable', 'string', 'max:255'],
                'promotions.items.*.locale.ar.description' => ['nullable', 'string', 'max:500'],
                'promotions_image_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],

                'whatsapp_banner.enabled' => ['nullable', 'boolean'],
                'whatsapp_banner.title' => ['nullable', 'string', 'max:255'],
                'whatsapp_banner.subtitle' => ['nullable', 'string', 'max:500'],
                'whatsapp_banner.features' => ['nullable', 'array'],
                'whatsapp_banner.features.*' => ['nullable', 'string', 'max:255'],
                'whatsapp_banner.button_text' => ['nullable', 'string', 'max:100'],
                'whatsapp_banner.button_url' => ['nullable', 'string', 'max:500'],
                'whatsapp_banner.qr_code_url' => ['nullable', 'string', 'max:2048'],
                'whatsapp_banner_qr_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],

                'cruises.enabled' => ['nullable', 'boolean'],
                'cruises.title' => ['nullable', 'string', 'max:255'],
                'cruises.image_url' => ['nullable', 'string', 'max:2048'],
                'cruises.button_text' => ['nullable', 'string', 'max:100'],
                'cruises.button_url' => ['nullable', 'string', 'max:500'],
                'cruises_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],

                'footer.col1_heading' => ['nullable', 'string', 'max:255'],
                'footer.col2_heading' => ['nullable', 'string', 'max:255'],
                'footer.legal_text' => ['nullable', 'string', 'max:1000'],

                'destinations_by_region' => ['nullable', 'array'],
                'destinations_by_region.enabled' => ['nullable'],
                'destinations_by_region.title' => ['nullable', 'string', 'max:255'],
                'destinations_by_region.items' => ['nullable', 'array'],
                'destinations_by_region.items.*.label' => ['nullable', 'string', 'max:255'],
                'destinations_by_region.items.*.image_url' => ['nullable', 'string', 'max:2048'],
                'destinations_by_region.items.*.link_url' => ['nullable', 'string', 'max:2048'],
                'destinations_by_region.items.*.order' => ['nullable', 'integer', 'min:0'],
                'destinations_by_region_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            ], [
                'hero_video_file.max' => 'Vidéo trop grande (max 50MB). Utilisez une URL YouTube/Vimeo.',
                'hero_video_file.uploaded' => 'Upload vidéo échoué (limite serveur). Augmentez upload_max_filesize/post_max_size/max_execution_time ou utilisez un lien vidéo.',
                'hero_video_file.mimetypes' => 'Le fichier vidéo doit être un MP4/M4V/MOV valide.',
            ]);

            $current = $this->readHomeSettings();

        $heroImageUrl = $validated['hero']['image_url'] ?? ($current['hero']['image_url'] ?? '');
        if ($request->hasFile('hero.image_file')) {
            $heroImagePath = $request->file('hero.image_file')->store('home-settings/hero', 'public');
            $heroImageUrl = Storage::disk('public')->url($heroImagePath);
        }

            $currentHeroVideoUrl = (string) ($current['hero']['video_url'] ?? '');
            $videoUrlInput = trim((string) (
                $validated['hero']['video_url']
                ?? ''
            ));
            $heroVideoUrl = $videoUrlInput !== '' ? $videoUrlInput : $currentHeroVideoUrl;

            if ($request->hasFile('hero_video_file')) {
                $uploadedVideo = $request->file('hero_video_file');
                $videoOk = false;
                if ($uploadedVideo instanceof UploadedFile && $uploadedVideo->isValid()) {
                    try {
                        $baseName = pathinfo($uploadedVideo->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeBaseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $baseName);
                        $safeBaseName = trim((string) $safeBaseName, '-_') ?: 'hero-video';
                        $extension = strtolower((string) $uploadedVideo->getClientOriginalExtension()) ?: 'mp4';
                        $fileName = $safeBaseName . '-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
                        $heroVideoPath = Storage::disk('public')->putFileAs('home-settings/hero', $uploadedVideo, $fileName);
                        if ($heroVideoPath !== false) {
                            $heroVideoUrl = Storage::disk('public')->url($heroVideoPath);
                            $videoOk = true;
                        }
                    } catch (Throwable $ve) {
                        Log::warning('Home page hero video upload failed', ['message' => $ve->getMessage()]);
                    }
                }
                if (!$videoOk) {
                    $request->session()->flash('warning', "L'upload de la video hero a echoue. L'URL existante est conservee. Utilisez un lien YouTube/Vimeo ou augmentez upload_max_filesize/post_max_size.");
                }
            }

        $regions = [];
        $regionsInput = $validated['regions'] ?? [];
        foreach ($regionsInput as $index => $region) {
            $title = trim((string)($region['title'] ?? ''));
            $imageUrl = trim((string)($region['image_url'] ?? ''));
            $linkUrl = trim((string)($region['link_url'] ?? ''));

            if ($request->hasFile("regions_files.$index")) {
                $path = $request->file("regions_files.$index")->store('home-settings/regions', 'public');
                $imageUrl = Storage::disk('public')->url($path);
            }

            if ($title === '' && $imageUrl === '' && $linkUrl === '') {
                continue;
            }

            $regions[] = [
                'title' => $title,
                'image_url' => $imageUrl,
                'link_url' => $linkUrl,
            ];
        }

        $goodSpotsInput = $validated['good_spots'] ?? [];
        $goodSpots = [];
        for ($i = 0; $i < 4; $i++) {
            $currentSpot = $goodSpotsInput[$i] ?? [];

            $title = trim((string)($currentSpot['title'] ?? ''));
            $imageUrl = trim((string)($currentSpot['image_url'] ?? ''));
            $linkUrl = trim((string)($currentSpot['link_url'] ?? ''));

            if ($request->hasFile("good_spots_files.$i")) {
                $path = $request->file("good_spots_files.$i")->store('home-settings/good-spots', 'public');
                $imageUrl = Storage::disk('public')->url($path);
            }

            $goodSpots[] = [
                'title' => $title,
                'subtitle' => trim((string)($currentSpot['subtitle'] ?? '')),
                'icon' => trim((string)($currentSpot['icon'] ?? '')),
                'image_url' => $imageUrl,
                'link_url' => $linkUrl,
            ];
        }

            $promotionItems = [];
            $promosInput = $validated['promotions']['items'] ?? [];
            if (is_array($promosInput)) {
                foreach ($promosInput as $promoIndex => $promo) {
                    $promoTitle = trim((string)($promo['title'] ?? ''));
                    if ($promoTitle === '') continue;

                    $imageUrl = trim((string)($promo['image_url'] ?? ''));
                    if ($request->hasFile("promotions_image_files.$promoIndex")) {
                        $path = $request->file("promotions_image_files.$promoIndex")->store('home-settings/promotions', 'public');
                        $imageUrl = Storage::disk('public')->url($path);
                    }

                    $displayType = trim((string)($promo['display_type'] ?? 'css'));
                    if (!in_array($displayType, ['css', 'image'], true)) {
                        $displayType = 'css';
                    }

                    $promotionItems[] = [
                        'badge_text'  => trim((string)($promo['badge_text'] ?? '')),
                        'badge_bg'    => trim((string)($promo['badge_bg'] ?? '#ef4444')),
                        'badge_color' => trim((string)($promo['badge_color'] ?? '#fff')),
                        'title'       => $promoTitle,
                        'text'        => trim((string)($promo['text'] ?? '')),
                        'style'       => trim((string)($promo['style'] ?? 'blue')),
                        'url'         => trim((string)($promo['url'] ?? '#')),
                        'display_type' => $displayType,
                        'background_color' => trim((string)($promo['background_color'] ?? '')),
                        'background_gradient' => trim((string)($promo['background_gradient'] ?? '')),
                        'image_url' => $imageUrl,
                        'overlay_enabled' => (bool)($promo['overlay_enabled'] ?? false),
                        'overlay_opacity' => max(0, min(1, (float)($promo['overlay_opacity'] ?? 0.35))),
                        'text_color' => trim((string)($promo['text_color'] ?? '#ffffff')),
                        'button_label' => trim((string)($promo['button_label'] ?? '')),
                        'locale' => [
                            'fr' => [
                                'badge' => trim((string) data_get($promo, 'locale.fr.badge', '')),
                                'title' => trim((string) data_get($promo, 'locale.fr.title', '')),
                                'description' => trim((string) data_get($promo, 'locale.fr.description', '')),
                            ],
                            'ar' => [
                                'badge' => trim((string) data_get($promo, 'locale.ar.badge', '')),
                                'title' => trim((string) data_get($promo, 'locale.ar.title', '')),
                                'description' => trim((string) data_get($promo, 'locale.ar.description', '')),
                            ],
                        ],
                    ];
                }
            }

            $payload = [
                'hero' => [
                    'type' => $validated['hero']['type'],
                    'image_url' => $heroImageUrl,
                    'video_url' => $heroVideoUrl,
                    'title' => $validated['hero']['title'],
                    'subtitle' => $validated['hero']['subtitle'] ?? '',
                    'cta_text' => $validated['hero']['cta_text'] ?? '',
                    'cta_url' => $validated['hero']['cta_url'] ?? '',
                    'overlay' => (float) $validated['hero']['overlay'],
                ],
                'sections' => array_merge([
                    'search' => (bool) $request->boolean('sections.search'),
                    'last_minute' => (bool) $request->boolean('sections.last_minute'),
                    'accommodations' => (bool) $request->boolean('sections.accommodations'),
                    'holiday_theme' => (bool) ($request->boolean('sections.holiday_theme') || $request->boolean('holiday_theme.enabled')),
                    'regions' => (bool) $request->boolean('sections.regions'),
                    'good_spots' => (bool) $request->boolean('sections.good_spots'),
                    'promotions' => (bool) $request->boolean('sections.promotions'),
                    'whatsapp_banner' => (bool) $request->boolean('sections.whatsapp_banner'),
                    'cruises' => (bool) $request->boolean('sections.cruises'),
                    'newsletter' => (bool) $request->boolean('sections.newsletter'),
                ], array_filter(
                    array_map(function ($v) { return (bool) $v; }, $request->input('sections', [])),
                    function ($k) { return str_starts_with((string) $k, 'custom_'); },
                    ARRAY_FILTER_USE_KEY
                )),
                'section_order' => $this->normalizeSectionOrder($request->input('section_order', [])),
                'custom_sections' => $this->normalizeCustomSections($request->input('custom_sections', [])),
                'search' => [
                    'shortcode' => trim((string)($validated['search']['shortcode'] ?? '[traveler_search]')),
                ],
                'last_minute' => [
                    'title' => $validated['last_minute']['title'],
                    'count' => (int) $validated['last_minute']['count'],
                    'featured_only' => (bool) $request->boolean('last_minute.featured_only', false),
                ],
                'accommodations' => [
                    'title' => trim((string)($validated['accommodations']['title'] ?? 'Découvrez des séjours uniques')),
                    'count' => (int) ($validated['accommodations']['count'] ?? 4),
                ],
                'holiday_theme' => $this->buildHolidayThemePayload($request, $validated),
                'regions' => $regions,
                'good_spots' => $goodSpots,
                'good_spots_title' => trim((string)($validated['good_spots_title'] ?? 'Les bons coins sur votre destination')),
                'promotions' => [
                    'title' => trim((string)($validated['promotions']['title'] ?? 'Destinations de ce mois')),
                    'items' => $promotionItems,
                ],
                'whatsapp_banner' => $this->buildWhatsAppBannerPayload($request, $validated),
                'cruises' => $this->buildCruisesPayload($request, $validated),
                'footer' => [
                    'col1_heading' => trim((string)($validated['footer']['col1_heading'] ?? 'En savoir plus')),
                    'col2_heading' => trim((string)($validated['footer']['col2_heading'] ?? 'Société')),
                    'legal_text' => trim((string)($validated['footer']['legal_text'] ?? '')),
                ],
            ];

            $optionValue = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $destinationsByRegionPayload = $this->buildDestinationsByRegionPayload($request);
            if ($destinationsByRegionPayload !== null) {
                $drJson = json_encode($destinationsByRegionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                Setting::setValue('destinations_by_region', $drJson);
                try {
                    $this->wpOptionsTable();
                    DB::connection('wp')
                        ->table('options')
                        ->updateOrInsert(
                            ['option_name' => 'aj_destinations_by_region'],
                            ['option_value' => $drJson, 'autoload' => 'no']
                        );
                } catch (Throwable $e) {
                    Log::warning('Could not mirror destinations_by_region to wp_options', ['error' => $e->getMessage()]);
                }
            }

            $this->wpOptionsTable();

            DB::connection('wp')
                ->table('options')
                ->updateOrInsert(
                    ['option_name' => 'aj_home_settings'],
                    ['option_value' => $optionValue, 'autoload' => 'no']
                );

            $writtenRaw = DB::connection('wp')
                ->table('options')
                ->where('option_name', 'aj_home_settings')
                ->value('option_value');

            Log::info('WP aj_home_settings saved from Laravel', [
                'option_name' => 'aj_home_settings',
                'db' => config('database.connections.wp.database'),
                'prefix' => config('database.connections.wp.prefix'),
                'written_option_value' => $writtenRaw,
            ]);

            Log::info('Home page settings update finished', [
                'has_video_upload' => $hasVideoUpload,
            ]);

            return redirect()
                ->route('admin.settings.home-page.edit', ['tab' => 'content'])
                ->with('success', 'Home page settings enregistrés. Rafraîchissez la home WordPress pour voir les changements.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Home page settings update failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'has_video_upload' => $request->hasFile('hero_video_file'),
                'trace' => $e->getTraceAsString(),
            ]);

            $err = $e->getMessage();
            $errorDisplay = 'Echec enregistrement : ' . $err;
            if (stripos($err, 'video') !== false || stripos($err, 'upload') !== false || stripos($err, 'file') !== false || stripos($err, 'storage') !== false || stripos($err, 'size') !== false) {
                $errorDisplay .= ' Pour la video, utilisez une URL (YouTube/Vimeo) ou augmentez upload_max_filesize/post_max_size.';
            }

            return redirect()
                ->route('admin.settings.home-page.edit', ['tab' => 'content'])
                ->withInput()
                ->with('error', $errorDisplay);
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * Header settings – stored in Laravel `settings` table (key=wp_header)
     * AND mirrored to wp_options (key=aj_header_settings) for WP access.
     * Header settings managed from Laravel admin /admin/settings/home-page
     * ────────────────────────────────────────────────────────────────── */

    private const HEADER_DEFAULTS = [
        'enabled'               => true,
        'topbar_enabled'        => true,
        'phone'                 => '+212 5 39 32 38 74',
        'email'                 => 'contact@ajinsafro.ma',
        'socials'               => [
            'facebook'  => '#',
            'twitter'   => '#',
            'instagram' => '#',
            'youtube'   => '#',
            'linkedin'  => '#',
        ],
        'navbar_enabled'        => true,
        'logo_url'              => '',
        'show_auth_links'       => true,
        'login_url'             => '/login',
        'signup_url'            => '/register',
        'menu_source'           => 'wp_menu',
        'wp_menu_location'      => 'primary',
        'show_header_sitewide'  => false,
        'show_footer_sitewide'  => true,
        'links'                 => [],
        'lowcost_enabled'       => true,
        'lowcost_text'          => 'Formule low cost',
        'lowcost_url'           => '#',
    ];

    public function updateHeader(Request $request)
    {
        $validated = $request->validate([
            'header.enabled'            => ['nullable'],
            'header.topbar_enabled'     => ['nullable'],
            'header.phone'              => ['nullable', 'string', 'max:60'],
            'header.email'              => ['nullable', 'email', 'max:120'],
            'header.socials.facebook'   => ['nullable', 'url', 'max:500'],
            'header.socials.twitter'    => ['nullable', 'url', 'max:500'],
            'header.socials.instagram'  => ['nullable', 'url', 'max:500'],
            'header.socials.youtube'    => ['nullable', 'url', 'max:500'],
            'header.socials.linkedin'   => ['nullable', 'url', 'max:500'],
            'header.navbar_enabled'     => ['nullable'],
            'header.logo_url'           => ['nullable', 'string', 'max:2048'],
            'header.logo_file'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:5120'],
            'header.show_auth_links'    => ['nullable'],
            'header.login_url'          => ['nullable', 'string', 'max:500'],
            'header.signup_url'         => ['nullable', 'string', 'max:500'],
            'header.menu_source'        => ['required', Rule::in(['wp_menu', 'laravel_links'])],
            'header.wp_menu_location'   => ['nullable', 'string', 'max:80'],
            'header.links'              => ['nullable', 'array'],
            'header.links.*.label'        => ['nullable', 'string', 'max:120'],
            'header.links.*.url'          => ['nullable', 'string', 'max:500'],
            'header.links.*.icon'         => ['nullable', 'string', 'max:100'],
            'header.links.*.order'        => ['nullable', 'integer', 'min:0'],
            'header.links.*.active'       => ['nullable'],
            'header.links.*.highlight'    => ['nullable'],
            'header.links.*.children'     => ['nullable', 'array'],
            'header.links.*.children.*.label' => ['nullable', 'string', 'max:120'],
            'header.links.*.children.*.url'   => ['nullable', 'string', 'max:500'],
            'header.links.*.children.*.icon'  => ['nullable', 'string', 'max:100'],
            'header.links.*.children.*.order' => ['nullable', 'integer', 'min:0'],
            'header.links.*.children_json'    => ['nullable', 'string', 'max:2000'],
            'header.show_header_sitewide'     => ['nullable'],
            'header.show_footer_sitewide'     => ['nullable'],
            'header.lowcost_enabled'    => ['nullable'],
            'header.lowcost_text'       => ['nullable', 'string', 'max:100'],
            'header.lowcost_url'        => ['nullable', 'string', 'max:500'],
        ]);

        $h = $validated['header'] ?? [];

        $logoUrl = trim((string) ($h['logo_url'] ?? ''));
        if ($request->hasFile('header.logo_file')) {
            $path = $request->file('header.logo_file')->store('home-settings/header', 'public');
            $logoUrl = Storage::disk('public')->url($path);
        }

        $links = [];
        $linkIndex = 0;
        foreach (($h['links'] ?? []) as $link) {
            $label = trim((string) ($link['label'] ?? ''));
            $url   = trim((string) ($link['url'] ?? ''));
            if ($label === '' && $url === '') {
                continue;
            }
            $children = [];
            $childrenRaw = $link['children'] ?? [];
            if (!is_array($childrenRaw)) {
                $childrenRaw = [];
            }
            $childrenJson = trim((string) ($link['children_json'] ?? ''));
            if ($childrenJson !== '') {
                $parsed = json_decode($childrenJson, true);
                if (is_array($parsed)) {
                    $childrenRaw = array_merge($childrenRaw, $parsed);
                }
            }
            $childIndex = 0;
            foreach ($childrenRaw as $child) {
                $cl = trim((string) ($child['label'] ?? ''));
                $cu = trim((string) ($child['url'] ?? ''));
                if ($cl !== '' || $cu !== '') {
                    $children[] = [
                        'label' => $cl,
                        'url'   => $cu,
                        'icon'  => trim((string) ($child['icon'] ?? '')),
                        'order' => isset($child['order']) ? (int) $child['order'] : ($childIndex + 1),
                    ];
                    $childIndex++;
                }
            }
            // Sort children by order
            usort($children, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
            // Renumber children
            $childOrder = 1;
            foreach ($children as &$c) {
                $c['order'] = $childOrder++;
            }
            unset($c);

            $links[] = [
                'label'     => $label,
                'url'       => $url,
                'icon'      => trim((string) ($link['icon'] ?? '')),
                'order'     => isset($link['order']) ? (int) $link['order'] : ($linkIndex + 1),
                'active'    => !empty($link['active']),
                'highlight' => !empty($link['highlight']),
                'children'  => $children,
            ];
            $linkIndex++;
        }

        // Sort links by order
        usort($links, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        // Renumber links
        $linkOrder = 1;
        foreach ($links as &$l) {
            $l['order'] = $linkOrder++;
        }
        unset($l);

        $payload = [
            'enabled'          => $request->boolean('header.enabled'),
            'topbar_enabled'   => $request->boolean('header.topbar_enabled'),
            'phone'            => trim((string) ($h['phone'] ?? '')),
            'email'            => trim((string) ($h['email'] ?? '')),
            'socials'          => [
                'facebook'  => trim((string) ($h['socials']['facebook'] ?? '')),
                'twitter'   => trim((string) ($h['socials']['twitter'] ?? '')),
                'instagram' => trim((string) ($h['socials']['instagram'] ?? '')),
                'youtube'   => trim((string) ($h['socials']['youtube'] ?? '')),
                'linkedin'  => trim((string) ($h['socials']['linkedin'] ?? '')),
            ],
            'navbar_enabled'   => $request->boolean('header.navbar_enabled'),
            'logo_url'         => $logoUrl,
            'show_auth_links'  => $request->boolean('header.show_auth_links'),
            'login_url'        => trim((string) ($h['login_url'] ?? '/login')),
            'signup_url'       => trim((string) ($h['signup_url'] ?? '/register')),
            'menu_source'           => $h['menu_source'] ?? 'wp_menu',
            'wp_menu_location'      => trim((string) ($h['wp_menu_location'] ?? 'primary')),
            'show_header_sitewide'  => $request->boolean('header.show_header_sitewide'),
            'show_footer_sitewide'  => $request->boolean('header.show_footer_sitewide'),
            'links'                 => $links,
            'lowcost_enabled'       => $request->boolean('header.lowcost_enabled'),
            'lowcost_text'          => trim((string) ($h['lowcost_text'] ?? 'Formule low cost')),
            'lowcost_url'           => trim((string) ($h['lowcost_url'] ?? '#')),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Setting::setValue('wp_header', $json);

        $wpWriteOk = false;
        try {
            $this->wpOptionsTable();

            DB::connection('wp')
                ->table('options')
                ->updateOrInsert(
                    ['option_name' => 'aj_header_settings'],
                    ['option_value' => $json, 'autoload' => 'no']
                );

            DB::connection('wp')
                ->table('options')
                ->updateOrInsert(
                    ['option_name' => 'aj_header_settings_ts'],
                    ['option_value' => (string) time(), 'autoload' => 'no']
                );

            $wpWriteOk = true;

            Log::info('Header settings written to wp_options', [
                'json_length' => strlen($json),
                'timestamp'   => time(),
            ]);
        } catch (Throwable $e) {
            Log::error('Could not mirror header settings to wp_options', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $msg = $wpWriteOk
            ? 'Header settings enregistrés et synchronisés avec WordPress.'
            : 'Header settings enregistrés localement, mais la synchronisation WordPress a échoué. Vérifiez la connexion WP DB.';

        return redirect()
            ->route('admin.settings.home-page.edit', ['tab' => 'header'])
            ->with($wpWriteOk ? 'success' : 'error', $msg);
    }

    private function readHeaderSettings(): array
    {
        $raw = Setting::getValue('wp_header');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_replace_recursive(self::HEADER_DEFAULTS, $decoded);
            }
        }

        return self::HEADER_DEFAULTS;
    }

    private function readHomeSettings(): array
    {
        $defaults = [
            'hero' => [
                'type' => 'image',
                'image_url' => '',
                'video_url' => '',
                'title' => 'Partir en vacances au meilleur prix !',
                'subtitle' => '',
                'cta_text' => '',
                'cta_url' => '',
                'overlay' => 0.4,
            ],
            'sections' => [
                'search' => true,
                'last_minute' => true,
                'accommodations' => true,
                'holiday_theme' => true,
                'regions' => true,
                'good_spots' => true,
                'promotions' => true,
                'whatsapp_banner' => false,
                'cruises' => false,
                'newsletter' => true,
            ],
            'section_order' => ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'promotions', 'whatsapp_banner', 'cruises', 'newsletter'],
            'custom_sections' => [],
            'search' => [
                'shortcode' => '[traveler_search]',
            ],
            'last_minute' => [
                'title' => 'Cap sur les tendances du moment',
                'count' => 4,
                'featured_only' => false,
            ],
            'accommodations' => [
                'title' => 'Découvrez des séjours uniques',
                'count' => 4,
            ],
            'holiday_theme' => [
                'enabled' => false,
                'eyebrow' => 'Voyages par theme',
                'title_line_1' => '',
                'title_line_2' => '',
                'title_line_3' => '',
                'subtitle' => '',
                'left_image_url' => '',
                'deco_image_url' => '',
                'button_text' => '',
                'button_url' => '',
                'items' => [],
            ],
            'regions' => [],
            'good_spots' => [
                ['title' => 'Restaurants', 'subtitle' => 'Où manger ?', 'icon' => 'fas fa-utensils', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Loisirs', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-icons', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Que faire ?', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-map-marked-alt', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Shopping', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-shopping-bag', 'image_url' => '', 'link_url' => ''],
            ],
            'good_spots_title' => 'Les bons coins sur votre destination',
            'promotions' => [
                'title' => 'Destinations de ce mois',
                'items' => [],
            ],
            'whatsapp_banner' => [
                'enabled' => false,
                'title' => 'JOIN OUR WHATSAPP CHANNEL FOR THE LATEST TRAVEL UPDATES',
                'subtitle' => 'Stay informed with satguru travel',
                'features' => ['Exclusive travel packages', 'Latest news and updates', 'Special offers and promotions'],
                'button_text' => 'JOIN NOW',
                'button_url' => '#',
                'qr_code_url' => '',
            ],
            'cruises' => [
                'enabled' => false,
                'title' => 'CROISIÈRES',
                'image_url' => '',
                'button_text' => 'Découvrir',
                'button_url' => '#',
            ],
            'footer' => [
                'col1_heading' => 'En savoir plus',
                'col2_heading' => 'Société',
                'legal_text' => "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU",
            ],
        ];

        $this->wpOptionsTable();

        $raw = DB::connection('wp')
            ->table('options')
            ->where('option_name', 'aj_home_settings')
            ->value('option_value');

        if (!is_string($raw) || $raw === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return $defaults;
        }

        $settings = array_replace_recursive($defaults, $decoded);

        if (!isset($settings['regions']) || !is_array($settings['regions'])) {
            $settings['regions'] = [];
        }

        if (!isset($settings['good_spots']) || !is_array($settings['good_spots'])) {
            $settings['good_spots'] = $defaults['good_spots'];
        }

        while (count($settings['good_spots']) < 4) {
            $settings['good_spots'][] = $defaults['good_spots'][count($settings['good_spots'])];
        }

        return $settings;
    }

    /* ──────────────────────────────────────────────────────────────────
     * Destinations par région — Laravel settings (key=destinations_by_region)
     * + mirrored to wp_options (aj_destinations_by_region) for WP (grille 2x4).
     * ────────────────────────────────────────────────────────────────── */

    private const DESTINATIONS_BY_REGION_DEFAULTS = [
        'enabled' => true,
        'title'   => 'Nos destinations',
        'items'   => [
            ['label' => 'CAP NORD', 'image_url' => '', 'link_url' => '', 'order' => 1],
            ['label' => 'MAROC MÉDITERRANÉE', 'image_url' => '', 'link_url' => '', 'order' => 2],
            ['label' => 'MAROC CENTRE', 'image_url' => '', 'link_url' => '', 'order' => 3],
            ['label' => 'ATLAS ET VALLÉES', 'image_url' => '', 'link_url' => '', 'order' => 4],
            ['label' => 'CENTRE ATLANTIQUE', 'image_url' => '', 'link_url' => '', 'order' => 5],
            ['label' => 'MARRAKECH ATLANTIQUE', 'image_url' => '', 'link_url' => '', 'order' => 6],
            ['label' => 'SOUSS SAHARA ATLANTIQUE', 'image_url' => '', 'link_url' => '', 'order' => 7],
            ['label' => 'GRAND-SUD ATLANTIQUE', 'image_url' => '', 'link_url' => '', 'order' => 8],
        ],
    ];

    private function readDestinationsByRegion(): array
    {
        $raw = Setting::getValue('destinations_by_region');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded['items'] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                return [
                    'enabled' => (bool) ($decoded['enabled'] ?? true),
                    'title'   => (string) ($decoded['title'] ?? self::DESTINATIONS_BY_REGION_DEFAULTS['title']),
                    'items'   => array_values($items),
                ];
            }
        }
        return self::DESTINATIONS_BY_REGION_DEFAULTS;
    }

    private function buildDestinationsByRegionPayload(Request $request): ?array
    {
        $input = $request->input('destinations_by_region', []);
        if (!is_array($input)) {
            return null;
        }
        $itemsInput = $input['items'] ?? [];
        if (!is_array($itemsInput)) {
            $itemsInput = [];
        }
        $filtered = [];
        foreach ($itemsInput as $idx => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $imageUrl = trim((string) ($item['image_url'] ?? ''));
            if ($request->hasFile("destinations_by_region_files.{$idx}")) {
                $path = $request->file("destinations_by_region_files.{$idx}")->store('home-settings/destinations-by-region', 'public');
                $imageUrl = Storage::disk('public')->url($path);
            }
            $order = isset($item['order']) ? (int) $item['order'] : ($idx + 1);
            $filtered[] = [
                'label'     => $label,
                'image_url' => $imageUrl,
                'link_url'  => trim((string) ($item['link_url'] ?? '')),
                'order'     => $order,
            ];
        }
        usort($filtered, fn ($a, $b) => $a['order'] <=> $b['order']);
        $order = 1;
        foreach ($filtered as &$row) {
            $row['order'] = $order++;
        }
        unset($row);
        return [
            'enabled' => $request->boolean('destinations_by_region.enabled'),
            'title'   => trim((string) ($input['title'] ?? self::DESTINATIONS_BY_REGION_DEFAULTS['title'])),
            'items'   => array_values($filtered),
        ];
    }

    private const BUILTIN_SECTIONS = ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'promotions', 'whatsapp_banner', 'cruises', 'newsletter'];

    private function normalizeSectionOrder(array $input): array
    {
        $order = [];
        foreach ($input as $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $order[] = $key;
        }
        if (empty($order)) {
            return self::BUILTIN_SECTIONS;
        }
        return array_values(array_unique($order));
    }

    private function normalizeCustomSections(array $input): array
    {
        $out = [];
        foreach ($input as $id => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = preg_replace('/[^a-z0-9_]/', '', (string) $id);
            if ($id === '') {
                continue;
            }
            if (strpos($id, 'custom_') !== 0) {
                $id = 'custom_' . $id;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $content = trim((string) ($item['content'] ?? ''));
            $out[$id] = ['title' => $title, 'content' => $content];
        }
        return $out;
    }

    private function buildWhatsAppBannerPayload(Request $request, array $validated): array
    {
        $current = $this->readHomeSettings();
        $qrCodeUrl = trim((string) ($validated['whatsapp_banner']['qr_code_url'] ?? ($current['whatsapp_banner']['qr_code_url'] ?? '')));
        
        if ($request->hasFile('whatsapp_banner_qr_file')) {
            try {
                $path = $request->file('whatsapp_banner_qr_file')->store('home-settings/whatsapp', 'public');
                $qrCodeUrl = Storage::disk('public')->url($path);
            } catch (Throwable $e) {
                Log::warning('WhatsApp QR code upload failed', ['message' => $e->getMessage()]);
            }
        }

        $features = [];
        $featuresInput = $validated['whatsapp_banner']['features'] ?? [];
        if (is_array($featuresInput)) {
            foreach ($featuresInput as $feature) {
                $f = trim((string) $feature);
                if ($f !== '') {
                    $features[] = $f;
                }
            }
        }

        return [
            'enabled' => (bool) $request->boolean('whatsapp_banner.enabled'),
            'title' => trim((string) ($validated['whatsapp_banner']['title'] ?? 'JOIN OUR WHATSAPP CHANNEL FOR THE LATEST TRAVEL UPDATES')),
            'subtitle' => trim((string) ($validated['whatsapp_banner']['subtitle'] ?? 'Stay informed with satguru travel')),
            'features' => $features,
            'button_text' => trim((string) ($validated['whatsapp_banner']['button_text'] ?? 'JOIN NOW')),
            'button_url' => trim((string) ($validated['whatsapp_banner']['button_url'] ?? '#')),
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    private function buildHolidayThemePayload(Request $request, array $validated): array
    {
        $current = $this->readHomeSettings();
        $input = $validated['holiday_theme'] ?? [];

        $leftImageUrl = trim((string) ($input['left_image_url'] ?? data_get($current, 'holiday_theme.left_image_url', '')));
        $decoImageUrl = trim((string) ($input['deco_image_url'] ?? data_get($current, 'holiday_theme.deco_image_url', '')));

        if ($request->hasFile('holiday_theme_left_image_file')) {
            $path = $request->file('holiday_theme_left_image_file')->store('home-settings/holiday-theme', 'public');
            $leftImageUrl = Storage::disk('public')->url($path);
        }
        if ($request->hasFile('holiday_theme_deco_image_file')) {
            $path = $request->file('holiday_theme_deco_image_file')->store('home-settings/holiday-theme', 'public');
            $decoImageUrl = Storage::disk('public')->url($path);
        }

        $items = [];
        $itemsInput = $input['items'] ?? [];
        if (is_array($itemsInput)) {
            foreach ($itemsInput as $idx => $item) {
                $title = trim((string) ($item['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $imageUrl = trim((string) ($item['image_url'] ?? ''));
                if ($request->hasFile("holiday_theme_item_files.$idx")) {
                    $path = $request->file("holiday_theme_item_files.$idx")->store('home-settings/holiday-theme/items', 'public');
                    $imageUrl = Storage::disk('public')->url($path);
                }
                $items[] = [
                    'title' => $title,
                    'image_url' => $imageUrl,
                    'button_text' => trim((string) ($item['button_text'] ?? 'Voir plus')),
                    'button_url' => trim((string) ($item['button_url'] ?? '#')),
                    'tags' => trim((string) ($item['tags'] ?? '')),
                    'active' => (bool) ($item['active'] ?? true),
                    'order' => isset($item['order']) ? (int) $item['order'] : (int) $idx,
                ];
            }
        }

        usort($items, static fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));

        return [
            'enabled' => (bool) $request->boolean('holiday_theme.enabled'),
            'eyebrow' => trim((string) ($input['eyebrow'] ?? 'Voyages par theme')),
            'title_line_1' => trim((string) ($input['title_line_1'] ?? '')),
            'title_line_2' => trim((string) ($input['title_line_2'] ?? '')),
            'title_line_3' => trim((string) ($input['title_line_3'] ?? '')),
            'subtitle' => trim((string) ($input['subtitle'] ?? '')),
            'left_image_url' => $leftImageUrl,
            'deco_image_url' => $decoImageUrl,
            'button_text' => trim((string) ($input['button_text'] ?? '')),
            'button_url' => trim((string) ($input['button_url'] ?? '')),
            'items' => array_values($items),
        ];
    }

    private function buildCruisesPayload(Request $request, array $validated): array
    {
        $current = $this->readHomeSettings();
        $imageUrl = trim((string) ($validated['cruises']['image_url'] ?? ($current['cruises']['image_url'] ?? '')));
        
        if ($request->hasFile('cruises_image_file')) {
            try {
                $path = $request->file('cruises_image_file')->store('home-settings/cruises', 'public');
                $imageUrl = Storage::disk('public')->url($path);
            } catch (Throwable $e) {
                Log::warning('Cruises image upload failed', ['message' => $e->getMessage()]);
            }
        }

        return [
            'enabled' => (bool) $request->boolean('cruises.enabled'),
            'title' => trim((string) ($validated['cruises']['title'] ?? 'CROISIÈRES')),
            'image_url' => $imageUrl,
            'button_text' => trim((string) ($validated['cruises']['button_text'] ?? 'Découvrir')),
            'button_url' => trim((string) ($validated['cruises']['button_url'] ?? '#')),
        ];
    }

    private function wpOptionsTable(): string
    {
        $optionsTable = 'options';

        try {
            $prefix = (string) (config('database.connections.wp.prefix') ?: 'wp_');
            $prefix = trim($prefix, " \t\n\r\0\x0B`");
            $prefix = rtrim($prefix, '_') . '_';
            $physicalTable = $prefix . 'options';
            $tablePattern = addcslashes($physicalTable, "\\_%");

            $tableRows = DB::connection('wp')->select("SHOW TABLES LIKE '{$tablePattern}'");

            if (empty($tableRows)) {
                $dbRow = DB::connection('wp')->selectOne('SELECT DATABASE() as db');
                $db = (string) ($dbRow->db ?? 'unknown');

                throw new RuntimeException(
                    "WP options table not found: {$physicalTable} in DB {$db}. Check WP_DB_DATABASE and WP_DB_PREFIX against wp-config.php. Prefix used: {$prefix}"
                );
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to validate WordPress options table: ' . $e->getMessage(), 0, $e);
        }

        return $optionsTable;
    }
}
