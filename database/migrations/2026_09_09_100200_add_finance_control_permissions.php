<?php

use App\Models\User;
use App\Support\FinanceControlPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cree les permissions du module « Finance & Controle » et ne les attribue QU'AUX roles
 * d'administration globale (super_admin / siege_admin et graphies legacy).
 *
 * Aucune permission existante n'est modifiee : les autres roles conservent strictement
 * les droits qu'ils avaient avant cette migration.
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

        // Comptes super-administrateurs historiques identifies par le flag is_admin.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_admin')) {
            User::query()->where('is_admin', true)->get()->each(function (User $user) use ($permissions) {
                foreach ($permissions as $permission) {
                    $user->givePermissionTo($permission);
                }
            });
        }

        // Filet de securite : si un role operationnel avait deja recu l'une de ces
        // permissions (synchronisation anterieure), on la retire.
        $allowedRoleIds = $adminRoles->pluck('id')->all();
        $permissionIds = collect($permissions)->pluck('id')->all();

        if ($permissionIds !== []) {
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->when($allowedRoleIds !== [], fn ($query) => $query->whereNotIn('role_id', $allowedRoleIds))
                ->delete();
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
