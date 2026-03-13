<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        if (Client::query()->exists()) {
            return;
        }

        Client::create([
            'client_type' => 'individual',
            'status' => 'active',
            'source' => 'website',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'gender' => 'male',
            'nationality' => 'France',
            'country_of_residence' => 'France',
            'city' => 'Paris',
            'email' => 'jean.dupont@example.com',
            'phone' => '+33612345678',
            'preferred_language' => 'fr',
            'traveler_category' => 'couple',
            'preferred_destination' => 'Maroc',
            'budget_min' => 2000,
            'budget_max' => 5000,
            'newsletter_opt_in' => true,
        ]);

        Client::create([
            'client_type' => 'company',
            'status' => 'vip',
            'source' => 'referral',
            'first_name' => 'Sophie',
            'last_name' => 'Martin',
            'company_name' => 'Voyages Pro SARL',
            'email' => 's.martin@voyagespro.fr',
            'phone' => '+33698765432',
            'nationality' => 'France',
            'city' => 'Lyon',
            'traveler_category' => 'group',
            'billing_name' => 'Voyages Pro SARL',
            'billing_email' => 'facturation@voyagespro.fr',
        ]);
    }
}
