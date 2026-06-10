<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OthmaneOfflineAgentSeeder extends Seeder
{
    public const EMAIL = 'othmane.ajinsafro@ajinsafro.ma';
    public const PASSWORD = 'Othmane@2026';
    public const ROLE_NAME = 'Agent Offline';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'custom_requests.view',
            'custom_requests.quote',
            'custom_requests.documents',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(self::ROLE_NAME, 'web');
        $role->syncPermissions($permissions);

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Othmane Ajinsafro',
                'password' => Hash::make(self::PASSWORD),
                'is_admin' => false,
                'is_active' => true,
                'access_mode' => 'role',
                'base_role' => self::ROLE_NAME,
                'job_title' => 'Agent offline / cotation',
            ]
        );

        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
