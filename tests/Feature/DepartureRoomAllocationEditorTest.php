<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\ReservationRoomAllocation;
use App\Models\TourHotel;
use App\Models\User;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DepartureRoomAllocationEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['logging.default' => 'null']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::findOrCreate(BranchScopeService::ROLE_SUPER_ADMIN, 'web');
        foreach ([AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'circuits.voyages.view'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create(); $user->assignRole($role); $this->actingAs($user);
        if (! Schema::connection('wp')->hasTable('aj_tour_hotels')) {
            Schema::connection('wp')->create('aj_tour_hotels', function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('tour_id'); $table->string('hotel_name'); $table->integer('sort_order')->default(0); $table->timestamps();
            });
        }
    }

    private function departure(): Departure
    {
        $voyage = Voyage::create(['name' => 'Égypte', 'slug' => 'egypte-'.uniqid(), 'price_from' => 17950]);
        $departure = $voyage->departures()->create(['start_date' => '2026-10-04', 'status' => 'open', 'total_capacity' => 20, 'available_capacity' => 20]);
        $departure->roomAllocations()->create($this->room());

        return $departure;
    }

    private function room(array $overrides = []): array
    {
        return array_replace(['room_type' => 'Double', 'quantity' => 10, 'capacity_per_room' => 2, 'supplement' => 0, 'hotel_id' => null], $overrides);
    }

    private function url(Departure $departure): string
    {
        return route('admin.circuits.voyages.departures.room-allocations.show', [$departure->voyage_id, $departure->id]);
    }

    public function test_edit_keeps_source_ids_updates_preview_and_only_changes_the_selected_departure(): void
    {
        $departure = $this->departure(); $other = $this->departure();
        $data = $this->getJson($this->url($departure))->assertOk()->json();
        $sourceId = $data['rooms'][0]['id'];
        $data['rooms'][0]['quantity'] = 8; $data['rooms'][0]['supplement'] = 250;
        $data['rooms'][] = $this->room(['room_type' => 'Single', 'quantity' => 4, 'capacity_per_room' => 1]);
        $saved = $this->putJson($this->url($departure), $data)->assertOk()
            ->assertJsonPath('rooms.0.id', $sourceId)->assertJsonPath('rooms.0.quantity', 8)
            ->assertJsonPath('availability.rooms_source', 'departure_room_allocations')
            ->assertJsonPath('availability.rooms.0.room_source_id', $sourceId)
            ->assertJsonPath('availability.rooms.0.available_rooms', 8)
            ->assertJsonPath('availability.rooms.0.unit_supplement', 250)->json();
        $this->assertNotSame($data['revision'], $saved['revision']);
        $this->assertSame(10, $other->roomAllocations()->first()->quantity);
        $this->assertSame(20, $departure->fresh()->total_capacity);
        $this->getJson($this->url($departure))->assertJsonPath('rooms.0.supplement', '250.00');
        $saved['rooms'] = [$saved['rooms'][0]];
        $this->putJson($this->url($departure), $saved)->assertOk()->assertJsonCount(1, 'rooms');
    }

    public function test_foreign_departures_rows_and_hotels_are_rejected_without_partial_changes(): void
    {
        $departure = $this->departure(); $other = $this->departure();
        $this->getJson(route('admin.circuits.voyages.departures.room-allocations.show', [$other->voyage_id, $departure->id]))->assertNotFound();
        $data = $this->getJson($this->url($departure))->json();
        $data['rooms'][0]['quantity'] = 7;
        $data['rooms'][] = $this->room(['id' => $other->roomAllocations()->first()->id]);
        $this->putJson($this->url($departure), $data)->assertUnprocessable()->assertJsonValidationErrors('rooms.1.id');
        $this->assertSame(10, $departure->roomAllocations()->first()->quantity);
        $data['rooms'] = [$data['rooms'][0]]; $data['rooms'][0]['hotel_id'] = 999999;
        $this->putJson($this->url($departure), $data)->assertUnprocessable()->assertJsonValidationErrors('rooms.0.hotel_id');
    }

    public function test_stale_edits_cannot_overwrite_a_newer_save(): void
    {
        $departure = $this->departure(); $data = $this->getJson($this->url($departure))->json();
        $data['rooms'][0]['quantity'] = 9;
        $this->putJson($this->url($departure), $data)->assertOk();
        $data['rooms'][0]['quantity'] = 8;
        $this->putJson($this->url($departure), $data)->assertConflict();
        $this->assertSame(9, $departure->roomAllocations()->first()->quantity);
    }

    public function test_hotel_application_is_shared_with_the_voyage_editor_and_overrides_generic_rooms(): void
    {
        $departure = $this->departure();
        $hotel = TourHotel::create(['tour_id' => $departure->voyage_id, 'hotel_name' => 'Hôtel du Nil']);
        $generic = $departure->departureHotels()->create(['hotel_name' => 'Ancien stock', 'is_active' => true]);
        $generic->rooms()->create(['room_type' => 'Twin', 'capacity_total' => 2, 'total_rooms' => 3, 'available_rooms' => 3, 'status' => 'available']);
        $data = $this->getJson($this->url($departure))->assertJsonPath('hotels.0.hotel_name', 'Hôtel du Nil')->json();
        $data['rooms'][0]['hotel_id'] = $hotel->id;
        $this->putJson($this->url($departure), $data)->assertOk()
            ->assertJsonPath('rooms.0.hotel_id', $hotel->id)
            ->assertJsonPath('availability.rooms.0.hotel_name', 'Hôtel du Nil')
            ->assertJsonPath('availability.rooms.0.room_type', 'Double');
    }

    public function test_booked_rooms_cannot_be_deleted_retyped_or_reduced_below_usage(): void
    {
        $departure = $this->departure(); $data = $this->getJson($this->url($departure))->json();
        $reservation = Reservation::create(['tour_id' => $departure->voyage_id, 'voyage_id' => $departure->voyage_id, 'departure_id' => $departure->id, 'status' => 'confirmed', 'passengers_count' => 2]);
        ReservationRoomAllocation::create(['reservation_id' => $reservation->id, 'travel_date_id' => 1, 'tour_hotel_id' => 0, 'tour_hotel_room_id' => 0, 'room_source_type' => 'departure_room_allocation', 'room_source_id' => $data['rooms'][0]['id'], 'rooms_total_count' => 1, 'capacity' => 2, 'occupied_count' => 2, 'supplement_total' => 0]);
        foreach ([['quantity' => 0], ['capacity_per_room' => 1], ['room_type' => 'Single']] as $change) {
            $payload = $data; $payload['rooms'][0] = array_replace($payload['rooms'][0], $change);
            $this->putJson($this->url($departure), $payload)->assertUnprocessable();
        }
        $payload = $data; $payload['rooms'] = [$this->room(['room_type' => 'Single'])];
        $this->putJson($this->url($departure), $payload)->assertUnprocessable();
        $this->assertSame(1, $departure->roomAllocations()->count());
        $data['rooms'][0]['quantity'] = 1; $data['rooms'][0]['supplement'] = 50;
        $this->putJson($this->url($departure), $data)->assertOk()
            ->assertJsonPath('availability.rooms.0.available_rooms', 0)
            ->assertJsonPath('availability.rooms.0.used_rooms', 1);
        $this->assertSame('0.00', $reservation->roomAllocations()->first()->supplement_total);
    }

    public function test_empty_or_invalid_values_are_rejected_and_zero_quantity_blocks_availability(): void
    {
        $departure = $this->departure(); $data = $this->getJson($this->url($departure))->json();
        foreach ([[], [$this->room(['quantity' => -1])], [$this->room(['capacity_per_room' => 0])], [$this->room(['supplement' => -1])]] as $rows) {
            $this->putJson($this->url($departure), array_replace($data, ['rooms' => $rows]))->assertUnprocessable();
        }
        $data['rooms'][0]['quantity'] = 0;
        $this->putJson($this->url($departure), $data)->assertOk()->assertJsonPath('availability.mode', 'blocked')->assertJsonCount(0, 'availability.rooms');
    }

    public function test_voyage_permission_is_required_for_read_and_write(): void
    {
        $departure = $this->departure(); $url = $this->url($departure); $data = $this->getJson($url)->json();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate(AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'web'));
        $this->actingAs($user)->getJson($url)->assertForbidden();
        $this->putJson($url, $data)->assertForbidden();
    }
}
