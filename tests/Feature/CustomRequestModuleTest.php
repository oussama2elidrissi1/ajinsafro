<?php

namespace Tests\Feature;

use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomRequestModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_CONNECTION'] = 'mysql';

        putenv('DB_DATABASE=ajinsafronet_wp_tkrpc');
        $_ENV['DB_DATABASE'] = 'ajinsafronet_wp_tkrpc';
        $_SERVER['DB_DATABASE'] = 'ajinsafronet_wp_tkrpc';

        putenv('DB_USERNAME=root');
        $_ENV['DB_USERNAME'] = 'root';
        $_SERVER['DB_USERNAME'] = 'root';

        putenv('DB_PASSWORD=');
        $_ENV['DB_PASSWORD'] = '';
        $_SERVER['DB_PASSWORD'] = '';

        putenv('DB_MYSQL_DRIVER=mysql');
        $_ENV['DB_MYSQL_DRIVER'] = 'mysql';
        $_SERVER['DB_MYSQL_DRIVER'] = 'mysql';

        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('public');
    }

    public function test_agent_portal_creates_custom_request_and_notifies_offline_agents(): void
    {
        $offline = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.quote',
        ], ['Agent Offline']);

        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $response = $this->actingAs($commercial)->post(route('agent.custom-reservations.store'), [
            'customer_full_name' => 'Client Test Agent',
            'customer_phone' => '+212600000001',
            'customer_email' => 'client@example.test',
            'desired_destination' => 'Istanbul',
            'departure_city' => 'Casablanca',
            'desired_departure_date' => now()->addDays(20)->toDateString(),
            'desired_return_date' => now()->addDays(27)->toDateString(),
            'travel_type' => 'organized_trip',
            'travelers_count' => 2,
            'adults_count' => 2,
            'children_count' => 0,
            'babies_count' => 0,
            'client_notes' => 'Demande test.',
        ]);

        $response->assertRedirect(route('agent.custom-reservations.index'));

        $this->assertDatabaseHas('custom_requests', [
            'customer_full_name' => 'Client Test Agent',
            'created_by' => $commercial->id,
            'status' => CustomRequest::STATUS_NEW,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $offline->id,
            'type' => 'custom_request_new',
        ]);
    }

    public function test_offline_agent_sees_new_and_assigned_requests_only(): void
    {
        $offline = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.quote',
        ], ['Agent Offline']);

        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $available = $this->customRequest($commercial, ['customer_full_name' => 'Client Disponible', 'status' => CustomRequest::STATUS_NEW]);
        $assigned = $this->customRequest($commercial, ['customer_full_name' => 'Client Assigné', 'assigned_to' => $offline->id, 'status' => CustomRequest::STATUS_PROCESSING]);
        $otherDraft = $this->customRequest($commercial, ['customer_full_name' => 'Client Brouillon', 'status' => CustomRequest::STATUS_DRAFT]);

        $visibleIds = CustomRequest::query()->visibleTo($offline)->pluck('id')->all();

        $this->assertContains($available->id, $visibleIds);
        $this->assertContains($assigned->id, $visibleIds);
        $this->assertNotContains($otherDraft->id, $visibleIds);
    }

    public function test_quote_prepare_calculates_totals_and_generates_client_pdf(): void
    {
        $offline = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.quote',
            'custom_requests.documents',
        ], ['Agent Offline']);

        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $customRequest = $this->customRequest($commercial, [
            'assigned_to' => $offline->id,
            'status' => CustomRequest::STATUS_PROCESSING,
        ]);

        $quote = $customRequest->quotes()->create([
            'created_by' => $offline->id,
            'currency' => 'MAD',
        ]);

        $response = $this->actingAs($offline)->post(route('admin.custom-requests.quote.prepare', [$customRequest, $quote]), [
            'supplier_name' => 'Fournisseur Interne',
            'valid_until' => now()->addDays(10)->toDateString(),
            'currency' => 'MAD',
            'requested_deposit' => 500,
            'paid_amount' => 100,
            'customer_conditions' => 'Conditions client test.',
            'internal_notes' => 'Ne doit pas apparaître dans le PDF.',
            'items' => [
                [
                    'service_type' => 'hotel',
                    'description' => 'Hôtel 4 étoiles',
                    'supplier_name' => 'Supplier Hidden',
                    'quantity' => 2,
                    'unit_purchase_price' => 100,
                    'unit_margin' => 30,
                    'unit_sale_price' => 160,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.custom-requests.quote', $customRequest));

        $quote->refresh();
        $customRequest->refresh();

        $this->assertSame('320.00', $quote->total_sale);
        $this->assertSame('200.00', $quote->total_purchase);
        $this->assertSame('60.00', $quote->total_margin);
        $this->assertSame('220.00', $quote->remaining_amount);
        $this->assertSame(CustomRequestQuote::STATUS_PREPARED, $quote->status);
        $this->assertSame(CustomRequest::STATUS_QUOTE_PREPARED, $customRequest->status);
        $this->assertNotNull($quote->pdf_path);
        Storage::disk('public')->assertExists($quote->pdf_path);
        $this->assertDatabaseHas('custom_request_documents', [
            'quote_id' => $quote->id,
            'document_type' => 'quote',
            'is_auto_generated' => true,
        ]);
    }

    private function userWithPermissions(array $permissions, array $roles): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roleModels = [];
        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo($permissions);
            $roleModels[] = $role;
        }

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'access_mode' => 'role',
            'base_role' => $roles[0] ?? null,
        ]);

        $user->syncRoles($roleModels);

        return $user;
    }

    private function customRequest(User $creator, array $overrides = []): CustomRequest
    {
        return CustomRequest::query()->create(array_merge([
            'created_by' => $creator->id,
            'customer_full_name' => 'Client Module Test',
            'customer_phone' => '+212600000002',
            'customer_type' => 'new_customer',
            'desired_destination' => 'Dubaï',
            'departure_city' => 'Casablanca',
            'desired_departure_date' => now()->addDays(30)->toDateString(),
            'travel_type' => 'hotel_stay',
            'travelers_count' => 2,
            'adults_count' => 2,
            'children_count' => 0,
            'babies_count' => 0,
            'currency' => 'MAD',
            'payment_status' => 'unpaid',
            'status' => CustomRequest::STATUS_NEW,
            'priority' => 'normal',
        ], $overrides));
    }
}
