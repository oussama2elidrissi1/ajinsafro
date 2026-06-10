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
                || str_starts_with($p, 'agencies.') || str_starts_with($p, 'points_of_sale.') || str_starts_with($p, 'agency_accounts.')
                || str_starts_with($p, 'agency_employees.') || str_starts_with($p, 'pos_employees.') || str_starts_with($p, 'assignments.')
                || str_starts_with($p, 'agency_dashboard.') || str_starts_with($p, 'agency_performance.')
                || str_starts_with($p, 'agency_commissions.')
                || str_starts_with($p, 'settings.view')
                || str_starts_with($p, 'settings.branches.') || str_starts_with($p, 'settings.users.')
                || str_starts_with($p, 'settings.general.');
        }));
        $branchScoped = array_values(array_diff($branchScoped, self::RESTRICTED_RESERVATION_PERMISSIONS));
        $commercial = array_values(array_filter($allPermissions, function (string $p): bool {
            return str_starts_with($p, 'dashboard.') || str_starts_with($p, 'reservations.') || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'circuits.') || str_starts_with($p, 'group-deals.')
                || str_starts_with($p, 'products-services.') || str_starts_with($p, 'messagerie.')
                || in_array($p, ['agencies.view', 'points_of_sale.view', 'agency_employees.view', 'pos_employees.view', 'agency_accounts.view', 'assignments.view', 'commissions.view-team'], true);
        }));
        $commercial = array_values(array_diff($commercial, self::RESTRICTED_RESERVATION_PERMISSIONS));
        $agent = array_values(array_filter($allPermissions, function (string $p): bool {
            return str_starts_with($p, 'dashboard.') || str_starts_with($p, 'reservations.') || str_starts_with($p, 'customers.')
                || str_starts_with($p, 'circuits.') || str_starts_with($p, 'group-deals.')
                || str_starts_with($p, 'products-services.') || str_starts_with($p, 'operations.') || str_starts_with($p, 'visa.')
                || str_starts_with($p, 'messagerie.')
                || in_array($p, ['agencies.view', 'points_of_sale.view', 'agency_accounts.view', 'commissions.view-own', 'custom_requests.create'], true);
        }));
        $agent = array_values(array_diff($agent, self::RESTRICTED_RESERVATION_PERMISSIONS));

        $commercialReservationsOnly = array_values(array_filter($allPermissions, static function (string $permission): bool {
            return in_array($permission, [
                'reservations.view',
                'reservations.create',
                'reservations.store',
                'reservations.edit',
                'reservations.update',
                'reservations.destroy',
            ], true);
        }));

        $this->createRole(BranchScopeService::ROLE_SUPER_ADMIN, $allPermissions);
        $this->createRole(BranchScopeService::ROLE_SIEGE_ADMIN, $allPermissions);
        $this->createRole(BranchScopeService::ROLE_BRANCH_ADMIN, $exceptRolesSecurity);
        $this->createRole(BranchScopeService::ROLE_CHEF_COMMERCIAL, $branchScoped);
        $this->createRole(BranchScopeService::ROLE_COMMERCIAL, $commercial);
        $this->createRole(BranchScopeService::ROLE_MANAGER, $commercial);
        $this->createRole(BranchScopeService::ROLE_AGENT, $agent);
        $this->createRole(BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY, $commercialReservationsOnly);
        $this->createRole('partner_admin', []);
        $this->createRole('partner_agent', []);

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
