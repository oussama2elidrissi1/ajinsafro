<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        $agencies = [
            ['name' => 'Ajinsafro Casablanca', 'code' => 'CAS', 'type' => Branch::TYPE_BRANCH, 'agency_type' => Branch::AGENCY_TYPE_INTERNAL, 'status' => Branch::STATUS_ACTIVE, 'city' => 'Casablanca', 'country' => 'Maroc', 'currency' => 'MAD', 'default_commission_rate' => 7.5],
            ['name' => 'Ajinsafro Tanger', 'code' => 'TNG', 'type' => Branch::TYPE_HEAD_OFFICE, 'agency_type' => Branch::AGENCY_TYPE_INTERNAL, 'status' => Branch::STATUS_ACTIVE, 'city' => 'Tanger', 'country' => 'Maroc', 'currency' => 'MAD', 'default_commission_rate' => 0],
            ['name' => 'Ajinsafro Fès', 'code' => 'FES', 'type' => Branch::TYPE_BRANCH, 'agency_type' => Branch::AGENCY_TYPE_INTERNAL, 'status' => Branch::STATUS_ACTIVE, 'city' => 'Fès', 'country' => 'Maroc', 'currency' => 'MAD', 'default_commission_rate' => 6.5],
            ['name' => 'Ajinsafro Marrakech', 'code' => 'MAR', 'type' => Branch::TYPE_BRANCH, 'agency_type' => Branch::AGENCY_TYPE_INTERNAL, 'status' => Branch::STATUS_ACTIVE, 'city' => 'Marrakech', 'country' => 'Maroc', 'currency' => 'MAD', 'default_commission_rate' => 7.0],
            ['name' => 'Ajinsafro Bruxelles', 'code' => 'BRU', 'type' => Branch::TYPE_BRANCH, 'agency_type' => Branch::AGENCY_TYPE_PARTNER, 'status' => Branch::STATUS_ACTIVE, 'city' => 'Bruxelles', 'country' => 'Belgique', 'currency' => 'EUR', 'default_commission_rate' => 8.5],
        ];

        foreach ($agencies as $data) {
            Branch::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'is_active' => $data['status'] === Branch::STATUS_ACTIVE,
                ])
            );
        }
    }
}
