<?php

namespace Database\Seeders;

use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TangerAgencyManagerSeeder extends Seeder
{
    public const EMAIL = 'Communication@ajinsafro.ma';
    public const PASSWORD = 'Ajinsafro#2026';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $managerPermissions = [
            AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION,
            'custom_requests.view',
            'custom_requests.create',
        ];
        $offlinePermissions = [
            'reservations.view',
            'custom_requests.view',
            'custom_requests.view_all',
            'custom_requests.create',
            'custom_requests.quote',
            'custom_requests.documents',
        ];

        foreach (array_unique(array_merge($managerPermissions, $offlinePermissions)) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(BranchScopeService::ROLE_MANAGER, 'web');
        $role->givePermissionTo($managerPermissions);

        $offlineRole = Role::findOrCreate('Agent Offline', 'web');
        $offlineRole->syncPermissions($offlinePermissions);

        $branch = Branch::query()
            ->where('code', 'TNG')
            ->orWhere('name', 'Ajinsafro Tanger')
            ->orWhere('city', 'Tanger')
            ->first();

        if (! $branch) {
            $this->command?->warn('Agence Tanger (TNG) introuvable. Lancez BranchesSeeder avant TangerAgencyManagerSeeder.');

            return;
        }

        /** @var User $manager */
        $manager = User::query()->firstOrNew(['email' => self::EMAIL]);
        $isNewManager = ! $manager->exists;
        $manager->name = $manager->name ?: 'Chef Agence Tanger';
        $manager->email = self::EMAIL;
        $manager->branch_id = $branch->id;
        $manager->job_title = $manager->job_title ?: 'Chef agence Tanger';
        $manager->user_type = $manager->user_type ?: 'agency_employee';
        $manager->is_admin = $isNewManager ? false : (bool) $manager->is_admin;
        $manager->is_active = true;
        $manager->access_mode = $manager->access_mode ?: 'role';
        $manager->base_role = $manager->base_role ?: BranchScopeService::ROLE_MANAGER;

        if (! $manager->exists || ! $manager->password) {
            $manager->password = Hash::make(self::PASSWORD);
        }

        $manager->save();
        $manager->assignRole([$role->name, $offlineRole->name]);
        $manager->givePermissionTo(array_unique(array_merge($managerPermissions, $offlinePermissions)));

        $branch->update(['manager_user_id' => $manager->id]);

        AgencyEmployee::query()->updateOrCreate(
            [
                'branch_id' => $branch->id,
                'email' => self::EMAIL,
            ],
            [
                'user_id' => $manager->id,
                'first_name' => 'Chef',
                'last_name' => 'Agence Tanger',
                'position' => 'Chef agence',
                'status' => AgencyEmployee::STATUS_ACTIVE,
                'can_login' => true,
            ]
        );

        User::query()
            ->where('branch_id', $branch->id)
            ->whereKeyNot($manager->id)
            ->where(function ($query): void {
                $query->whereIn('email', [
                    'othman.aji@ajinsafro.ma',
                    'booking@ajinsafro.ma',
                    'commercial@ajinsafro.ma',
                    'Collaboration@ajinsafro.ma',
                    'Partnership@ajinsafro.ma',
                    'agent.TNG@ajinsafro.com',
                    'commercial.TNG@ajinsafro.com',
                ])->orWhereHas('roles', function ($roles): void {
                    $roles->whereIn('name', [
                        BranchScopeService::ROLE_AGENT,
                        BranchScopeService::ROLE_COMMERCIAL,
                        'Agent',
                        'Agent Offline',
                    ]);
                });
            })
            ->update(['manager_id' => $manager->id]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
