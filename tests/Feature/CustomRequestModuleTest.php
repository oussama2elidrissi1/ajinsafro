<?php

namespace Tests\Feature;

use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\Client;
use App\Models\ClientNotification;
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
            'submit_action' => 'submit',
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

        $customRequest->refresh();
        $this->assertNull($customRequest->latestQuote);
        $this->assertDatabaseMissing('custom_request_documents', [
            'custom_request_id' => $customRequest->id,
            'document_type' => 'quote',
            'is_auto_generated' => true,
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
        $response->assertSee('Parcours');
        $response->assertSee('Offre commerciale');
        $response->assertSee('Paiement / estimation');
        $response->assertSee('Suivi');
        $response->assertSee('/agent/reservations-a-la-carte', false);
        $response->assertDontSee('/admin/custom-requests', false);
    }

    public function test_agent_existing_client_search_is_limited_to_own_clients_and_persists_client_link(): void
    {
        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $otherAgent = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent Offline']);

        $ownClient = $this->clientForAgent($commercial, 'Client Agent Propre', '0600000001');
        $otherClient = $this->clientForAgent($otherAgent, 'Client Autre Agent', '0600000002');

        $page = $this->actingAs($commercial)->get(route('agent.custom-reservations.create'));
        $page->assertOk();
        $page->assertSee('Rechercher un client existant');
        $page->assertDontSee('Client Agent Propre');
        $page->assertDontSee('Client Autre Agent');

        $searchResponse = $this->actingAs($commercial)->get(route('agent.custom-reservations.clients.search', ['q' => '0600000001']));
        $searchResponse->assertOk();
        $searchResponse->assertJsonPath('count', 1);
        $searchResponse->assertJsonPath('items.0.id', $ownClient->id);
        $searchResponse->assertJsonMissing(['id' => $otherClient->id]);

        $request = $this->requestAs($commercial, [
            'customer_type' => 'existing_customer',
            'existing_client_id' => $ownClient->id,
            'customer_full_name' => '',
            'customer_phone' => '',
            'customer_email' => '',
            'customer_city' => '',
            'customer_country' => '',
            'customer_identity' => '',
            'desired_destination' => 'Istanbul',
            'departure_city' => 'Casablanca',
            'desired_departure_date' => now()->addDays(20)->toDateString(),
            'travel_type' => 'organized_trip',
            'travelers_count' => 1,
            'adults_count' => 1,
            'children_count' => 0,
            'babies_count' => 0,
            'currency' => 'MAD',
            'payment_status' => 'unpaid',
            'priority' => 'normal',
            'services' => [],
        ]);

        $response = app(CustomReservationController::class)->store($request);

        $customRequest = CustomRequest::query()->where('client_id', $ownClient->id)->latest('id')->firstOrFail();
        $this->assertRedirectsTo($response, route('agent.custom-reservations.show', $customRequest));
        $this->assertSame($ownClient->id, (int) $customRequest->client_id);
        $this->assertSame($ownClient->full_name, $customRequest->customer_full_name);
        $this->assertSame($ownClient->phone, $customRequest->customer_phone);
        $this->assertSame($ownClient->email, $customRequest->customer_email);
        $this->assertDatabaseHas('custom_requests', [
            'id' => $customRequest->id,
            'client_id' => $ownClient->id,
            'created_by' => $commercial->id,
        ]);
        $this->assertDatabaseMissing('custom_requests', [
            'client_id' => $otherClient->id,
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
        $assigned = $this->customRequest($commercial, ['customer_full_name' => 'Client AssignÃ©', 'assigned_to' => $offline->id, 'status' => CustomRequest::STATUS_PROCESSING]);
        $otherDraft = $this->customRequest($commercial, ['customer_full_name' => 'Client Brouillon', 'status' => CustomRequest::STATUS_DRAFT]);

        $visibleIds = CustomRequest::query()->visibleTo($offline)->pluck('id')->all();

        $this->assertContains($available->id, $visibleIds);
        $this->assertContains($assigned->id, $visibleIds);
        $this->assertNotContains($otherDraft->id, $visibleIds);
    }

    public function test_regular_agent_with_quote_permission_cannot_quote_custom_request(): void
    {
        $regularAgent = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
            'custom_requests.quote',
        ], ['Agent']);

        $offline = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.quote',
        ], ['Agent Offline']);

        $customRequest = $this->customRequest($regularAgent, [
            'status' => CustomRequest::STATUS_NEW,
        ]);

        $this->assertTrue($regularAgent->can('custom_requests.quote'));
        $this->assertFalse($regularAgent->canQuoteCustomRequests());
        $this->assertFalse($customRequest->canBeQuotedBy($regularAgent));
        $this->assertTrue($offline->canQuoteCustomRequests());
        $this->assertTrue($customRequest->canBeQuotedBy($offline));
    }

    public function test_creator_agent_can_download_quote_and_request_modification_after_offline_processing(): void
    {
        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $offline = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.quote',
        ], ['Agent Offline']);

        $customRequest = $this->customRequest($commercial, [
            'assigned_to' => $offline->id,
            'status' => CustomRequest::STATUS_QUOTE_SENT,
        ]);

        Storage::disk('public')->put('custom-requests/test/devis.pdf', 'PDF test');
        $quote = $customRequest->quotes()->create([
            'created_by' => $offline->id,
            'currency' => 'MAD',
            'status' => CustomRequestQuote::STATUS_SENT,
            'pdf_path' => 'custom-requests/test/devis.pdf',
        ]);

        $download = app(CustomReservationController::class)->downloadQuote(
            $this->requestAs($commercial, []),
            $customRequest,
            $quote
        );

        $this->assertSame(200, $download->getStatusCode());

        $request = $this->requestAs($commercial, [
            'message' => 'Merci de revoir le prix de l hotel.',
        ]);

        app(CustomReservationController::class)->requestModification($request, $customRequest);

        $quote->refresh();
        $customRequest->refresh();

        $this->assertSame(CustomRequestQuote::STATUS_MODIFICATION_REQUESTED, $quote->status);
        $this->assertSame(CustomRequest::STATUS_MODIFICATION_REQUESTED, $customRequest->status);
        $this->assertDatabaseHas('custom_request_comments', [
            'custom_request_id' => $customRequest->id,
            'user_id' => $commercial->id,
            'comment_type' => 'modification_request',
            'message' => 'Merci de revoir le prix de l hotel.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $offline->id,
            'type' => 'custom_request_modification_requested',
        ]);
    }

    public function test_agent_can_mark_notification_as_read_from_modal_action(): void
    {
        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $notification = ClientNotification::query()->create([
            'user_id' => $commercial->id,
            'type' => 'custom_request_quote_sent',
            'title' => 'Devis envoye',
            'message' => 'Le devis DAC-TEST est pret.',
            'link' => route('agent.custom-reservations.index'),
            'is_read' => false,
        ]);

        $response = $this->actingAs($commercial)->post(route('agent.notifications.read', $notification));

        $response->assertRedirect(route('agent.custom-reservations.index'));
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_agent_index_can_filter_custom_requests_by_priority(): void
    {
        $commercial = $this->userWithPermissions([
            'dashboard.view',
            'custom_requests.view',
            'custom_requests.create',
        ], ['Agent']);

        $this->customRequest($commercial, [
            'customer_full_name' => 'Client Urgent Filtre',
            'priority' => 'urgent',
            'status' => CustomRequest::STATUS_QUOTE_SENT,
        ]);

        $this->customRequest($commercial, [
            'customer_full_name' => 'Client Normal Filtre',
            'priority' => 'normal',
            'status' => CustomRequest::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($commercial)->get(route('agent.custom-reservations.index', ['priority' => 'urgent']));

        $response->assertOk();
        $response->assertSee('Client Urgent Filtre');
        $response->assertDontSee('Client Normal Filtre');
        $response->assertSee('Priorite');
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
            'internal_notes' => 'Ne doit pas apparaÃ®tre dans le PDF.',
            'items' => [
                [
                    'service_type' => 'hotel',
                    'description' => 'HÃ´tel 4 Ã©toiles',
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

    public function test_quote_prepare_accepts_program_days_and_day_services(): void
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
            'supplier_name' => 'Fournisseur Programme',
            'valid_until' => now()->addDays(12)->toDateString(),
            'response_deadline' => now()->addDays(5)->toDateString(),
            'currency' => 'MAD',
            'requested_deposit' => 300,
            'paid_amount' => 100,
            'customer_conditions' => 'Conditions du devis.',
            'days' => [
                [
                    'day_number' => 1,
                    'date' => now()->addDays(30)->toDateString(),
                    'title' => 'Arrivée à Paris',
                    'city' => 'Paris',
                    'client_description' => 'Arrivée, transfert et installation.',
                    'services' => [
                        [
                            'service_type' => 'flight',
                            'title' => 'Vol Casablanca Paris',
                            'description' => 'Vol direct avec bagage inclus.',
                            'supplier_name' => 'Royal Air Maroc',
                            'quantity' => 2,
                            'unit_purchase_price' => 100,
                            'margin_type' => 'percent',
                            'margin_value' => 20,
                            'data_json' => [
                                'airline' => 'Royal Air Maroc',
                                'flight_number' => 'AT760',
                                'from' => 'Casablanca',
                                'to' => 'Paris',
                            ],
                        ],
                        [
                            'service_type' => 'hotel',
                            'title' => 'Hôtel Exemple',
                            'description' => 'Chambre double avec petit déjeuner.',
                            'supplier_name' => 'Hotel Supplier',
                            'quantity' => 1,
                            'unit_purchase_price' => 500,
                            'margin_type' => 'amount',
                            'margin_value' => 100,
                            'data_json' => [
                                'hotel_name' => 'Hôtel Exemple',
                                'nights' => '3',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = app(CustomRequestQuoteController::class)->prepare($request, $customRequest, $quote);

        $this->assertRedirectsTo($response, route('admin.custom-requests.quote', $customRequest));

        $quote->refresh();

        $this->assertSame('840.00', $quote->total_sale);
        $this->assertSame('700.00', $quote->total_purchase);
        $this->assertSame('140.00', $quote->total_margin);
        $this->assertSame('740.00', $quote->remaining_amount);
        $this->assertDatabaseHas('custom_request_quote_days', [
            'custom_request_quote_id' => $quote->id,
            'day_number' => 1,
            'title' => 'Arrivée à Paris',
        ]);
        $this->assertDatabaseHas('custom_request_quote_items', [
            'custom_request_quote_id' => $quote->id,
            'service_type' => 'flight',
            'title' => 'Vol Casablanca Paris',
            'margin_type' => 'percent',
        ]);
        Storage::disk('public')->assertExists($quote->pdf_path);
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
            'desired_destination' => 'DubaÃ¯',
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

    private function clientForAgent(User $agent, string $fullName, string $phone): Client
    {
        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        return Client::query()->create([
            'created_by' => $agent->id,
            'assigned_to' => $agent->id,
            'client_type' => 'individual',
            'status' => 'active',
            'source' => 'admin',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => strtolower(str_replace(' ', '.', $fullName)).'@example.test',
            'city' => 'Casablanca',
        ]);
    }
}
