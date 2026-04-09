<?php

namespace Database\Seeders;

use App\Models\VoyageTheme;
use Illuminate\Database\Seeder;

class VoyageThemeSeeder extends Seeder
{
    /**
     * Thèmes par défaut Ajinsafro (slug stable pour filtres + sync WP).
     *
     * @return list<array{name: string, slug: string, sort_order: int}>
     */
    public static function defaultThemes(): array
    {
        return [
            ['name' => 'Week-end', 'slug' => 'week-end', 'sort_order' => 10],
            ['name' => 'Séjour', 'slug' => 'sejour', 'sort_order' => 20],
            ['name' => 'Circuit', 'slug' => 'circuit', 'sort_order' => 30],
            ['name' => 'Omra', 'slug' => 'omra', 'sort_order' => 40],
            ['name' => 'Hajj', 'slug' => 'hajj', 'sort_order' => 50],
            ['name' => 'Voyage organisé', 'slug' => 'voyage-organise', 'sort_order' => 60],
            ['name' => 'Famille', 'slug' => 'famille', 'sort_order' => 70],
            ['name' => 'Groupe', 'slug' => 'groupe', 'sort_order' => 80],
            ['name' => 'City break', 'slug' => 'city-break', 'sort_order' => 90],
            ['name' => 'Plage & détente', 'slug' => 'plage-detente', 'sort_order' => 100],
            ['name' => 'Culture & découverte', 'slug' => 'culture-decouverte', 'sort_order' => 110],
            ['name' => 'Aventure', 'slug' => 'aventure', 'sort_order' => 120],
            ['name' => 'Luxe', 'slug' => 'luxe', 'sort_order' => 130],
            ['name' => 'Promo', 'slug' => 'promo', 'sort_order' => 140],
        ];
    }

    public function run(): void
    {
        foreach (self::defaultThemes() as $row) {
            VoyageTheme::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
