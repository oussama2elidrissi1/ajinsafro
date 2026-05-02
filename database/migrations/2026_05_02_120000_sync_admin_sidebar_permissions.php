<?php

use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminMenuPermissionRegistry::allPermissionNames() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $permissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        foreach (AdminMenuPermissionRegistry::legacyPermissionMap() as $newPermission => $legacyPermissions) {
            $newPermissionId = $permissionIds->get($newPermission);

            if (! $newPermissionId) {
                continue;
            }

            $legacyPermissionIds = collect($legacyPermissions)
                ->map(fn (string $name) => $permissionIds->get($name))
                ->filter()
                ->values();

            if ($legacyPermissionIds->isEmpty()) {
                continue;
            }

            $roleIds = DB::table('role_has_permissions')
                ->whereIn('permission_id', $legacyPermissionIds)
                ->pluck('role_id')
                ->unique()
                ->values();

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $newPermissionId,
                    'role_id' => $roleId,
                ], []);
            }

            $directAssignments = DB::table('model_has_permissions')
                ->whereIn('permission_id', $legacyPermissionIds)
                ->get(['model_id', 'model_type']);

            foreach ($directAssignments as $assignment) {
                DB::table('model_has_permissions')->updateOrInsert([
                    'permission_id' => $newPermissionId,
                    'model_id' => $assignment->model_id,
                    'model_type' => $assignment->model_type,
                ], []);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = Permission::query()
            ->whereIn('name', array_keys(AdminMenuPermissionRegistry::legacyPermissionMap()))
            ->pluck('id', 'name');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds->values())->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds->values())->delete();
            Permission::query()->whereIn('id', $permissionIds->values())->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
