<?php

namespace Tests\Feature;

use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAgencyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_admin_sidebar_shows_points_of_sale_entries(): void
    {
        $user = $this->makeAdminUser([
            'dashboard.view',
            'dashboard.overview.view',
            'customers.clients.view',
            'customers.travelers.view',
            'agencies.view',
            'points_of_sale.view',
            'agency_employees.view',
            'pos_employees.view',
            'agency_accounts.view',
            'assignments.view',
            'partners.list.view',
            'partners.suppliers.view',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard.v2'));

        $response->assertOk();
        $response->assertSee('Points de vente');
        $response->assertSee('Liste des points de vente');
        $response->assertSee('Comptes points de vente');
    }

    public function test_agency_crud_flow_works(): void
    {
        $user = $this->makeAdminUser([
            'dashboard.view',
            'agencies.view',
            'agencies.create',
            'agencies.edit',
            'agencies.delete',
            'points_of_sale.view',
            'points_of_sale.create',
            'points_of_sale.edit',
            'points_of_sale.delete',
            'points_of_sale.performance',
            'agency_performance.view',
        ]);

        $storeResponse = $this->actingAs($user)->post(route('admin.agencies.store'), [
            'name' => 'Ajinsafro Rabat',
            'code' => 'RAB',
            'type' => Branch::TYPE_BRANCH,
            'agency_type' => Branch::AGENCY_TYPE_INTERNAL,
            'status' => Branch::STATUS_ACTIVE,
            'city' => 'Rabat',
            'country' => 'Maroc',
            'currency' => 'MAD',
            'default_commission_rate' => 7.5,
        ]);

        $agency = Branch::query()->where('code', 'RAB')->firstOrFail();

        $storeResponse->assertRedirect(route('admin.agencies.show', $agency));
        $this->assertSame('Ajinsafro Rabat', $agency->name);
        $this->assertSame('active', $agency->status);

        $showResponse = $this->actingAs($user)->get(route('admin.agencies.show', $agency));
        $showResponse->assertOk();
        $showResponse->assertSee('Ajinsafro Rabat');

        $aliasIndexResponse = $this->actingAs($user)->get(route('admin.points-of-sale.index'));
        $aliasIndexResponse->assertOk();
        $aliasIndexResponse->assertSee('Points de vente');

        $aliasShowResponse = $this->actingAs($user)->get(route('admin.points-of-sale.show', $agency));
        $aliasShowResponse->assertOk();
        $aliasShowResponse->assertSee('Ajinsafro Rabat');

        $updateResponse = $this->actingAs($user)->put(route('admin.agencies.update', $agency), [
            'name' => 'Ajinsafro Rabat Centre',
            'code' => 'RAB',
            'type' => Branch::TYPE_BRANCH,
            'agency_type' => Branch::AGENCY_TYPE_INTERNAL,
            'status' => Branch::STATUS_INACTIVE,
            'city' => 'Rabat',
            'country' => 'Maroc',
            'currency' => 'MAD',
            'default_commission_rate' => 6.25,
        ]);

        $updateResponse->assertRedirect(route('admin.agencies.show', $agency));
        $agency->refresh();
        $this->assertSame('Ajinsafro Rabat Centre', $agency->name);
        $this->assertSame('inactive', $agency->status);

        $toggleResponse = $this->actingAs($user)->patch(route('admin.agencies.toggle-status', $agency));
        $toggleResponse->assertRedirect();
        $agency->refresh();
        $this->assertSame('active', $agency->status);
    }

    public function test_agency_employee_creation_links_user_and_agency(): void
    {
        $user = $this->makeAdminUser([
            'dashboard.view',
            'agencies.view',
            'agency_employees.view',
            'agency_employees.create',
            'agency_employees.edit',
            'agency_employees.delete',
            'pos_employees.create',
        ]);

        $branch = Branch::query()->create([
            'name' => 'Ajinsafro Tanger',
            'code' => 'TNG',
            'type' => Branch::TYPE_HEAD_OFFICE,
            'agency_type' => Branch::AGENCY_TYPE_INTERNAL,
            'status' => Branch::STATUS_ACTIVE,
            'city' => 'Tanger',
            'country' => 'Maroc',
            'is_active' => true,
        ]);

        Role::findOrCreate('manager', 'web');

        $response = $this->actingAs($user)->post(route('admin.agency-employees.store'), [
            'branch_id' => $branch->id,
            'first_name' => 'Sara',
            'last_name' => 'Manager',
            'email' => 'sara.manager@example.test',
            'phone' => '+212600000001',
            'position' => 'Manager point de vente',
            'status' => AgencyEmployee::STATUS_ACTIVE,
            'can_login' => '1',
            'role_name' => 'manager',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $employee = AgencyEmployee::query()->where('email', 'sara.manager@example.test')->firstOrFail();
        $employee->load('user');

        $response->assertRedirect(route('admin.agency-employees.show', $employee));
        $this->assertSame($branch->id, $employee->branch_id);
        $this->assertTrue($employee->can_login);
        $this->assertNotNull($employee->user_id);
        $this->assertSame('sara.manager@example.test', $employee->user?->email);
        $this->assertSame($branch->id, $employee->user?->branch_id);
    }

    public function test_agency_account_can_link_existing_user_and_reset_password(): void
    {
        $user = $this->makeAdminUser([
            'dashboard.view',
            'agencies.view',
            'points_of_sale.view',
            'agency_accounts.view',
            'agency_accounts.create',
            'agency_accounts.edit',
            'agency_accounts.reset_password',
            'agency_employees.view',
            'agency_employees.create',
        ]);

        $branch = Branch::query()->create([
            'name' => 'Ajinsafro Casablanca',
            'code' => 'CASA',
            'type' => Branch::TYPE_BRANCH,
            'agency_type' => Branch::AGENCY_TYPE_INTERNAL,
            'status' => Branch::STATUS_ACTIVE,
            'city' => 'Casablanca',
            'country' => 'Maroc',
            'is_active' => true,
        ]);

        $employee = AgencyEmployee::query()->create([
            'branch_id' => $branch->id,
            'first_name' => 'Nadia',
            'last_name' => 'Sales',
            'email' => 'nadia.sales@example.test',
            'position' => 'Agent commercial',
            'status' => AgencyEmployee::STATUS_ACTIVE,
            'can_login' => false,
        ]);

        $existingUser = User::factory()->create([
            'name' => 'Nadia Sales',
            'email' => 'nadia.sales@example.test',
            'branch_id' => $branch->id,
            'is_active' => true,
            'access_mode' => 'role',
        ]);

        Role::findOrCreate('commercial', 'web');

        $storeResponse = $this->actingAs($user)->post(route('admin.agency-accounts.store'), [
            'name' => 'Nadia Sales',
            'email' => 'nadia.sales@example.test',
            'branch_id' => $branch->id,
            'employee_id' => $employee->id,
            'existing_user_id' => $existingUser->id,
            'job_title' => 'Agent commercial',
            'role_name' => 'commercial',
            'is_active' => '1',
            'can_login' => '1',
        ]);

        $storeResponse->assertRedirect(route('admin.agency-accounts.index'));

        $employee->refresh();
        $existingUser->refresh();

        $this->assertSame($existingUser->id, $employee->user_id);
        $this->assertTrue($employee->can_login);
        $this->assertSame($branch->id, $existingUser->branch_id);

        $resetResponse = $this->actingAs($user)->post(route('admin.agency-accounts.reset-password', $existingUser));
        $resetResponse->assertRedirect();
        $resetResponse->assertSessionHas('success');
    }

    public function test_roles_permissions_page_shows_agency_permissions(): void
    {
        $user = $this->makeAdminUser([
            'dashboard.view',
            'settings.view',
            'settings.roles.manage',
            'agencies.view',
            'points_of_sale.view',
            'agency_employees.view',
            'pos_employees.view',
            'agency_performance.view',
            'agency_commissions.view',
        ]);

        $role = Role::findOrCreate('Test Role', 'web');

        $response = $this->actingAs($user)->get(route('admin.settings.roles-permissions.edit', $role));

        $response->assertOk();
        $response->assertSee('agencies.view');
        $response->assertSee('points_of_sale.view');
        $response->assertSee('agency_employees.view');
        $response->assertSee('pos_employees.view');
        $response->assertSee('agency_performance.view');
        $response->assertSee('agency_commissions.view');
    }

    private function makeAdminUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'access_mode' => 'custom',
        ]);

        $user->syncPermissions($permissions);

        return $user;
    }
}
