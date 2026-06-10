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

class TangerAgentsSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Ajinsafro#2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $branch = Branch::query()
            ->where('code', 'TNG')
            ->orWhere('name', 'Ajinsafro Tanger')
            ->orWhere('city', 'Tanger')
            ->first();
        if (! $branch) {
            $this->command?->warn('Agence Tanger (TNG) introuvable. Lancez BranchesSeeder avant TangerAgentsSeeder.');

            return;
        }

        $role = Role::findOrCreate(BranchScopeService::ROLE_AGENT, 'web');
        $managerId = User::query()->where('email', 'chef.TNG@ajinsafro.com')->value('id')
            ?: $branch->manager_user_id;

        $accounts = [
            [
                'name' => 'Oumaima Ajinsafro',
                'email' => 'booking@ajinsafro.ma',
                'job_title' => 'Agent',
                'first_name' => 'Oumaima',
                'last_name' => 'Ajinsafro',
                'position' => 'Agent réservation',
            ],
            [
                'name' => 'Ilham Ajinsafro',
                'email' => 'commercial@ajinsafro.ma',
                'job_title' => 'Agent',
                'first_name' => 'Ilham',
                'last_name' => 'Ajinsafro',
                'position' => 'Agent réservation',
            ],
        ];

        foreach ($accounts as $account) {
            /** @var User $user */
            $user = User::query()->firstOrNew(['email' => $account['email']]);
            $user->name = $account['name'];
            $user->branch_id = $branch->id;
            $user->manager_id = $managerId;
            $user->job_title = $account['job_title'];
            $user->user_type = 'agency_employee';
            $user->is_admin = false;
            $user->is_active = true;
            $user->access_mode = 'role';
            $user->base_role = BranchScopeService::ROLE_AGENT;

            if (! $user->exists || ! $user->password) {
                $user->password = Hash::make(self::DEFAULT_PASSWORD);
            }

            $user->save();
            $user->syncRoles([$role]);

            AgencyEmployee::query()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'email' => $account['email'],
                ],
                [
                    'user_id' => $user->id,
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'position' => $account['position'],
                    'status' => AgencyEmployee::STATUS_ACTIVE,
                    'can_login' => true,
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
