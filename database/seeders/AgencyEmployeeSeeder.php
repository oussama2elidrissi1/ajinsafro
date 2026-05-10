<?php

namespace Database\Seeders;

use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgencyEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['branch_code' => 'CAS', 'first_name' => 'Manager', 'last_name' => 'Casablanca', 'email' => 'agence.CAS@ajinsafro.com', 'position' => 'Manager agence', 'status' => AgencyEmployee::STATUS_ACTIVE, 'can_login' => true],
            ['branch_code' => 'TNG', 'first_name' => 'Agent', 'last_name' => 'Réservation Tanger', 'email' => 'agent.TNG@ajinsafro.com', 'position' => 'Agent réservation', 'status' => AgencyEmployee::STATUS_ACTIVE, 'can_login' => true],
            ['branch_code' => 'FES', 'first_name' => 'Agent', 'last_name' => 'Visa Fès', 'email' => 'agent.FES@ajinsafro.com', 'position' => 'Agent visa', 'status' => AgencyEmployee::STATUS_ACTIVE, 'can_login' => true],
            ['branch_code' => 'MAR', 'first_name' => 'Chef', 'last_name' => 'Commercial Marrakech', 'email' => 'chef.MAR@ajinsafro.com', 'position' => 'Chef commercial', 'status' => AgencyEmployee::STATUS_ACTIVE, 'can_login' => true],
            ['branch_code' => 'BRU', 'first_name' => 'Support', 'last_name' => 'Bruxelles', 'email' => 'support.bruxelles@ajinsafro.com', 'position' => 'Agent support', 'status' => AgencyEmployee::STATUS_ACTIVE, 'can_login' => false],
        ];

        foreach ($employees as $data) {
            $branch = Branch::query()->where('code', $data['branch_code'])->first();
            if (! $branch) {
                continue;
            }

            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->first();

            AgencyEmployee::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'email' => $data['email'],
                ],
                [
                    'user_id' => $user?->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'position' => $data['position'],
                    'status' => $data['status'],
                    'can_login' => $data['can_login'],
                ]
            );
        }
    }
}
