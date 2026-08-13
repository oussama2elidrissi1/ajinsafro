<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crée le compte dev de Hiba en clonant le profil du compte dev principal
 * (mêmes attributs, rôles et permissions directes que dev@ajinsafro.ma).
 *
 * Réexécutable sans danger : réutilise le compte existant si déjà créé.
 */
class DevHibaAccountSeeder extends Seeder
{
    public const SOURCE_EMAIL = 'dev@ajinsafro.ma';
    public const EMAIL = 'dev-hiba@ajinsafro.ma';
    public const PASSWORD = 'Hiba@2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $source = User::query()->where('email', self::SOURCE_EMAIL)->first();

        /** @var User $user */
        $user = User::query()->where('email', self::EMAIL)->first() ?? new User;

        $user->name = 'Hiba';
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
            // Compte source absent : profil dev global par défaut.
            $user->is_admin = true;
            $user->access_mode = $user->access_mode ?: 'role';
            $user->job_title = $user->job_title ?: 'Développeuse';
        }

        $user->save();

        if ($source) {
            $user->syncRoles($source->roles()->pluck('name')->all());
            $user->syncPermissions($source->getDirectPermissions()->pluck('name')->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(self::EMAIL.' : compte dev prêt'.($source ? ' (profil cloné depuis '.self::SOURCE_EMAIL.')' : ' (profil par défaut, compte source introuvable)').'.');
    }
}
