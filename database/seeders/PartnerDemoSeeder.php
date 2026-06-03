<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PartnerDemoSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Partenaire', 'web');
        Role::findOrCreate('partner_admin', 'web');
        Role::findOrCreate('partner_agent', 'web');

        DB::transaction(function (): void {
            $admin = User::updateOrCreate(
                ['email' => 'partner.admin@ajinsafro.ma'],
                [
                    'name' => 'Admin Partenaire Demo',
                    'phone' => '0600000000',
                    'password' => Hash::make('password'),
                    'user_type' => 'partner',
                    'base_role' => 'partner_admin',
                    'is_admin' => false,
                    'is_active' => true,
                ]
            );
            $admin->syncRoles(['Partenaire', 'partner_admin']);

            $partner = Partner::updateOrCreate(
                ['email' => 'partenaire.demo@ajinsafro.ma'],
                [
                    'user_id' => $admin->id,
                    'name' => 'Agence Partenaire Demo',
                    'raison_sociale' => 'Agence Partenaire Demo',
                    'nom_commercial' => 'Agence Partenaire Demo',
                    'responsable_name' => 'Admin Partenaire Demo',
                    'nom_responsable' => 'Admin Partenaire Demo',
                    'phone' => '0600000000',
                    'telephone' => '0600000000',
                    'city' => 'Tanger',
                    'ville' => 'Tanger',
                    'status' => Partner::STATUS_VALIDATED,
                    'wallet_balance' => 0,
                    'validated_at' => now(),
                    'validated_by' => null,
                ]
            );

            $admin->forceFill([
                'partner_id' => $partner->id,
            ])->save();

            $agent = User::updateOrCreate(
                ['email' => 'partner.agent@ajinsafro.ma'],
                [
                    'name' => 'Agent Partenaire Demo',
                    'phone' => '0600000001',
                    'password' => Hash::make('password'),
                    'partner_id' => $partner->id,
                    'created_by' => $admin->id,
                    'user_type' => 'partner_agent',
                    'base_role' => 'partner_agent',
                    'is_admin' => false,
                    'is_active' => true,
                ]
            );
            $agent->syncRoles(['partner_agent']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
