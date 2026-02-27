<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.settings.home-page.index', [
            'settings' => $settings,
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
                'sections.regions' => ['nullable', 'boolean'],
                'sections.good_spots' => ['nullable', 'boolean'],

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
                'good_spots.*.image_url' => ['nullable', 'url', 'max:2048'],
                'good_spots.*.link_url' => ['nullable', 'url', 'max:2048'],
                'good_spots_files.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
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

                if (!$uploadedVideo instanceof UploadedFile || !$uploadedVideo->isValid()) {
                    $errorMessage = $uploadedVideo instanceof UploadedFile
                        ? $uploadedVideo->getErrorMessage()
                        : 'fichier manquant';

                    throw new RuntimeException('Upload vidéo invalide. Détail: ' . $errorMessage);
                }

                Log::info('Home page hero video upload processing started', [
                    'size' => $uploadedVideo->getSize(),
                    'original_name' => $uploadedVideo->getClientOriginalName(),
                    'is_valid' => $uploadedVideo->isValid(),
                    'mime' => $uploadedVideo->getMimeType(),
                ]);

                $baseName = pathinfo($uploadedVideo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeBaseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $baseName);
                $safeBaseName = trim((string) $safeBaseName, '-_');
                if ($safeBaseName === '') {
                    $safeBaseName = 'hero-video';
                }

                $extension = strtolower((string) $uploadedVideo->getClientOriginalExtension());
                if ($extension === '') {
                    $extension = 'mp4';
                }

                $fileName = sprintf(
                    '%s-%s-%s.%s',
                    $safeBaseName,
                    now()->format('YmdHis'),
                    bin2hex(random_bytes(4)),
                    $extension
                );

                Log::info('Home page hero video upload file resolved', [
                    'filename' => $fileName,
                ]);

                $heroVideoPath = Storage::disk('public')->putFileAs('home-settings/hero', $uploadedVideo, $fileName);
                if ($heroVideoPath === false) {
                    throw new RuntimeException('Video file storage failed.');
                }

                $heroVideoUrl = Storage::disk('public')->url($heroVideoPath);

                Log::info('Home page hero video upload processing completed', [
                    'stored_path' => $heroVideoPath,
                    'public_url' => $heroVideoUrl,
                ]);
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
                'image_url' => $imageUrl,
                'link_url' => $linkUrl,
            ];
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
                'sections' => [
                    'search' => (bool) $request->boolean('sections.search', true),
                    'last_minute' => (bool) $request->boolean('sections.last_minute', true),
                    'regions' => (bool) $request->boolean('sections.regions', true),
                    'good_spots' => (bool) $request->boolean('sections.good_spots', true),
                ],
                'search' => [
                    'shortcode' => trim((string)($validated['search']['shortcode'] ?? '[traveler_search]')),
                ],
                'last_minute' => [
                    'title' => $validated['last_minute']['title'],
                    'count' => (int) $validated['last_minute']['count'],
                    'featured_only' => (bool) $request->boolean('last_minute.featured_only', false),
                ],
                'regions' => $regions,
                'good_spots' => $goodSpots,
            ];

            $optionValue = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
                ->back()
                ->with('success', 'Home page settings enregistrés. Rafraîchissez la home WordPress pour voir les changements.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Home page settings update failed', [
                'message' => $e->getMessage(),
                'has_video_upload' => $request->hasFile('hero_video_file'),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Échec de l'enregistrement. Si l’upload mp4 échoue, augmentez upload_max_filesize/post_max_size/max_execution_time ou utilisez un lien vidéo.");
        }
    }

    private function readHomeSettings(): array
    {
        $defaults = [
            'hero' => [
                'type' => 'image',
                'image_url' => '',
                'video_url' => '',
                'title' => 'Découvrez le Maroc',
                'subtitle' => 'Voyages, hébergements et activités au meilleur prix',
                'cta_text' => 'Voir les offres',
                'cta_url' => '',
                'overlay' => 0.35,
            ],
            'sections' => [
                'search' => true,
                'last_minute' => true,
                'regions' => true,
                'good_spots' => true,
            ],
            'search' => [
                'shortcode' => '[traveler_search]',
            ],
            'last_minute' => [
                'title' => 'Offres de dernière minute',
                'count' => 6,
                'featured_only' => false,
            ],
            'regions' => [],
            'good_spots' => [
                ['title' => 'Restaurants', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Loisirs', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Que faire ?', 'image_url' => '', 'link_url' => ''],
                ['title' => 'Shopping', 'image_url' => '', 'link_url' => ''],
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
