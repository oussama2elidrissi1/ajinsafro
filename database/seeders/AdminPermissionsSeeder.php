<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [];

        foreach (config('admin_menu.items', []) as $section) {
            if (! empty($section['permission'])) {
                $permissions[] = $section['permission'];
            }

            foreach ($section['children'] ?? [] as $child) {
                if (! empty($child['permission'])) {
                    $permissions[] = $child['permission'];
                }
            }
        }

        $permissions = array_merge(
            $permissions,
            array_values(config('admin_menu.route_permissions', [])),
            array_values(config('admin_menu.route_prefix_permissions', []))
        );

        $permissions = array_values(array_unique(array_filter($permissions)));

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::findOrCreate('Admin', 'web');
        $managerRole = Role::findOrCreate('Manager', 'web');
        $agentRole = Role::findOrCreate('Agent', 'web');
        $accountantRole = Role::findOrCreate('Comptable', 'web');

        $adminRole->syncPermissions(Permission::query()->pluck('name')->all());

        $managerRole->syncPermissions(array_values(array_filter($permissions, function (string $permission): bool {
            return ! str_starts_with($permission, 'settings.roles.')
                && ! str_starts_with($permission, 'settings.security.');
        })));

        $agentRole->syncPermissions(array_values(array_filter($permissions, function (string $permission): bool {
            return str_starts_with($permission, 'dashboard.')
                || str_starts_with($permission, 'reservations.')
                || str_starts_with($permission, 'customers.')
                || str_starts_with($permission, 'circuits.')
                || str_starts_with($permission, 'accommodations.')
                || str_starts_with($permission, 'operations.')
                || str_starts_with($permission, 'visa.');
        })));

        $accountantRole->syncPermissions(array_values(array_filter($permissions, function (string $permission): bool {
            return str_starts_with($permission, 'dashboard.')
                || str_starts_with($permission, 'finance.')
                || str_starts_with($permission, 'reporting.')
                || str_starts_with($permission, 'reservations.payments.');
        })));

        $adminUsers = User::query()->where('is_admin', true)->get();
        foreach ($adminUsers as $adminUser) {
            if ($adminUser->access_mode === 'custom') {
                continue;
            }
            $adminUser->syncRoles([$adminRole]);
            $adminUser->access_mode = 'role';
            $adminUser->base_role = 'Admin';
            $adminUser->is_active = $adminUser->is_active ?? true;
            $adminUser->save();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
