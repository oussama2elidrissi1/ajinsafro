<?php

namespace Database\Seeders;

use App\Support\AdminMenuPermissionRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPermissionsSeeder extends Seeder
{
    private const RESTRICTED_RESERVATION_PERMISSIONS = [
        'reservations.view_sensitive',
        'reservations.view_financial',
        'reservations.view_client_contact',
        'reservations.view_internal_notes',
        'reservations.view_commissions',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = AdminMenuPermissionRegistry::allPermissionNames();

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::findOrCreate('Admin', 'web');
        $managerRole = Role::findOrCreate('Manager', 'web');
        $agentRole = Role::findOrCreate('Agent', 'web');
        $accountantRole = Role::findOrCreate('Comptable', 'web');
        Role::findOrCreate('Partenaire', 'web');

        $adminRole->syncPermissions(Permission::query()->pluck('name')->all());

        $managerRole->syncPermissions(array_values(array_filter($permissions, function (string $permission): bool {
            return ! str_starts_with($permission, 'settings.roles.')
                && ! str_starts_with($permission, 'settings.security.')
                && ! in_array($permission, self::RESTRICTED_RESERVATION_PERMISSIONS, true);
        })));

        $agentPermissions = array_values(array_filter($permissions, function (string $permission): bool {
            return str_starts_with($permission, 'dashboard.')
                || str_starts_with($permission, 'reservations.')
                || str_starts_with($permission, 'customers.')
                || str_starts_with($permission, 'circuits.')
                || str_starts_with($permission, 'accommodations.')
                || str_starts_with($permission, 'operations.')
                || str_starts_with($permission, 'visa.')
                || in_array($permission, ['agencies.view', 'points_of_sale.view', 'commissions.view-own'], true);
        }));
        $agentRole->syncPermissions(array_values(array_diff($agentPermissions, self::RESTRICTED_RESERVATION_PERMISSIONS)));

        $managerPermissions = array_values(array_unique(array_merge(
            array_values(array_filter($permissions, function (string $permission): bool {
                return ! str_starts_with($permission, 'settings.roles.')
                    && ! str_starts_with($permission, 'settings.security.')
                    && ! in_array($permission, self::RESTRICTED_RESERVATION_PERMISSIONS, true);
            })),
            ['commissions.view-team']
        )));
        $managerRole->syncPermissions($managerPermissions);

        $accountantRole->syncPermissions(array_values(array_filter($permissions, function (string $permission): bool {
            return str_starts_with($permission, 'dashboard.')
                || str_starts_with($permission, 'finance.')
                || str_starts_with($permission, 'reporting.')
                || str_starts_with($permission, 'reservations.payments.')
                || str_starts_with($permission, 'commissions.');
        })));

        $adminUsers = User::query()->where('is_admin', true)->get();
        $hasAccessMode = Schema::hasColumn('users', 'access_mode');
        $hasBaseRole = Schema::hasColumn('users', 'base_role');
        $hasIsActive = Schema::hasColumn('users', 'is_active');

        foreach ($adminUsers as $adminUser) {
            if ($hasAccessMode && $adminUser->access_mode === 'custom') {
                continue;
            }

            $adminUser->syncRoles([$adminRole]);

            if ($hasAccessMode) {
                $adminUser->access_mode = 'role';
            }

            if ($hasBaseRole) {
                $adminUser->base_role = 'Admin';
            }

            if ($hasIsActive) {
                $adminUser->is_active = $adminUser->is_active ?? true;
            }

            $adminUser->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
