<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * La messagerie interne était accessible à tout utilisateur connecté ; elle est
 * désormais protégée par la permission dédiée `messagerie.view`.
 *
 * Ce seeder préserve l'existant : il crée la permission puis l'accorde à tous
 * les rôles et à tous les comptes en mode « permissions personnalisées ».
 * Les admins peuvent ensuite la retirer rôle par rôle ou compte par compte.
 *
 * Réexécutable sans danger.
 */
class MessageriePermissionSeeder extends Seeder
{
    public const PERMISSION = 'messagerie.view';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(self::PERMISSION, 'web');

        foreach (Role::query()->get() as $role) {
            $role->givePermissionTo(self::PERMISSION);
        }

        $customUsers = User::query()->where('access_mode', 'custom')->get();
        foreach ($customUsers as $user) {
            $user->givePermissionTo(self::PERMISSION);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(sprintf(
            'messagerie.view accordée à %d rôle(s) et %d compte(s) en mode personnalisé.',
            Role::query()->count(),
            $customUsers->count()
        ));
    }
}
