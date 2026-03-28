<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchesSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Ajinsafro Tanger', 'code' => 'TNG', 'type' => Branch::TYPE_HEAD_OFFICE, 'city' => 'Tanger', 'country' => 'Maroc'],
            ['name' => 'Ajinsafro Fès', 'code' => 'FES', 'type' => Branch::TYPE_BRANCH, 'city' => 'Fès', 'country' => 'Maroc'],
            ['name' => 'Ajinsafro Casablanca', 'code' => 'CAS', 'type' => Branch::TYPE_BRANCH, 'city' => 'Casablanca', 'country' => 'Maroc'],
            ['name' => 'Ajinsafro Marrakech', 'code' => 'MAR', 'type' => Branch::TYPE_BRANCH, 'city' => 'Marrakech', 'country' => 'Maroc'],
            ['name' => 'Ajinsafro Bruxelles', 'code' => 'BRU', 'type' => Branch::TYPE_BRANCH, 'city' => 'Bruxelles', 'country' => 'Belgique'],
        ];

        foreach ($branches as $data) {
            Branch::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
