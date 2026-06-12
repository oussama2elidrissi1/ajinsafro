<?php

use App\Models\User;
use App\Services\BranchScopeService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate(AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'web');

        $roleNames = [
            'Admin',
            'Super Admin',
            'Admin Siege',
            'Admin Siège',
            'Chef Commercial',
            'Manager',
            'Commercial',
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY,
        ];

        Role::query()
            ->whereIn('name', $roleNames)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_admin')) {
            User::query()
                ->where('is_admin', true)
                ->get()
                ->each(fn (User $user) => $user->givePermissionTo($permission));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION)
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
