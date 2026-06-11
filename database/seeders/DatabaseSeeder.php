<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            VoyageThemeSeeder::class,
            BranchesSeeder::class,
            AdminPermissionsSeeder::class,
            AjinsafroRolesSeeder::class,
            PartnerDemoSeeder::class,
            BranchAccountsSeeder::class,
            NormalizeAjinsafroTestUsersSeeder::class,
            DubaiTravelSeeder::class,
            ClientSeeder::class,
            ReservationDemoSeeder::class,
            AccommodationPackageSeeder::class,
            ActivityOfferSeeder::class,
            GroupDealsSeeder::class,
            HajjOmraSeeder::class,
            EconomicOfferSeeder::class,
            OumaymaReservationsOnlySeeder::class,
            CustomRequestDemoSeeder::class,
            OthmaneOfflineAgentSeeder::class,
            TangerAgencyManagerSeeder::class,
            TangerAgentsSeeder::class,
        ]);
    }
}
