<?php

use App\Support\FinanceControlPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cree les permissions du module « Finance & Controle » et ne les attribue QU'AU seul role
 * `super_admin`.
 *
 * Tout autre porteur de ces permissions est purge : role (role_has_permissions) comme
 * utilisateur en attribution directe (model_has_permissions). Le flag `users.is_admin`
 * ne donne aucun droit ici.
 *
 * Aucune permission ETRANGERE au module n'est touchee : les autres roles conservent
 * strictement les droits qu'ils avaient avant cette migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [];
        foreach (FinanceControlPermissions::moduleOwned() as $name) {
            $permissions[] = Permission::findOrCreate($name, 'web');
        }

        $adminRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', FinanceControlPermissions::adminRoleNames())
            ->get();

        foreach ($adminRoles as $role) {
            foreach ($permissions as $permission) {
                $role->givePermissionTo($permission);
            }
        }

        // Filet de securite : toute attribution a un autre role (synchronisation anterieure
        // ou reglage manuel) est retiree.
        $allowedRoleIds = $adminRoles->pluck('id')->all();
        $permissionIds = collect($permissions)->pluck('id')->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->when($allowedRoleIds !== [], fn ($query) => $query->whereNotIn('role_id', $allowedRoleIds))
                ->delete();

            // Attributions directes a un utilisateur : aucune n'est legitime pour ce module,
            // l'acces passe exclusivement par le role super_admin.
            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', FinanceControlPermissions::moduleOwned())
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
