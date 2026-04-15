<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCustomPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_permissions_are_saved_and_reloaded(): void
    {
        $this->withoutMiddleware();

        $editor = User::factory()->create();
        $user = User::factory()->create(['access_mode' => 'custom']);

        $permissions = [
            'dashboard.view',
            'reservations.view',
            'circuits.voyages.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'access_mode' => 'custom',
            'role_name' => '',
            'permissions' => [$permissions[0], $permissions[2]],
            'is_admin' => '1',
            'is_active' => '1',
        ];

        $response = $this->actingAs($editor)->put(route('admin.settings.utilisateurs.update', $user), $payload);

        $response->assertRedirect(route('admin.settings.utilisateurs.edit', $user));

        $user->refresh();

        $this->assertSame('custom', $user->access_mode);
        $this->assertSame([], $user->getRoleNames()->values()->all());
        $this->assertEqualsCanonicalizing([$permissions[0], $permissions[2]], $user->getPermissionNames()->values()->all());
    }

    public function test_unchecking_permissions_is_applied_on_save(): void
    {
        $this->withoutMiddleware();

        $editor = User::factory()->create();
        $user = User::factory()->create(['access_mode' => 'custom']);

        $permissions = [
            'dashboard.view',
            'reservations.view',
            'circuits.voyages.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $user->syncPermissions($permissions);

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'access_mode' => 'custom',
            'role_name' => '',
            'permissions' => [$permissions[1]],
            'is_admin' => '1',
            'is_active' => '1',
        ];

        $response = $this->actingAs($editor)->put(route('admin.settings.utilisateurs.update', $user), $payload);

        $response->assertRedirect(route('admin.settings.utilisateurs.edit', $user));

        $user->refresh();

        $this->assertEqualsCanonicalizing([$permissions[1]], $user->getPermissionNames()->values()->all());
    }

    public function test_role_mode_takes_priority_over_custom_permissions_payload(): void
    {
        $this->withoutMiddleware();

        $editor = User::factory()->create();
        $user = User::factory()->create(['access_mode' => 'custom']);

        $settingsPermission = Permission::findOrCreate('settings.users.manage', 'web');
        $dashboardPermission = Permission::findOrCreate('dashboard.view', 'web');
        Permission::findOrCreate('reservations.view', 'web');

        $role = Role::findOrCreate('Test Manager', 'web');
        $role->syncPermissions([$settingsPermission, $dashboardPermission]);

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'access_mode' => 'role',
            'role_name' => $role->name,
            'permissions' => ['reservations.view'],
            'is_admin' => '1',
            'is_active' => '1',
        ];

        $response = $this->actingAs($editor)->put(route('admin.settings.utilisateurs.update', $user), $payload);

        $response->assertRedirect(route('admin.settings.utilisateurs.edit', $user));

        $user->refresh();

        $this->assertSame('role', $user->access_mode);
        $this->assertSame($role->name, $user->base_role);
        $this->assertEqualsCanonicalizing([$role->name], $user->getRoleNames()->values()->all());
        $this->assertSame([], $user->permissions()->pluck('name')->all());
    }
}
