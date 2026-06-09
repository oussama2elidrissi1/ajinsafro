<?php

namespace Tests\Feature;

use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\User;
use App\Http\Controllers\Admin\CustomRequestQuoteController;
use App\Http\Controllers\Agent\CustomReservationController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        $request = $this->requestAs($commercial, [
            'customer_full_name' => 'Client Test Agent',
            'customer_phone' => '+212600000001',
            'customer_email' => 'client@example.test',
            'customer_city' => 'Casablanca',
            'customer_country' => 'Maroc',
            'customer_identity' => 'BK123456',
            'customer_type' => 'new_customer',
            'customer_notes' => 'Demande test.',
            'desired_destination' => 'Istanbul',
            'departure_city' => 'Casablanca',
            'desired_departure_date' => now()->addDays(20)->toDateString(),
            'desired_return_date' => now()->addDays(27)->toDateString(),
            'desired_duration' => '7 nuits',
            'travel_type' => 'organized_trip',
            'travelers_count' => 2,
            'adults_count' => 2,
            'children_count' => 0,
            'babies_count' => 0,
            'approximate_budget' => 12000,
            'currency' => 'MAD',
            'desired_level' => 'comfort',
            'desired_hotel' => 'Hotel test',
            'hotel_category' => '4_stars',
            'meal_plan' => 'breakfast',
            'rooms_count' => 1,
            'room_type' => 'double',
            'flight_included' => 'yes',
            'preferred_airline' => 'RAM',
            'departure_airport' => 'CMN',
            'arrival_airport' => 'IST',
            'baggage_included' => 'to_confirm',
            'airport_transfer_included' => 'yes',
            'local_transport' => 'private_car',
            'requested_services_details' => 'Vol, hotel et transferts.',
            'estimated_price' => 13000,
            'requested_deposit' => 3000,
            'paid_amount' => 0,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'priority' => 'normal',
            'services' => ['flight_ticket', 'hotel', 'transfers'],
        ]);

        $response = app(CustomReservationController::class)->store($request);

        $customRequest = CustomRequest::query()->where('customer_full_name', 'Client Test Agent')->latest('id')->firstOrFail();

        $this->assertRedirectsTo($response, route('agent.custom-reservations.show', $customRequest));

        $this->assertDatabaseHas('custom_requests', [
            'customer_full_name' => 'Client Test Agent',
            'created_by' => $commercial->id,
            'status' => CustomRequest::STATUS_NEW,
            'customer_city' => 'Casablanca',
            'desired_hotel' => 'Hotel test',
        ]);

        $this->assertDatabaseHas('custom_request_services', [
            'custom_request_id' => $customRequest->id,
            'service_key' => 'hotel',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $offline->id,
            'type' => 'custom_request_new',
        ]);
    }

    public function test_agent_create_page_renders_complete_custom_request_form(): void
    {
        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $response = $this->actingAs($commercial)->get(route('agent.custom-reservations.create'));

        $response->assertOk();
        $response->assertSee('Créer une demande à la carte');
        $response->assertSee('Parcours');
        $response->assertSee('Informations générales');
        $response->assertSee('Voyage demandé');
        $response->assertSee('Offre commerciale');
        $response->assertSee('Détails de programme');
        $response->assertSee('Programme détaillé souhaité');
        $response->assertSee('Configuration hébergement');
        $response->assertSee('Configuration transport et transferts');
        $response->assertSee('Paiement / estimation');
        $response->assertSee('Suivi');
        $response->assertSee('Nom complet du client <span>*</span>', false);
        $response->assertSee('Téléphone <span>*</span>', false);
        $response->assertSee('Destination souhaitée <span>*</span>', false);
        $response->assertSee('Adultes <span>*</span>', false);
        $response->assertDontSee('Email <span>*</span>', false);
        $response->assertSee('/agent/reservations-a-la-carte', false);
        $response->assertDontSee('/admin/custom-requests', false);
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

        $request = $this->requestAs($offline, [
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

        $response = app(CustomRequestQuoteController::class)->prepare($request, $customRequest, $quote);

        $this->assertRedirectsTo($response, route('admin.custom-requests.quote', $customRequest));

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

    private function requestAs(User $user, array $data): Request
    {
        $request = Request::create('/', 'POST', $data);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function assertRedirectsTo(RedirectResponse $response, string $url): void
    {
        $this->assertSame($url, $response->getTargetUrl());
    }

    private function customRequest(User $creator, array $overrides = []): CustomRequest
    {
        return CustomRequest::query()->create(array_merge([
            'created_by' => $creator->id,
            'request_number' => 'DAC-TEST-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8)),
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
