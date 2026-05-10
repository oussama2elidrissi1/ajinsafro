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
            'agency_employees.view',
            'agency_employees.create',
            'agency_employees.edit',
            'agency_employees.delete',
            'agency_performance.view',
            'agency_commissions.view',
        ];

        // Créer les permissions
        foreach ($agencyPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Récupérer les rôles admin/super admin
        $adminRoles = Role::whereIn('name', [
            'Admin',
            'admin',
            'Super Admin',
            'Super_Admin',
            'SUPER_ADMIN',
            'Manager',
            'ADMIN',
            'Chef Commercial',
            'CHEF_COMMERCIAL',
            'Commercial',
            'COMMERCIAL',
        ])->where('guard_name', 'web')->get();

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
