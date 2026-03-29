<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AjinsafroHomePageFrenchContentSeeder extends Seeder
{
    public function run(): void
    {
        $raw = DB::connection('wp')
            ->table('options')
            ->where('option_name', 'aj_home_settings')
            ->value('option_value');

        $settings = is_string($raw) && $raw !== ''
            ? json_decode($raw, true)
            : [];

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['promotions'] = array_replace(
            is_array($settings['promotions'] ?? null) ? $settings['promotions'] : [],
            [
                'title' => 'Explorez plus, voyagez mieux avec AjinSafro',
            ]
        );

        $settings['whatsapp_banner'] = array_replace(
            is_array($settings['whatsapp_banner'] ?? null) ? $settings['whatsapp_banner'] : [],
            [
                'badge' => 'WHATSAPP',
                'title' => 'Rejoignez notre chaîne WhatsApp pour suivre nos actualités voyage',
                'subtitle' => 'Restez informé avec AjinSafro',
                'features' => [],
                'button_text' => 'Rejoindre',
                'button_url' => (string) data_get($settings, 'whatsapp_banner.button_url', '#'),
                'qr_code_url' => (string) data_get($settings, 'whatsapp_banner.qr_code_url', ''),
            ]
        );

        $settings['cruises'] = array_replace(
            is_array($settings['cruises'] ?? null) ? $settings['cruises'] : [],
            [
                'title' => 'Croisières',
                'image_url' => (string) data_get($settings, 'cruises.image_url', ''),
                'button_text' => 'Découvrir',
                'button_url' => (string) data_get($settings, 'cruises.button_url', '#'),
            ]
        );

        $settings['holiday_theme'] = array_replace(
            is_array($settings['holiday_theme'] ?? null) ? $settings['holiday_theme'] : [],
            [
                'eyebrow' => 'Voyages par thème',
                'title_line_1' => 'Explorez',
                'title_line_2' => 'les voyages',
                'title_line_3' => 'par thème',
                'subtitle' => 'Des idées d’évasion pensées pour chaque envie.',
                'button_text' => 'VOIR PLUS',
                'button_url' => (string) data_get($settings, 'holiday_theme.button_url', '#'),
                'left_image_url' => (string) data_get($settings, 'holiday_theme.left_image_url', ''),
                'deco_image_url' => (string) data_get($settings, 'holiday_theme.deco_image_url', ''),
                'items' => $this->frenchHolidayItems(data_get($settings, 'holiday_theme.items', [])),
            ]
        );

        DB::connection('wp')
            ->table('options')
            ->updateOrInsert(
                ['option_name' => 'aj_home_settings'],
                [
                    'option_value' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'autoload' => 'no',
                ]
            );
    }

    private function frenchHolidayItems($items): array
    {
        $existingItems = is_array($items) ? array_values($items) : [];

        $demo = [
            [
                'title' => 'Escapades romantiques',
                'badge' => 'À deux',
                'description' => 'Des séjours pensés pour se retrouver dans des adresses pleines de charme.',
                'button_text' => 'Voir plus',
                'tags' => ['couple', 'charme', 'détente'],
            ],
            [
                'title' => 'Vacances en famille',
                'badge' => 'Famille',
                'description' => 'Des voyages pratiques et dépaysants avec des activités pour petits et grands.',
                'button_text' => 'Voir plus',
                'tags' => ['famille', 'activités', 'confort'],
            ],
            [
                'title' => 'Séjours bien-être',
                'badge' => 'Relax',
                'description' => 'Spa, détente et parenthèses douces pour ralentir le rythme et se ressourcer.',
                'button_text' => 'Voir plus',
                'tags' => ['spa', 'bien-être', 'luxe'],
            ],
            [
                'title' => 'Voyages d’aventure',
                'badge' => 'Aventure',
                'description' => 'Circuits dynamiques et découvertes fortes pour celles et ceux qui aiment bouger.',
                'button_text' => 'Voir plus',
                'tags' => ['nature', 'exploration', 'immersion'],
            ],
        ];

        $normalized = [];

        foreach ($demo as $index => $row) {
            $existing = is_array($existingItems[$index] ?? null) ? $existingItems[$index] : [];
            $imageUrl = (string) ($existing['image_url'] ?? $existing['image'] ?? '');
            $buttonUrl = (string) ($existing['button_url'] ?? '#');

            $normalized[] = [
                'title' => $row['title'],
                'badge' => $row['badge'],
                'description' => $row['description'],
                'image_url' => $imageUrl,
                'image' => $imageUrl,
                'button_text' => $row['button_text'],
                'button_url' => $buttonUrl !== '' ? $buttonUrl : '#',
                'tags' => $row['tags'],
                'active' => (bool) ($existing['active'] ?? true),
                'order' => isset($existing['order']) ? (int) $existing['order'] : $index,
            ];
        }

        return $normalized;
    }
}
