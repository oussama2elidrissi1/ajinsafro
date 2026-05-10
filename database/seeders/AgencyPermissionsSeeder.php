<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AgencyPermissionsSeeder extends Seeder
{
    /**
     * Seeder spécifique pour les permissions des agences.
     * Crée et assigne les permissions des agences à tous les rôles admin.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permissions des agences
        $agencyPermissions = [
            'agencies.view',
            'agencies.create',
            'agencies.edit',
            'agencies.delete',
            'points_of_sale.view',
            'points_of_sale.create',
            'points_of_sale.edit',
            'points_of_sale.delete',
            'points_of_sale.performance',
            'agency_dashboard.view',
            'agency_accounts.view',
            'agency_accounts.create',
            'agency_accounts.edit',
            'agency_accounts.disable',
            'agency_accounts.reset_password',
            'assignments.view',
            'assignments.create',
            'assignments.edit',
            'assignments.bulk',
            'assignments.remove',
            'agency_employees.view',
            'agency_employees.create',
            'agency_employees.edit',
            'agency_employees.delete',
            'pos_employees.view',
            'pos_employees.create',
            'pos_employees.edit',
            'pos_employees.delete',
            'agency_performance.view',
            'agency_commissions.view',
            'reservations.view_sensitive',
            'reservations.view_financial',
            'reservations.view_client_contact',
            'reservations.view_internal_notes',
            'reservations.view_commissions',
        ];

        // Créer les permissions
        foreach ($agencyPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Récupérer les rôles admin/super admin
        $adminRoles = Role::query()
            ->where('guard_name', 'web')
            ->get()
            ->filter(function (Role $role): bool {
                $name = strtolower((string) $role->name);

                return str_contains($name, 'admin')
                    || str_contains($name, 'super')
                    || str_contains($name, 'dev');
            });

        // Assigner les permissions aux rôles admin
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($agencyPermissions);
        }

        // Également assigner au rôle 'Ajinsafro Super Admin' s'il existe
        $ajinsafroSuperAdmin = Role::where('name', 'Ajinsafro Super Admin')
            ->where('guard_name', 'web')
            ->first();
        if ($ajinsafroSuperAdmin) {
            $ajinsafroSuperAdmin->givePermissionTo($agencyPermissions);
        }

        // Également assigner au rôle 'Ajinsafro Siege Admin' s'il existe
        $ajinsafroSiegeAdmin = Role::where('name', 'Ajinsafro Siege Admin')
            ->where('guard_name', 'web')
            ->first();
        if ($ajinsafroSiegeAdmin) {
            $ajinsafroSiegeAdmin->givePermissionTo($agencyPermissions);
        }

        // Également assigner au rôle 'Ajinsafro Branch Admin' s'il existe
        $ajinsafrobranchAdmin = Role::where('name', 'Ajinsafro Branch Admin')
            ->where('guard_name', 'web')
            ->first();
        if ($ajinsafrobranchAdmin) {
            $ajinsafrobranchAdmin->givePermissionTo($agencyPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
