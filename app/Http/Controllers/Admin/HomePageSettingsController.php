<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $validated = $request->validate([
            'hero.type' => ['required', Rule::in(['image', 'video'])],
            'hero.image_url' => ['nullable', 'url', 'max:2048'],
            'hero.video_url' => ['nullable', 'url', 'max:2048'],
            'hero.image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'hero.video_file' => ['nullable', 'file', 'mimes:mp4', 'max:102400'],
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
        ]);

        $current = $this->readHomeSettings();

        $heroImageUrl = $validated['hero']['image_url'] ?? ($current['hero']['image_url'] ?? '');
        if ($request->hasFile('hero.image_file')) {
            $heroImagePath = $request->file('hero.image_file')->store('home-settings/hero', 'public');
            $heroImageUrl = Storage::disk('public')->url($heroImagePath);
        }

        $heroVideoUrl = $validated['hero']['video_url'] ?? ($current['hero']['video_url'] ?? '');
        if ($request->hasFile('hero.video_file')) {
            $heroVideoPath = $request->file('hero.video_file')->store('home-settings/hero', 'public');
            $heroVideoUrl = Storage::disk('public')->url($heroVideoPath);
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

        $prefix = env('WP_DB_PREFIX', env('WP_TABLE_PREFIX', 'cFdgeZ_'));
        $optionsTable = $prefix . 'options';

        DB::connection('wp')
            ->table($optionsTable)
            ->updateOrInsert(
                ['option_name' => 'aj_home_settings'],
                ['option_value' => $optionValue, 'autoload' => 'no']
            );

        return redirect()
            ->route('admin.settings.home-page.edit')
            ->with('success', 'Home page settings enregistrés. Rafraîchissez la home WordPress pour voir les changements.');
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

        $prefix = env('WP_DB_PREFIX', env('WP_TABLE_PREFIX', 'cFdgeZ_'));
        $optionsTable = $prefix . 'options';

        $raw = DB::connection('wp')
            ->table($optionsTable)
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
}
