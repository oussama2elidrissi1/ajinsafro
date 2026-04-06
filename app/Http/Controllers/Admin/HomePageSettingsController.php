<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomePageSettingsController extends Controller
{
	private const HOME_OPTION = 'aj_home_settings';
	private const HEADER_OPTION = 'aj_header_settings';
	private const HEADER_TS_OPTION = 'aj_header_settings_ts';
	private const DBR_OPTION = 'aj_destinations_by_region';

	public function edit(Request $request): View
	{
		$settings = $this->readWpJsonOption(self::HOME_OPTION, $this->defaultHomeSettings());
		$settings = $this->normalizeHomeSettings($settings);

		$header = $this->readWpJsonOption(self::HEADER_OPTION, $this->defaultHeaderSettings());
		$header = $this->normalizeHeaderSettings($header);

		$destinationsByRegion = $this->readWpJsonOption(self::DBR_OPTION, $this->defaultDestinationsByRegion());
		$destinationsByRegion = $this->normalizeDestinationsByRegion($destinationsByRegion);

		$tab = in_array((string) $request->query('tab', 'header'), ['header', 'content'], true)
			? (string) $request->query('tab', 'header')
			: 'header';

		return view('admin.settings.home-page.index', [
			'settings' => $settings,
			'header' => $header,
			'destinationsByRegion' => $destinationsByRegion,
			'tab' => $tab,
		]);
	}
	

	public function updateHeader(Request $request): RedirectResponse
	{
		$header = $this->readWpJsonOption(self::HEADER_OPTION, $this->defaultHeaderSettings());
		$header = $this->normalizeHeaderSettings($header);

		$incoming = $request->input('header', []);
		$incoming = is_array($incoming) ? $incoming : [];

		$header['enabled'] = $this->truthy($incoming['enabled'] ?? false);
		$header['show_header_sitewide'] = $this->truthy($incoming['show_header_sitewide'] ?? false);
		$header['show_footer_sitewide'] = $this->truthy($incoming['show_footer_sitewide'] ?? false);
		$header['topbar_enabled'] = $this->truthy($incoming['topbar_enabled'] ?? false);
		$header['navbar_enabled'] = $this->truthy($incoming['navbar_enabled'] ?? false);
		$header['show_auth_links'] = $this->truthy($incoming['show_auth_links'] ?? false);
		$header['lowcost_enabled'] = $this->truthy($incoming['lowcost_enabled'] ?? true);

		$header['phone'] = $this->cleanText($incoming['phone'] ?? '', 100);
		$header['email'] = $this->cleanText($incoming['email'] ?? '', 255);
		$header['login_url'] = $this->cleanUrl($incoming['login_url'] ?? '/login', '/login');
		$header['signup_url'] = $this->cleanUrl($incoming['signup_url'] ?? '/register', '/register');
		$header['menu_source'] = ($incoming['menu_source'] ?? 'wp_menu') === 'laravel_links' ? 'laravel_links' : 'wp_menu';
		$header['wp_menu_location'] = $this->cleanText($incoming['wp_menu_location'] ?? 'primary', 50);
		$header['lowcost_text'] = $this->cleanText($incoming['lowcost_text'] ?? 'Formule low cost', 80);
		$header['lowcost_url'] = $this->cleanUrl($incoming['lowcost_url'] ?? '#', '#');

		$socials = is_array($incoming['socials'] ?? null) ? $incoming['socials'] : [];
		$header['socials'] = [
			'facebook' => $this->cleanUrl($socials['facebook'] ?? '#', '#'),
			'twitter' => $this->cleanUrl($socials['twitter'] ?? '#', '#'),
			'instagram' => $this->cleanUrl($socials['instagram'] ?? '#', '#'),
			'youtube' => $this->cleanUrl($socials['youtube'] ?? '#', '#'),
			'linkedin' => $this->cleanUrl($socials['linkedin'] ?? '#', '#'),
		];

		if ($request->hasFile('header.logo_file')) {
			$path = $request->file('header.logo_file')?->store('front/header', 'public');
			if (is_string($path) && $path !== '') {
				$header['logo_url'] = $this->publicStorageUrl($path);
			}
		} else {
			$header['logo_url'] = $this->cleanUrl($incoming['logo_url'] ?? '', $header['logo_url'] ?? '');
		}

		$header['links'] = [];
		$links = is_array($incoming['links'] ?? null) ? $incoming['links'] : [];
		foreach ($links as $link) {
			if (!is_array($link)) {
				continue;
			}

			$label = $this->cleanText($link['label'] ?? '', 80);
			$url = $this->cleanUrl($link['url'] ?? '', '');
			if ($label === '' && $url === '') {
				continue;
			}

			$children = [];
			foreach ((is_array($link['children'] ?? null) ? $link['children'] : []) as $child) {
				if (!is_array($child)) {
					continue;
				}
				$childLabel = $this->cleanText($child['label'] ?? '', 80);
				$childUrl = $this->cleanUrl($child['url'] ?? '', '');
				if ($childLabel === '' && $childUrl === '') {
					continue;
				}
				$children[] = [
					'label' => $childLabel,
					'url' => $childUrl,
					'icon' => $this->cleanText($child['icon'] ?? '', 80),
					'order' => (int) ($child['order'] ?? count($children) + 1),
				];
			}

			usort($children, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
			foreach ($children as $idx => &$child) {
				$child['order'] = $idx + 1;
			}
			unset($child);

			$header['links'][] = [
				'label' => $label,
				'url' => $url,
				'icon' => $this->cleanText($link['icon'] ?? '', 80),
				'active' => $this->truthy($link['active'] ?? true),
				'order' => (int) ($link['order'] ?? count($header['links']) + 1),
				'children' => $children,
			];
		}

		usort($header['links'], static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
		foreach ($header['links'] as $idx => &$link) {
			$link['order'] = $idx + 1;
		}
		unset($link);

		$this->writeWpJsonOption(self::HEADER_OPTION, $header);
		$this->writeWpOption(self::HEADER_TS_OPTION, (string) now()->timestamp);

		return redirect()
			->route('admin.settings.home-page.edit', ['tab' => 'header'])
			->with('success', 'Header enregistré et synchronisé vers WordPress.');
	}

	public function update(Request $request): RedirectResponse
	{
		$settings = $this->readWpJsonOption(self::HOME_OPTION, $this->defaultHomeSettings());
		$settings = $this->normalizeHomeSettings($settings);

		$hero = is_array($request->input('hero', [])) ? $request->input('hero', []) : [];
		$settings['hero']['type'] = ($hero['type'] ?? 'image') === 'video' ? 'video' : 'image';
		$settings['hero']['title'] = $this->cleanText($hero['title'] ?? $settings['hero']['title'], 255);
		$settings['hero']['subtitle'] = $this->cleanText($hero['subtitle'] ?? '', 500);
		$settings['hero']['cta_text'] = $this->cleanText($hero['cta_text'] ?? '', 120);
		$settings['hero']['cta_url'] = $this->cleanUrl($hero['cta_url'] ?? '', '');
		$settings['hero']['overlay'] = $this->clampFloat($hero['overlay'] ?? $settings['hero']['overlay'], 0.0, 1.0, 0.4);

		if ($request->hasFile('hero.image_file')) {
			$path = $request->file('hero.image_file')?->store('front/home/hero', 'public');
			if (is_string($path) && $path !== '') {
				$settings['hero']['image_url'] = $this->publicStorageUrl($path);
			}
		} else {
			$settings['hero']['image_url'] = $this->cleanUrl($hero['image_url'] ?? $settings['hero']['image_url'], $settings['hero']['image_url']);
		}

		if ($request->hasFile('hero_video_file')) {
			$path = $request->file('hero_video_file')?->store('front/home/hero', 'public');
			if (is_string($path) && $path !== '') {
				$settings['hero']['video_url'] = $this->publicStorageUrl($path);
			}
		} else {
			$settings['hero']['video_url'] = $this->cleanUrl($hero['video_url'] ?? $settings['hero']['video_url'], $settings['hero']['video_url']);
		}

		$sections = is_array($request->input('sections', [])) ? $request->input('sections', []) : [];
		$sectionOrder = $request->input('section_order', $settings['section_order']);
		$sectionOrder = is_array($sectionOrder) ? array_values(array_filter(array_map('strval', $sectionOrder), static fn (string $k): bool => $k !== '')) : $settings['section_order'];
		$sectionOrder = array_values(array_filter($sectionOrder, static fn (string $k): bool => $k !== 'promotions'));
		if (!in_array('newsletter', $sectionOrder, true)) {
			$sectionOrder[] = 'newsletter';
		}
		$settings['section_order'] = array_values(array_unique($sectionOrder));

		foreach ($settings['section_order'] as $sectionKey) {
			$settings['sections'][$sectionKey] = $this->truthy($sections[$sectionKey] ?? false);
		}
		unset($settings['sections']['promotions']);

		$settings['search']['shortcode'] = $this->cleanText($request->input('search.shortcode', '[traveler_search]'), 255);

		$settings['last_minute']['title'] = $this->cleanText($request->input('last_minute.title', $settings['last_minute']['title']), 255);
		$settings['last_minute']['count'] = $this->clampInt($request->input('last_minute.count', $settings['last_minute']['count']), 1, 20, 4);
		$settings['last_minute']['featured_only'] = $this->truthy($request->input('last_minute.featured_only', false));

		$settings['accommodations']['title'] = $this->cleanText($request->input('accommodations.title', $settings['accommodations']['title']), 255);
		$settings['accommodations']['count'] = $this->clampInt($request->input('accommodations.count', $settings['accommodations']['count']), 1, 20, 4);

		$holiday = is_array($request->input('holiday_theme', [])) ? $request->input('holiday_theme', []) : [];
		$settings['holiday_theme']['enabled'] = $this->truthy($holiday['enabled'] ?? false);
		$settings['holiday_theme']['eyebrow'] = $this->cleanText($holiday['eyebrow'] ?? '', 120);
		$settings['holiday_theme']['title_line_1'] = $this->cleanText($holiday['title_line_1'] ?? '', 120);
		$settings['holiday_theme']['title_line_2'] = $this->cleanText($holiday['title_line_2'] ?? '', 120);
		$settings['holiday_theme']['title_line_3'] = $this->cleanText($holiday['title_line_3'] ?? '', 120);
		$settings['holiday_theme']['subtitle'] = $this->cleanText($holiday['subtitle'] ?? '', 500);
		$settings['holiday_theme']['button_text'] = $this->cleanText($holiday['button_text'] ?? '', 80);
		$settings['holiday_theme']['button_url'] = $this->cleanUrl($holiday['button_url'] ?? '#', '#');
		$settings['holiday_theme']['left_image_url'] = $this->cleanUrl($holiday['left_image_url'] ?? $settings['holiday_theme']['left_image_url'], $settings['holiday_theme']['left_image_url']);
		$settings['holiday_theme']['deco_image_url'] = $this->cleanUrl($holiday['deco_image_url'] ?? $settings['holiday_theme']['deco_image_url'], $settings['holiday_theme']['deco_image_url']);

		if ($request->hasFile('holiday_theme_left_image_file')) {
			$path = $request->file('holiday_theme_left_image_file')?->store('front/home/holiday-theme', 'public');
			if (is_string($path) && $path !== '') {
				$settings['holiday_theme']['left_image_url'] = $this->publicStorageUrl($path);
			}
		}
		if ($request->hasFile('holiday_theme_deco_image_file')) {
			$path = $request->file('holiday_theme_deco_image_file')?->store('front/home/holiday-theme', 'public');
			if (is_string($path) && $path !== '') {
				$settings['holiday_theme']['deco_image_url'] = $this->publicStorageUrl($path);
			}
		}

		$holidayItems = [];
		foreach ((is_array($holiday['items'] ?? null) ? $holiday['items'] : []) as $idx => $item) {
			if (!is_array($item)) {
				continue;
			}
			$title = $this->cleanText($item['title'] ?? '', 140);
			if ($title === '') {
				continue;
			}
			$imageUrl = $this->cleanUrl($item['image_url'] ?? '', '');
			if ($request->hasFile('holiday_theme_item_files.' . $idx)) {
				$path = $request->file('holiday_theme_item_files.' . $idx)?->store('front/home/holiday-theme/items', 'public');
				if (is_string($path) && $path !== '') {
					$imageUrl = $this->publicStorageUrl($path);
				}
			}

			$tags = $item['tags'] ?? [];
			if (is_string($tags)) {
				$tags = preg_split('/\s*,\s*/', $tags, -1, PREG_SPLIT_NO_EMPTY) ?: [];
			}
			$tags = is_array($tags) ? array_values(array_filter(array_map(fn ($t) => $this->cleanText((string) $t, 40), $tags))) : [];

			$holidayItems[] = [
				'title' => $title,
				'badge' => $this->cleanText($item['badge'] ?? '', 80),
				'description' => $this->cleanText($item['description'] ?? '', 500),
				'image_url' => $imageUrl,
				'image' => $imageUrl,
				'button_text' => $this->cleanText($item['button_text'] ?? 'Voir plus', 80),
				'button_url' => $this->cleanUrl($item['button_url'] ?? '#', '#'),
				'tags' => $tags,
				'active' => $this->truthy($item['active'] ?? true),
				'order' => (int) ($item['order'] ?? count($holidayItems)),
			];
		}
		usort($holidayItems, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
		$settings['holiday_theme']['items'] = $holidayItems;

		$destinations = is_array($request->input('destinations_by_region', [])) ? $request->input('destinations_by_region', []) : [];
		$normalizedDbr = [
			'enabled' => $this->truthy($destinations['enabled'] ?? false),
			'title' => $this->cleanText($destinations['title'] ?? 'Destinations par région', 255),
			'items' => [],
		];
		foreach ((is_array($destinations['items'] ?? null) ? $destinations['items'] : []) as $idx => $item) {
			if (!is_array($item)) {
				continue;
			}
			$label = $this->cleanText($item['label'] ?? '', 120);
			if ($label === '') {
				continue;
			}
			$imageUrl = $this->cleanUrl($item['image_url'] ?? '', '');
			if ($request->hasFile('destinations_by_region_files.' . $idx)) {
				$path = $request->file('destinations_by_region_files.' . $idx)?->store('front/home/destinations-by-region', 'public');
				if (is_string($path) && $path !== '') {
					$imageUrl = $this->publicStorageUrl($path);
				}
			}
			$normalizedDbr['items'][] = [
				'label' => $label,
				'image_url' => $imageUrl,
				'link_url' => $this->cleanUrl($item['link_url'] ?? '', ''),
				'order' => (int) ($item['order'] ?? count($normalizedDbr['items']) + 1),
			];
		}
		usort($normalizedDbr['items'], static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

		$settings['good_spots_title'] = $this->cleanText($request->input('good_spots_title', $settings['good_spots_title']), 255);
		$goodSpots = [];
		foreach ((is_array($request->input('good_spots', [])) ? $request->input('good_spots', []) : []) as $idx => $spot) {
			if (!is_array($spot)) {
				continue;
			}
			$title = $this->cleanText($spot['title'] ?? '', 120);
			if ($title === '') {
				continue;
			}
			$imageUrl = $this->cleanUrl($spot['image_url'] ?? '', '');
			if ($request->hasFile('good_spots_files.' . $idx)) {
				$path = $request->file('good_spots_files.' . $idx)?->store('front/home/good-spots', 'public');
				if (is_string($path) && $path !== '') {
					$imageUrl = $this->publicStorageUrl($path);
				}
			}
			$goodSpots[] = [
				'title' => $title,
				'subtitle' => $this->cleanText($spot['subtitle'] ?? '', 180),
				'icon' => $this->cleanText($spot['icon'] ?? '', 80),
				'image_url' => $imageUrl,
				'link_url' => $this->cleanUrl($spot['link_url'] ?? '', ''),
			];
		}
		$settings['good_spots'] = array_slice($goodSpots, 0, 4);

		unset($settings['promotions']);

		$accordion = is_array($request->input('accordion_slider', [])) ? $request->input('accordion_slider', []) : [];
		$settings['accordion_slider']['enabled'] = $this->truthy($accordion['enabled'] ?? false);
		$settings['accordion_slider']['autoplay'] = $this->truthy($accordion['autoplay'] ?? true);
		$settings['accordion_slider']['autoplay_speed'] = $this->clampInt($accordion['autoplay_speed'] ?? 5000, 2000, 30000, 5000);
		$settings['accordion_slider']['slides'] = [];
		foreach ((is_array($accordion['slides'] ?? null) ? $accordion['slides'] : []) as $idx => $slide) {
			if (!is_array($slide)) {
				continue;
			}

			$title = $this->cleanText($slide['title'] ?? '', 160);
			if ($title === '') {
				continue;
			}

			$imageUrl = $this->cleanUrl($slide['image'] ?? '', '');
			if ($request->hasFile('accordion_slider_files.' . $idx)) {
				$path = $request->file('accordion_slider_files.' . $idx)?->store('front/home/accordion-slider', 'public');
				if (is_string($path) && $path !== '') {
					$imageUrl = $this->publicStorageUrl($path);
				}
			}

			$settings['accordion_slider']['slides'][] = [
				'title' => $title,
				'subtitle' => $this->cleanText($slide['subtitle'] ?? '', 255),
				'image' => $imageUrl,
				'link' => $this->cleanUrl($slide['link'] ?? '#', '#'),
				'button_text' => $this->cleanText($slide['button_text'] ?? '', 80),
				'button_style' => $this->cleanText($slide['button_style'] ?? 'orange', 40),
				'overlay_color' => $this->cleanText($slide['overlay_color'] ?? '', 120),
				'order' => (int) ($slide['order'] ?? count($settings['accordion_slider']['slides']) + 1),
			];
		}
		usort($settings['accordion_slider']['slides'], static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

		$whatsapp = is_array($request->input('whatsapp_banner', [])) ? $request->input('whatsapp_banner', []) : [];
		$settings['whatsapp_banner']['enabled'] = $this->truthy($whatsapp['enabled'] ?? false);
		$settings['whatsapp_banner']['title'] = $this->cleanText($whatsapp['title'] ?? '', 255);
		$settings['whatsapp_banner']['subtitle'] = $this->cleanText($whatsapp['subtitle'] ?? '', 400);
		$settings['whatsapp_banner']['button_text'] = $this->cleanText($whatsapp['button_text'] ?? 'Rejoindre', 80);
		$settings['whatsapp_banner']['button_url'] = $this->cleanUrl($whatsapp['button_url'] ?? '#', '#');
		$features = is_array($whatsapp['features'] ?? null) ? $whatsapp['features'] : [];
		$settings['whatsapp_banner']['features'] = array_values(array_filter(array_map(fn ($feature) => $this->cleanText((string) $feature, 80), $features)));
		$settings['whatsapp_banner']['qr_code_url'] = $this->cleanUrl($whatsapp['qr_code_url'] ?? $settings['whatsapp_banner']['qr_code_url'], $settings['whatsapp_banner']['qr_code_url']);
		if ($request->hasFile('whatsapp_banner_qr_file')) {
			$path = $request->file('whatsapp_banner_qr_file')?->store('front/home/whatsapp', 'public');
			if (is_string($path) && $path !== '') {
				$settings['whatsapp_banner']['qr_code_url'] = $this->publicStorageUrl($path);
			}
		}

		$cruises = is_array($request->input('cruises', [])) ? $request->input('cruises', []) : [];
		$settings['cruises']['enabled'] = $this->truthy($cruises['enabled'] ?? false);
		$settings['cruises']['title'] = $this->cleanText($cruises['title'] ?? 'Croisières', 120);
		$settings['cruises']['button_text'] = $this->cleanText($cruises['button_text'] ?? 'Découvrir', 80);
		$settings['cruises']['button_url'] = $this->cleanUrl($cruises['button_url'] ?? '#', '#');
		$settings['cruises']['image_url'] = $this->cleanUrl($cruises['image_url'] ?? $settings['cruises']['image_url'], $settings['cruises']['image_url']);
		if ($request->hasFile('cruises_image_file')) {
			$path = $request->file('cruises_image_file')?->store('front/home/cruises', 'public');
			if (is_string($path) && $path !== '') {
				$settings['cruises']['image_url'] = $this->publicStorageUrl($path);
			}
		}

		$customSections = is_array($request->input('custom_sections', [])) ? $request->input('custom_sections', []) : [];
		$normalizedCustomSections = [];
		foreach ($customSections as $key => $section) {
			if (!is_string($key) || !str_starts_with($key, 'custom_') || !is_array($section)) {
				continue;
			}
			$normalizedCustomSections[$key] = [
				'title' => $this->cleanText($section['title'] ?? '', 180),
				'content' => trim((string) ($section['content'] ?? '')),
			];
		}
		$settings['custom_sections'] = $normalizedCustomSections;

		$settings = $this->normalizeHomeSettings($settings);
		$this->writeWpJsonOption(self::HOME_OPTION, $settings);
		$this->writeWpJsonOption(self::DBR_OPTION, $normalizedDbr);

		return redirect()
			->route('admin.settings.home-page.edit', ['tab' => 'content'])
			->with('success', 'Paramètres de la home enregistrés et synchronisés vers WordPress.');
	}

	private function readWpJsonOption(string $optionName, array $default = []): array
	{
		$raw = $this->readWpOption($optionName);
		if (!is_string($raw) || trim($raw) === '') {
			return $default;
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : $default;
	}

	private function readWpOption(string $optionName): mixed
	{
		return DB::connection('wp')
			->table('options')
			->where('option_name', $optionName)
			->value('option_value');
	}

	private function writeWpJsonOption(string $optionName, array $value): void
	{
		$this->writeWpOption($optionName, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	private function writeWpOption(string $optionName, string $value): void
	{
		DB::connection('wp')
			->table('options')
			->updateOrInsert(
				['option_name' => $optionName],
				[
					'option_value' => $value,
					'autoload' => 'no',
				]
			);
	}

	private function normalizeHomeSettings(array $settings): array
	{
		$merged = array_replace_recursive($this->defaultHomeSettings(), $settings);
		unset($merged['promotions']);
		$merged['accordion_slider'] = $this->normalizeAccordionSlider(is_array($merged['accordion_slider'] ?? null) ? $merged['accordion_slider'] : []);
		return $merged;
	}

	private function normalizeHeaderSettings(array $header): array
	{
		$defaults = $this->defaultHeaderSettings();
		return array_replace_recursive($defaults, $header);
	}

	private function normalizeDestinationsByRegion(array $dbr): array
	{
		$defaults = $this->defaultDestinationsByRegion();
		$out = array_replace_recursive($defaults, $dbr);
		$items = is_array($out['items'] ?? null) ? $out['items'] : [];
		usort($items, static fn (array $a, array $b): int => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
		$out['items'] = $items;
		return $out;
	}

	private function defaultHeaderSettings(): array
	{
		return [
			'enabled' => true,
			'topbar_enabled' => true,
			'phone' => '+212 5 39 32 38 74',
			'email' => 'contact@ajinsafro.ma',
			'socials' => [
				'facebook' => '#',
				'twitter' => '#',
				'instagram' => '#',
				'youtube' => '#',
				'linkedin' => '#',
			],
			'navbar_enabled' => true,
			'logo_url' => '',
			'show_auth_links' => true,
			'login_url' => '/login',
			'signup_url' => '/register',
			'menu_source' => 'wp_menu',
			'wp_menu_location' => 'primary',
			'show_header_sitewide' => false,
			'show_footer_sitewide' => true,
			'links' => [],
			'lowcost_enabled' => true,
			'lowcost_text' => 'Formule low cost',
			'lowcost_url' => '#',
		];
	}

	private function defaultDestinationsByRegion(): array
	{
		return [
			'enabled' => true,
			'title' => 'Destinations par région',
			'items' => [],
		];
	}

	private function defaultHomeSettings(): array
	{
		return [
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
				'whatsapp_banner' => true,
				'cruises' => true,
				'newsletter' => true,
			],
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
				'enabled' => true,
				'eyebrow' => 'Voyages par thème',
				'title_line_1' => 'Explorez',
				'title_line_2' => 'les voyages',
				'title_line_3' => 'par thème',
				'subtitle' => 'Des idées d’évasion pensées pour chaque envie.',
				'left_image_url' => '',
				'deco_image_url' => '',
				'button_text' => 'VOIR PLUS',
				'button_url' => '#',
				'items' => [],
			],
			'regions' => [],
			'good_spots' => [
				[
					'title' => 'Restaurants',
					'subtitle' => 'Où manger ?',
					'icon' => 'fas fa-utensils',
					'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80',
					'link_url' => '#',
				],
				[
					'title' => 'Loisirs',
					'subtitle' => 'Lorem ipsum dolor sit amet',
					'icon' => 'fas fa-icons',
					'image_url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=800&q=80',
					'link_url' => '#',
				],
				[
					'title' => 'Que faire ?',
					'subtitle' => 'Lorem ipsum dolor sit amet',
					'icon' => 'fas fa-map-marked-alt',
					'image_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
					'link_url' => '#',
				],
				[
					'title' => 'Shopping',
					'subtitle' => 'Lorem ipsum dolor sit amet',
					'icon' => 'fas fa-shopping-bag',
					'image_url' => 'https://images.unsplash.com/photo-1481437156560-3205f6a55735?auto=format&fit=crop&w=800&q=80',
					'link_url' => '#',
				],
			],
			'good_spots_title' => 'Les bons coins sur votre destination',
			'accordion_slider' => [
				'enabled' => true,
				'autoplay' => true,
				'autoplay_speed' => 5000,
				'slides' => $this->defaultAccordionSliderSlides(),
			],
			'whatsapp_banner' => [
				'enabled' => true,
				'title' => 'Rejoignez notre chaîne WhatsApp pour suivre nos actualités voyage',
				'subtitle' => 'Restez informé avec AjinSafro',
				'features' => [],
				'button_text' => 'Rejoindre',
				'button_url' => '#',
				'qr_code_url' => '',
			],
			'cruises' => [
				'enabled' => true,
				'title' => 'Croisières',
				'image_url' => '',
				'button_text' => 'Découvrir',
				'button_url' => '#',
			],
			'section_order' => ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'whatsapp_banner', 'cruises', 'newsletter'],
			'custom_sections' => [],
			'footer' => [
				'col1_heading' => 'En savoir plus',
				'col2_heading' => 'Société',
				'legal_text' => "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU",
			],
		];
	}

	private function normalizeAccordionSlider(array $accordion): array
	{
		$defaults = [
			'enabled' => true,
			'autoplay' => true,
			'autoplay_speed' => 5000,
			'slides' => $this->defaultAccordionSliderSlides(),
		];

		$out = array_replace_recursive($defaults, $accordion);
		$out['enabled'] = $this->truthy($out['enabled'] ?? true);
		$out['autoplay'] = $this->truthy($out['autoplay'] ?? true);
		$out['autoplay_speed'] = $this->clampInt($out['autoplay_speed'] ?? 5000, 2000, 30000, 5000);

		$slides = is_array($out['slides'] ?? null) ? $out['slides'] : [];
		$normalized = [];
		foreach ($slides as $idx => $slide) {
			if (!is_array($slide)) {
				continue;
			}

			$title = $this->cleanText($slide['title'] ?? '', 160);
			if ($title === '') {
				continue;
			}

			$normalized[] = [
				'title' => $title,
				'subtitle' => $this->cleanText($slide['subtitle'] ?? '', 255),
				'image' => $this->cleanUrl($slide['image'] ?? '', ''),
				'link' => $this->cleanUrl($slide['link'] ?? '#', '#'),
				'button_text' => $this->cleanText($slide['button_text'] ?? '', 80),
				'button_style' => $this->cleanText($slide['button_style'] ?? 'orange', 40),
				'overlay_color' => $this->cleanText($slide['overlay_color'] ?? '', 120),
				'order' => (int) ($slide['order'] ?? ($idx + 1)),
			];
		}

		usort($normalized, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
		$out['slides'] = $normalized;

		return $out;
	}

	private function defaultAccordionSliderSlides(): array
	{
		return [
			[
				'title' => 'PROGRAMME DE FIDELITE',
				'subtitle' => '',
				'image' => 'https://i.ibb.co/tTrXK11z/Voyagez-Plus-Gagnez-Plus.png',
				'link' => 'https://www.ajinsafro.ma/fidelite',
				'button_text' => "S'inscrire !",
				'button_style' => 'orange',
				'overlay_color' => 'linear-gradient(to bottom, rgba(0, 163, 224, 0.10), rgba(0, 129, 188, 0.10))',
				'order' => 1,
			],
			[
				'title' => 'GROUP DEALS TRAVEL',
				'subtitle' => '',
				'image' => 'https://i.ibb.co/KcVS1QKB/plus-on-est-nombreaux-plus-on-voyage-leger.png',
				'link' => '#',
				'button_text' => '',
				'button_style' => 'orange',
				'overlay_color' => 'linear-gradient(to bottom, rgba(74, 222, 128, 0.05), rgba(22, 163, 74, 0.05))',
				'order' => 2,
			],
			[
				'title' => "L'7AJZ BKRI B'DHAB MCHRI",
				'subtitle' => '',
				'image' => 'https://i.ibb.co/tP3ByxFZ/7ajz-bkri.png',
				'link' => '#',
				'button_text' => 'احجز الآن',
				'button_style' => 'white-arabic',
				'overlay_color' => 'linear-gradient(to bottom, rgba(27, 92, 140, 0.05), rgba(14, 58, 90, 0.05))',
				'order' => 3,
			],
			[
				'title' => 'Programme BZTAM eSFAR',
				'subtitle' => '',
				'image' => 'https://i.ibb.co/qLZYDrYz/Voyagez-Plus-Gagnez-Plus-1.png',
				'link' => '#',
				'button_text' => '',
				'button_style' => 'orange',
				'overlay_color' => 'linear-gradient(to bottom, rgba(250, 204, 21, 0.10), rgba(249, 115, 22, 0.10))',
				'order' => 4,
			],
			[
				'title' => 'IMPORTANT UPDATES',
				'subtitle' => '',
				'image' => '',
				'link' => '#',
				'button_text' => '',
				'button_style' => 'orange',
				'overlay_color' => '',
				'order' => 5,
			],
		];
	}

	private function publicStorageUrl(string $path): string
	{
		$path = ltrim($path, '/');
		$diskUrl = Storage::disk('public')->url($path);
		if (is_string($diskUrl) && $diskUrl !== '') {
			if (str_starts_with($diskUrl, 'http://') || str_starts_with($diskUrl, 'https://')) {
				return $diskUrl;
			}
			return rtrim((string) config('app.admin_url', config('app.url')), '/') . '/' . ltrim($diskUrl, '/');
		}
		return $path;
	}

	private function truthy(mixed $value): bool
	{
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value) || is_float($value)) {
			return (int) $value === 1;
		}
		if (is_string($value)) {
			return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
		}
		return !empty($value);
	}

	private function cleanText(mixed $value, int $maxLen = 255): string
	{
		$text = trim(strip_tags((string) $value));
		if ($text === '') {
			return '';
		}
		return mb_substr($text, 0, $maxLen);
	}

	private function cleanUrl(mixed $value, string $default = ''): string
	{
		$url = trim((string) $value);
		if ($url === '') {
			return $default;
		}
		if ($url === '#' || str_starts_with($url, '/') || str_starts_with($url, '?')) {
			return $url;
		}
		if (preg_match('/^(https?:\/\/|mailto:|tel:)/i', $url) === 1) {
			return filter_var($url, FILTER_VALIDATE_URL) !== false || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')
				? $url
				: $default;
		}
		return $default;
	}

	private function cleanHexColor(mixed $value): string
	{
		$color = trim((string) $value);
		if ($color === '') {
			return '';
		}
		return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1 ? $color : '';
	}

	private function clampInt(mixed $value, int $min, int $max, int $default): int
	{
		if (!is_numeric($value)) {
			return $default;
		}
		return max($min, min($max, (int) $value));
	}

	private function clampFloat(mixed $value, float $min, float $max, float $default): float
	{
		if (!is_numeric($value)) {
			return $default;
		}
		return max($min, min($max, (float) $value));
	}
}
