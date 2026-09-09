<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Promeut les comptes d'administration principale au role `super_admin`.
 *
 * Contexte : historiquement les comptes d'administration portaient le role legacy `Admin`,
 * attribue automatiquement a tout compte `users.is_admin` par AdminPermissionsSeeder.
 * Le module « Finance & Controle » etant reserve au seul role `super_admin`, les comptes
 * d'administration principale doivent detenir ce role explicitement.
 *
 * Idempotent : relancer ce seeder ne cree pas de doublon et ne retire aucun autre role.
 */
class SuperAdminAccountsSeeder extends Seeder
{
    /**
     * Comptes promus. La liste reprend App\Models\User::DEV_ADMIN_EMAILS
     * (dev@ajinsafro.ma et dev-hiba@ajinsafro.ma) : ce sont les comptes
     * d'administration principale de la plateforme.
     *
     * @return list<string>
     */
    public static function emails(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            User::DEV_ADMIN_EMAILS
        )));
    }

    public function run(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate(BranchScopeService::ROLE_SUPER_ADMIN, 'web');

        foreach (self::emails() as $email) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                $this->command?->warn("Compte {$email} introuvable : promotion ignoree.");

                continue;
            }

            // `assignRole` est additif : les autres roles eventuels sont conserves.
            if (! $user->hasRole($superAdmin)) {
                $user->assignRole($superAdmin);
            }

            // Le mode « custom » court-circuite les roles : on repasse en heritage de role
            // pour que super_admin soit reellement effectif.
            if (Schema::hasColumn('users', 'access_mode') && $user->access_mode === 'custom') {
                $user->access_mode = 'role';
            }

            if (Schema::hasColumn('users', 'base_role')) {
                $user->base_role = BranchScopeService::ROLE_SUPER_ADMIN;
            }

            $user->save();

            $this->command?->info("{$email} : role super_admin attribue.");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
