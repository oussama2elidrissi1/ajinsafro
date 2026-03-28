<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchAccountsSeeder extends Seeder
{
    /** Mots de passe de test (à changer en production). */
    public const TEST_PASSWORD = 'password123';

    public function run(): void
    {
        $this->call(BranchesSeeder::class);

        $branches = Branch::where('is_active', true)->orderByRaw("CASE WHEN type = 'head_office' THEN 0 ELSE 1 END")->orderBy('code')->get()->keyBy('code');
        if ($branches->isEmpty()) {
            $this->command->warn('Aucune agence trouvée. Exécutez d\'abord BranchesSeeder.');
            return;
        }

        $tanger = $branches->get('TNG');
        $codes = ['TNG', 'FES', 'CAS', 'MAR', 'BRU'];

        // 1. Compte global siège Tanger (siege_admin) – accès à toutes les agences
        $siege = $this->firstOrCreateUser([
            'name' => 'Admin Siège Tanger',
            'email' => 'siege@ajinsafro.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'branch_id' => $tanger?->id,
            'job_title' => 'Admin Siège',
            'is_active' => true,
            'is_admin' => false,
            'access_mode' => 'role',
            'base_role' => BranchScopeService::ROLE_SIEGE_ADMIN,
        ]);
        $siege->syncRoles([BranchScopeService::ROLE_SIEGE_ADMIN]);

        // 2. Compte agence Tanger (branch_admin) – uniquement données agence Tanger
        if ($tanger) {
            $branchAdminTanger = $this->firstOrCreateUser([
                'name' => 'Admin Agence Tanger',
                'email' => 'agence.tanger@ajinsafro.com',
                'password' => Hash::make(self::TEST_PASSWORD),
                'branch_id' => $tanger->id,
                'job_title' => 'Responsable agence',
                'is_active' => true,
                'is_admin' => false,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_BRANCH_ADMIN,
            ]);
            $branchAdminTanger->syncRoles([BranchScopeService::ROLE_BRANCH_ADMIN]);
            $tanger->update(['manager_user_id' => $branchAdminTanger->id]);
        }

        // 3. Compte agence + chef commercial + commercial + agent pour chaque agence (Fès, Casablanca, Marrakech, Bruxelles, et Tanger)
        foreach ($codes as $code) {
            $branch = $branches->get($code);
            if (! $branch) {
                continue;
            }

            $branchAdmin = $this->getOrCreateBranchAdmin($branch);
            $chef = $this->firstOrCreateUser([
                'name' => "Chef Commercial {$branch->city}",
                'email' => "chef.{$code}@ajinsafro.com",
                'password' => Hash::make(self::TEST_PASSWORD),
                'branch_id' => $branch->id,
                'manager_id' => $branchAdmin->id,
                'job_title' => 'Chef Commercial',
                'is_active' => true,
                'is_admin' => false,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_CHEF_COMMERCIAL,
            ]);
            $chef->syncRoles([BranchScopeService::ROLE_CHEF_COMMERCIAL]);

            $commercial = $this->firstOrCreateUser([
                'name' => "Commercial {$branch->city}",
                'email' => "commercial.{$code}@ajinsafro.com",
                'password' => Hash::make(self::TEST_PASSWORD),
                'branch_id' => $branch->id,
                'manager_id' => $chef->id,
                'job_title' => 'Commercial',
                'is_active' => true,
                'is_admin' => false,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_COMMERCIAL,
            ]);
            $commercial->syncRoles([BranchScopeService::ROLE_COMMERCIAL]);

            $agent = $this->firstOrCreateUser([
                'name' => "Agent {$branch->city}",
                'email' => "agent.{$code}@ajinsafro.com",
                'password' => Hash::make(self::TEST_PASSWORD),
                'branch_id' => $branch->id,
                'manager_id' => $chef->id,
                'job_title' => 'Agent',
                'is_active' => true,
                'is_admin' => false,
                'access_mode' => 'role',
                'base_role' => BranchScopeService::ROLE_AGENT,
            ]);
            $agent->syncRoles([BranchScopeService::ROLE_AGENT]);
        }
    }

    private function firstOrCreateUser(array $attributes): User
    {
        $email = $attributes['email'];
        $user = User::where('email', $email)->first();
        if ($user) {
            $exceptPassword = array_filter($attributes, fn ($v, $k) => $k !== 'password', ARRAY_FILTER_USE_BOTH);
            $user->update($exceptPassword);
            return $user;
        }
        return User::create($attributes);
    }

    private function getOrCreateBranchAdmin(Branch $branch): User
    {
        $code = $branch->code;
        if ($code === 'TNG') {
            return User::where('email', 'agence.tanger@ajinsafro.com')->firstOrFail();
        }
        $email = "agence.{$code}@ajinsafro.com";
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update([
                'branch_id' => $branch->id,
                'job_title' => 'Responsable agence',
                'is_active' => true,
            ]);
            $user->syncRoles([BranchScopeService::ROLE_BRANCH_ADMIN]);
            $branch->update(['manager_user_id' => $user->id]);
            return $user;
        }
        $user = User::create([
            'name' => "Admin Agence {$branch->city}",
            'email' => $email,
            'password' => Hash::make(self::TEST_PASSWORD),
            'branch_id' => $branch->id,
            'job_title' => 'Responsable agence',
            'is_active' => true,
            'is_admin' => false,
            'access_mode' => 'role',
            'base_role' => BranchScopeService::ROLE_BRANCH_ADMIN,
        ]);
        $user->syncRoles([BranchScopeService::ROLE_BRANCH_ADMIN]);
        $branch->update(['manager_user_id' => $user->id]);
        return $user;
    }
}
