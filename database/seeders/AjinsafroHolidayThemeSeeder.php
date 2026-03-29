<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AjinsafroHolidayThemeSeeder extends Seeder
{
    private const PRIMARY_OPTION = 'aj_home_settings';
    private const LEGACY_OPTION = 'ajth_home_settings';

    public function run(): void
    {
        $settings = $this->readSettings();
        $existingTheme = is_array($settings['holiday_theme'] ?? null) ? $settings['holiday_theme'] : [];
        $existingItems = is_array($existingTheme['items'] ?? null) ? array_values($existingTheme['items']) : [];

        $settings['holiday_theme'] = array_replace(
            $existingTheme,
            [
                'enabled' => true,
                'eyebrow' => 'VOYAGES PAR THÈME',
                'title_line_1' => 'Vos envies de voyage',
                'title_line_2' => 'les voyages',
                'title_line_3' => 'par thème',
                'subtitle' => 'Sélectionnez votre thème de voyage au sein de notre collection.',
                'button_text' => 'VOIR PLUS',
                'button_url' => $this->stringValue($existingTheme['button_url'] ?? '#', '#'),
                'left_image_url' => $this->stringValue($existingTheme['left_image_url'] ?? $existingTheme['left_image'] ?? ''),
                'deco_image_url' => $this->stringValue($existingTheme['deco_image_url'] ?? $existingTheme['deco_image'] ?? ''),
                'items' => [
                    $this->buildItem(
                        $existingItems[0] ?? [],
                        'Nos meilleures idées de vacances en famille',
                        'Les vacances en famille autour du monde',
                        ['Aqua park', 'Clubs', 'All Inclusive'],
                        1
                    ),
                    $this->buildItem(
                        $existingItems[1] ?? [],
                        'Les voyages forment l’amitié',
                        'Les vacances entre femmes, women only',
                        ['Amies', 'copines', 'femmes voyageuses'],
                        2
                    ),
                    $this->buildItem(
                        $existingItems[2] ?? [],
                        'Destinations aussi romantiques que fascinantes',
                        'Les voyages de noces autour du monde',
                        ['séjour en amoureux'],
                        3
                    ),
                ],
            ]
        );

        if (!isset($settings['sections']) || !is_array($settings['sections'])) {
            $settings['sections'] = [];
        }
        $settings['sections']['holiday_theme'] = true;

        if (!isset($settings['section_order']) || !is_array($settings['section_order'])) {
            $settings['section_order'] = [];
        }
        if (!in_array('holiday_theme', $settings['section_order'], true)) {
            $settings['section_order'][] = 'holiday_theme';
        }

        DB::connection('wp')
            ->table('options')
            ->updateOrInsert(
                ['option_name' => self::PRIMARY_OPTION],
                [
                    'option_value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'autoload' => 'no',
                ]
            );
    }

    private function readSettings(): array
    {
        $raw = DB::connection('wp')
            ->table('options')
            ->whereIn('option_name', [self::PRIMARY_OPTION, self::LEGACY_OPTION])
            ->orderByRaw("CASE WHEN option_name = ? THEN 0 ELSE 1 END", [self::PRIMARY_OPTION])
            ->pluck('option_value', 'option_name');

        $primary = $this->decodeSettings($raw[self::PRIMARY_OPTION] ?? null);
        if ($primary !== []) {
            return $primary;
        }

        return $this->decodeSettings($raw[self::LEGACY_OPTION] ?? null);
    }

    private function decodeSettings(mixed $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function buildItem(array $existing, string $badge, string $title, array $tags, int $order): array
    {
        $imageUrl = $this->stringValue($existing['image_url'] ?? $existing['image'] ?? '');
        $buttonUrl = $this->stringValue($existing['button_url'] ?? '#', '#');

        return [
            'badge' => $badge,
            'title' => $title,
            'description' => '',
            'image_url' => $imageUrl,
            'image' => $imageUrl,
            'button_text' => 'VOIR PLUS',
            'button_url' => $buttonUrl,
            'tags' => $tags,
            'active' => true,
            'order' => $order,
        ];
    }

    private function stringValue(mixed $value, string $fallback = ''): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }
}
