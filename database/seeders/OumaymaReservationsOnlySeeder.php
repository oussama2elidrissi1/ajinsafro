<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OumaymaReservationsOnlySeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'reservations.view',
            'reservations.create',
            'reservations.store',
            'reservations.edit',
            'reservations.update',
            'reservations.destroy',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $role = Role::findOrCreate(BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY, 'web');
        $role->syncPermissions($permissionNames);

        $email = (string) env('AJ_OUMAYMA_EMAIL', 'oumayma.ajinsafro@ajinsafro.ma');
        $password = (string) env('AJ_OUMAYMA_PASSWORD', 'Ajinsafro#2026');

        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = 'Oumayma Ajinsafro';
        $user->phone = $user->phone ?: '+212 600 000 000';
        $user->job_title = 'Commercial';
        $user->user_type = 'commercial';
        $user->is_admin = true;
        $user->is_active = true;
        $user->access_mode = 'role';
        $user->base_role = BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY;

        if (! $user->exists || ! $user->password) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $user->syncRoles([$role]);
        $user->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
