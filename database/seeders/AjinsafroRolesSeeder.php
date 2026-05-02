<?php

namespace Database\Seeders;

use App\Services\BranchScopeService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AjinsafroRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->ensurePermissionsExist();

        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->all();
        $exceptRolesSecurity = array_values(array_filter($allPermissions, function (string $p): bool {
            return ! str_starts_with($p, 'settings.roles.') && ! str_starts_with($p, 'settings.security.');
        }));
        $branchScoped = array_values(array_filter($allPermissions, function (string $p): bool {
            return str_starts_with($p, 'dashboard.') || str_starts_with($p, 'reservations.') || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'circuits.') || str_starts_with($p, 'accommodations.') || str_starts_with($p, 'operations.')
                || str_starts_with($p, 'visa.') || str_starts_with($p, 'finance.') || str_starts_with($p, 'reporting.')
                || str_starts_with($p, 'messagerie.') || str_starts_with($p, 'products-services.')
                || str_starts_with($p, 'group-deals.') || str_starts_with($p, 'activities.') || str_starts_with($p, 'transfers.')
                || str_starts_with($p, 'settings.view')
                || str_starts_with($p, 'settings.branches.') || str_starts_with($p, 'settings.users.')
                || str_starts_with($p, 'settings.general.');
        }));
        $commercial = array_values(array_filter($allPermissions, function (string $p): bool {
            return str_starts_with($p, 'dashboard.') || str_starts_with($p, 'reservations.') || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'circuits.') || str_starts_with($p, 'group-deals.')
                || str_starts_with($p, 'products-services.') || str_starts_with($p, 'messagerie.');
        }));
        $agent = array_values(array_filter($allPermissions, function (string $p): bool {
            return str_starts_with($p, 'dashboard.') || str_starts_with($p, 'reservations.') || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'circuits.') || str_starts_with($p, 'group-deals.')
                || str_starts_with($p, 'products-services.') || str_starts_with($p, 'operations.') || str_starts_with($p, 'visa.')
                || str_starts_with($p, 'messagerie.');
        }));

        $this->createRole(BranchScopeService::ROLE_SUPER_ADMIN, $allPermissions);
        $this->createRole(BranchScopeService::ROLE_SIEGE_ADMIN, $allPermissions);
        $this->createRole(BranchScopeService::ROLE_BRANCH_ADMIN, $exceptRolesSecurity);
        $this->createRole(BranchScopeService::ROLE_CHEF_COMMERCIAL, $branchScoped);
        $this->createRole(BranchScopeService::ROLE_COMMERCIAL, $commercial);
        $this->createRole(BranchScopeService::ROLE_MANAGER, $commercial);
        $this->createRole(BranchScopeService::ROLE_AGENT, $agent);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermissionsExist(): void
    {
        $permissions = AdminMenuPermissionRegistry::allPermissionNames();
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    private function createRole(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }
}
