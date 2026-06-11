<?php

namespace Database\Seeders;

use App\Models\ChargeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChargeTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Hotel',
            'Bivouac',
            'Transport',
            'Frais accompagnateur',
            'Activite',
            'Guide',
            'Restaurant',
            'Vol',
            'Assurance',
            'Extra',
            'Autre',
        ];

        foreach ($types as $index => $name) {
            ChargeType::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
