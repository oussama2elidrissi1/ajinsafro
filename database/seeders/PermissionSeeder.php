<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminPermissionsSeeder::class,
            AgencyPermissionsSeeder::class,
            AjinsafroRolesSeeder::class,
        ]);
    }
}
