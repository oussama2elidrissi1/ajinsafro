<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;

/**
 * Corrige les comptes de test ambigus (ex. is_admin + rôle agence).
 *
 * Comptes canoniques :
 * - siege@ajinsafro.com → siege_admin, vue réseau
 * - agence.tanger@ajinsafro.com → branch_admin Tanger uniquement
 * - manager@ajinsafro.com (Oussama Idrissi) → chef_commercial Tanger, pas de flag is_admin
 */
class NormalizeAjinsafroTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $tanger = Branch::query()->where('code', 'TNG')->first();
        $branchAdminTanger = User::query()->where('email', 'agence.tanger@ajinsafro.com')->first();

        $manager = User::query()->where('email', 'manager@ajinsafro.com')->first();
        if ($manager) {
            $manager->update([
                'is_admin' => false,
                'job_title' => 'Manager',
                'branch_id' => $tanger?->id ?? $manager->branch_id,
                'manager_id' => $branchAdminTanger?->id ?? $manager->manager_id,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_CHEF_COMMERCIAL,
            ]);
            $manager->syncRoles([BranchScopeService::ROLE_CHEF_COMMERCIAL]);
            $this->command->info('manager@ajinsafro.com : chef_commercial (Tanger), is_admin désactivé — périmètre agence uniquement.');
        }

        $siege = User::query()->where('email', 'siege@ajinsafro.com')->first();
        if ($siege) {
            $siege->update([
                'is_admin' => false,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_SIEGE_ADMIN,
            ]);
            $siege->syncRoles([BranchScopeService::ROLE_SIEGE_ADMIN]);
            $this->command->info('siege@ajinsafro.com : siege_admin confirmé (accès toutes agences).');
        }
    }
}
