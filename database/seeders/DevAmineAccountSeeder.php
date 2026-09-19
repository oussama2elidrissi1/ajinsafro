<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cree le compte dev de Amine en clonant le profil du compte dev principal
 * (memes attributs, roles et permissions directes que dev@ajinsafro.ma).
 *
 * Reexecutable sans danger : reutilise le compte existant si deja cree.
 */
class DevAmineAccountSeeder extends Seeder
{
    public const SOURCE_EMAIL = 'dev@ajinsafro.ma';
    public const EMAIL = 'dev-amine@ajinsafro.ma';
    public const PASSWORD = 'Amine@2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $source = User::query()->where('email', self::SOURCE_EMAIL)->first();

        /** @var User $user */
        $user = User::query()->where('email', self::EMAIL)->first() ?? new User;

        $user->name = 'Amine';
        $user->email = self::EMAIL;
        $user->password = Hash::make(self::PASSWORD);
        $user->is_active = true;

        if ($source) {
            $user->branch_id = $source->branch_id;
            $user->partner_id = $source->partner_id;
            $user->manager_id = $source->manager_id;
            $user->job_title = $source->job_title;
            $user->user_type = $source->user_type;
            $user->is_admin = (bool) $source->is_admin;
            $user->access_mode = $source->access_mode;
            $user->base_role = $source->base_role;
        } else {
            // Compte source absent : profil dev global par defaut.
            $user->is_admin = true;
            $user->access_mode = $user->access_mode ?: 'role';
            $user->job_title = $user->job_title ?: 'Developpeur';
        }

        $user->save();

        if ($source) {
            $user->syncRoles($source->roles()->pluck('name')->all());
            $user->syncPermissions($source->getDirectPermissions()->pluck('name')->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(self::EMAIL.' : compte dev pret'.($source ? ' (profil clone depuis '.self::SOURCE_EMAIL.')' : ' (profil par defaut, compte source introuvable)').'.');
    }
}
