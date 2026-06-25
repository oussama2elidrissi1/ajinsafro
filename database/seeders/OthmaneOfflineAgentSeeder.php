<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OthmaneOfflineAgentSeeder extends Seeder
{
    public const OLD_EMAIL = 'othmane.ajinsafro@ajinsafro.ma';
    public const EMAIL = 'resa@ajinsafro.ma';
    public const PASSWORD = 'Othmane@2026';
    public const ROLE_NAME = 'Agent Offline';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'custom_requests.view',
            'custom_requests.view_all',
            'custom_requests.create',
            'custom_requests.quote',
            'custom_requests.documents',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(self::ROLE_NAME, 'web');
        $role->syncPermissions($permissions);

        $branch = Branch::query()
            ->where('code', 'TNG')
            ->orWhere('name', 'Ajinsafro Tanger')
            ->orWhere('city', 'Tanger')
            ->first();

        $existingNew = User::query()->where('email', self::EMAIL)->first();
        $existingOld = User::query()->where('email', self::OLD_EMAIL)->first();

        if ($existingOld && $existingNew && (int) $existingOld->id !== (int) $existingNew->id) {
            $existingOld->email = 'legacy+'.self::OLD_EMAIL;
            $existingOld->is_active = false;
            $existingOld->save();
        }

        /** @var User $user */
        $user = User::query()
            ->where('email', self::EMAIL)
            ->orWhere('email', self::OLD_EMAIL)
            ->firstOrNew(['email' => self::EMAIL]);

        $user->name = 'Othmane Ajinsafro';
        $user->email = self::EMAIL;
        $user->password = Hash::make(self::PASSWORD);
        $user->branch_id = $branch?->id;
        $user->is_admin = false;
        $user->is_active = true;
        $user->access_mode = 'role';
        $user->base_role = self::ROLE_NAME;
        $user->job_title = 'Agent offline / quotation';
        $user->save();

        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
