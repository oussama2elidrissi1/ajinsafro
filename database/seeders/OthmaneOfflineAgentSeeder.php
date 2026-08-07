<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OthmaneOfflineAgentSeeder extends Seeder
{
    /** Anciens emails du compte (resa@ est désormais réservé au gérant Casablanca). */
    public const LEGACY_EMAILS = ['resa@ajinsafro.ma', 'othmane.ajinsafro@ajinsafro.ma'];
    public const EMAIL = 'othman.aji@ajinsafro.ma';
    public const PASSWORD = 'Othmane@2026';
    public const ROLE_NAME = 'Agent Offline';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'reservations.view',
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

        // Ne jamais toucher au compte gérant (branch_admin) qui réutilise resa@ajinsafro.ma.
        $legacyQuery = fn () => User::query()
            ->whereIn('email', self::LEGACY_EMAILS)
            ->where(function ($query): void {
                $query->whereNull('base_role')
                    ->orWhere('base_role', '!=', BranchScopeService::ROLE_BRANCH_ADMIN);
            });

        /** @var User|null $user */
        $user = User::query()->where('email', self::EMAIL)->first()
            ?? $legacyQuery()->orderBy('id')->first();

        foreach ($legacyQuery()->whereKeyNot($user?->id ?? 0)->get() as $legacy) {
            $legacy->email = 'legacy+'.$legacy->email;
            $legacy->is_active = false;
            $legacy->save();
        }

        $user ??= new User;

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
