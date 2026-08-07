<?php

namespace Database\Seeders;

use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Compte gérant du point de vente Casablanca (CAS) : Imad Bourja.
 *
 * - Rôle branch_admin : accès admin complet scopé à son agence
 *   (création des comptes employés/commerciaux, programmes/voyages,
 *   réservations, départs, etc.).
 * - Devient le responsable de l'agence (branches.manager_user_id).
 * - Les comptes siège (siege_admin) et dev gardent la main sur ses accès
 *   via /admin/settings/utilisateurs et /admin/agency-accounts.
 */
class CasablancaAgencyManagerSeeder extends Seeder
{
    public const EMAIL = 'imad.bourja@ajinsafro.ma';
    public const PASSWORD = 'Ajinsafro#2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate(BranchScopeService::ROLE_BRANCH_ADMIN, 'web');
        if ($role->permissions()->count() === 0) {
            $this->command?->warn('Le rôle branch_admin n\'a aucune permission. Lancez AjinsafroRolesSeeder avant CasablancaAgencyManagerSeeder.');
        }

        $branch = Branch::query()
            ->where('code', 'CAS')
            ->orWhere('name', 'Ajinsafro Casablanca')
            ->orWhere('city', 'Casablanca')
            ->first();

        if (! $branch) {
            $this->command?->warn('Agence Casablanca (CAS) introuvable. Lancez BranchesSeeder avant CasablancaAgencyManagerSeeder.');

            return;
        }

        /** @var User $manager */
        $manager = User::query()->firstOrNew(['email' => self::EMAIL]);
        $isNewManager = ! $manager->exists;
        $manager->name = 'Imad Bourja';
        $manager->email = self::EMAIL;
        $manager->branch_id = $branch->id;
        $manager->job_title = $manager->job_title ?: 'Gérant point de vente Casablanca';
        $manager->user_type = $manager->user_type ?: 'agency_employee';
        $manager->is_admin = $isNewManager ? false : (bool) $manager->is_admin;
        $manager->is_active = true;
        $manager->access_mode = 'role';
        $manager->base_role = BranchScopeService::ROLE_BRANCH_ADMIN;

        if (! $manager->exists || ! $manager->password) {
            $manager->password = Hash::make(self::PASSWORD);
        }

        $manager->save();
        $manager->syncRoles([$role->name]);

        $branch->update(['manager_user_id' => $manager->id]);

        AgencyEmployee::query()->updateOrCreate(
            [
                'branch_id' => $branch->id,
                'email' => self::EMAIL,
            ],
            [
                'user_id' => $manager->id,
                'first_name' => 'Imad',
                'last_name' => 'Bourja',
                'position' => 'Gérant point de vente',
                'status' => AgencyEmployee::STATUS_ACTIVE,
                'can_login' => true,
            ]
        );

        // Les employés existants du point de vente passent sous sa responsabilité.
        User::query()
            ->where('branch_id', $branch->id)
            ->whereKeyNot($manager->id)
            ->whereHas('roles', function ($roles): void {
                $roles->whereIn('name', [
                    BranchScopeService::ROLE_CHEF_COMMERCIAL,
                    BranchScopeService::ROLE_COMMERCIAL,
                    BranchScopeService::ROLE_AGENT,
                    'Agent',
                    'Agent Offline',
                ]);
            })
            ->update(['manager_id' => $manager->id]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Compte gérant Casablanca prêt : '.self::EMAIL);
    }
}
