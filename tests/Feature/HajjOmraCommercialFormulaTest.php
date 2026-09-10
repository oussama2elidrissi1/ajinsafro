<?php

namespace Tests\Feature;

use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraPackage;
use App\Models\User;
use App\Services\BranchScopeService;
use App\Services\HajjOmra\HajjOmraCommercialPresenter;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HajjOmraCommercialFormulaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::findOrCreate(BranchScopeService::ROLE_SUPER_ADMIN, 'web');
        foreach ([AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'hajj-omra.view'] as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($user);
    }

    private function payload(): array
    {
        return [
            'title_fr' => 'Omra Ramadan', 'title_ar' => 'عمرة رمضان', 'type' => 'omra', 'status' => 'published',
            'duration_days' => 14, 'duration_nights' => 13, 'currency' => 'DH', 'adult_price' => 5,
            'hotels' => [
                ['client_key' => 'medina', 'city' => 'madinah', 'name' => 'Hôtel Médine', 'name_ar' => 'فندق المدينة', 'nights' => 4, 'haram_distance' => '500 m'],
                ['client_key' => 'makkah', 'city' => 'makkah', 'name' => 'Hôtel Makkah', 'name_ar' => 'فندق مكة', 'nights' => 9],
            ],
            'room_prices' => [
                ['client_key' => 'double', 'room_type' => 'double', 'price' => 16900, 'stock' => 10, 'is_active' => 1],
                ['client_key' => 'triple', 'room_type' => 'triple', 'price' => 15600, 'stock' => 10, 'is_active' => 1],
                ['client_key' => 'quad', 'room_type' => 'quadruple', 'price' => 14900, 'stock' => 10, 'is_active' => 1],
                ['client_key' => 'unlinked', 'room_type' => 'single', 'price' => 1, 'stock' => 10, 'is_active' => 1],
            ],
            'departures' => [
                ['client_key' => 'first', 'departure_date' => '2027-02-01', 'return_date' => '2027-02-14', 'status' => 'published', 'price_from' => 3, 'available_places' => 30],
                ['client_key' => 'second', 'departure_date' => '2027-03-01', 'return_date' => '2027-03-14', 'status' => 'published', 'available_places' => 30],
            ],
            'program_days' => [['client_key' => 'day5', 'day_number' => 5, 'title' => 'Makkah', 'title_ar' => 'مكة']],
            'formulas_present' => 1,
            'formulas' => [[
                'name_fr' => 'Programme touristique', 'name_ar' => 'البرنامج السياحي', 'is_active' => 1,
                'departure_id' => 'new:first', 'tariff_ids' => ['new:double', 'new:triple', 'new:quad'],
                'hotels' => [['hotel_id' => 'new:medina'], ['hotel_id' => 'new:makkah', 'program_day_id' => 'new:day5']],
            ]],
        ];
    }

    private function createOffer(?array $payload = null): HajjOmraPackage
    {
        $this->post(route('admin.hajj-omra.store'), $payload ?? $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        return HajjOmraPackage::latest('id')->firstOrFail()->load(HajjOmraCommercialPresenter::RELATIONS);
    }

    private function editing(HajjOmraPackage $package): array
    {
        $package->refresh()->load(HajjOmraCommercialPresenter::RELATIONS);
        $data = $package->getAttributes();
        foreach (['roomPrices' => 'room_prices', 'hotels' => 'hotels', 'departures' => 'departures', 'programDays' => 'program_days', 'serviceItems' => 'service_items'] as $relation => $field) {
            $data[$field] = $package->{$relation}->map(fn ($row) => $row->getAttributes())->all();
        }
        $data['formulas'] = $package->formulas->map(fn ($formula) => array_merge($formula->getAttributes(), [
            'tariff_ids' => $formula->tariffs->modelKeys(),
            'hotels' => $formula->stays->map(fn ($stay) => $stay->only(['hotel_id', 'program_day_id', 'nights_override']))->all(),
        ]))->all();
        $data['formulas_present'] = 1;
        return $data;
    }

    private function api(HajjOmraPackage $package): array
    {
        return $this->getJson('/api/public/hajj-omra/packages/'.$package->slug)->assertOk()->json('data');
    }

    public function test_formula_links_new_sources_and_derives_prices_and_stay_dates(): void
    {
        $offer = $this->createOffer();
        $data = $this->api($offer);
        $this->assertCount(1, $data['formulas']);
        $formula = $data['formulas'][0];
        $this->assertSame(['double', 'triple', 'quadruple'], array_keys($formula['prices']));
        $this->assertEquals(14900, $data['price_from']);
        $this->assertEquals(14900, $data['departures'][0]['price_from']);
        $this->assertNull($data['departures'][1]['price_from']);
        $this->assertSame('2027-02-05', $formula['accommodations'][1]['stay_start_date']);
        $this->assertSame('2027-02-14', $formula['accommodations'][1]['stay_end_date']);
        $this->assertSame($offer->hotels[0]->id, $formula['accommodations'][0]['id']);
        $this->assertSame($offer->roomPrices[0]->id, $formula['prices']['double']['tariff_id']);
        $this->assertArrayNotHasKey('internal_notes', $data['departures'][0]);
        $this->assertDatabaseCount('hajj_omra_formula_hotels', 2);
        $this->assertDatabaseCount('hajj_omra_formula_prices', 3);
    }

    public function test_multiple_formulas_same_city_hotels_and_single_room_type_are_supported(): void
    {
        $payload = $this->payload();
        $payload['hotels'][] = ['client_key' => 'premium', 'city' => 'makkah', 'name' => 'Hôtel Premium', 'nights' => 5];
        $payload['room_prices'][] = ['client_key' => 'premium', 'room_type' => 'double', 'price' => 21900, 'is_active' => 1, 'stock' => 10];
        $payload['formulas'][] = ['name_fr' => 'Premium', 'is_active' => 1, 'departure_id' => 'new:second', 'sort_order' => 2,
            'tariff_ids' => ['new:premium'], 'hotels' => [['hotel_id' => 'new:makkah', 'nights_override' => 3], ['hotel_id' => 'new:premium']]];
        $offer = $this->createOffer($payload);
        $data = $this->api($offer);
        $this->assertCount(2, $data['formulas']);
        $this->assertCount(1, $data['formulas'][1]['prices']);
        $this->assertSame('2027-03-04', $data['formulas'][1]['accommodations'][1]['stay_start_date']);
        $this->get(route('admin.hajj-omra.edit', $offer))->assertOk()->assertSee('Hôtel Premium')->assertSee('Hôtel Makkah');
        $this->put(route('admin.hajj-omra.update', $offer), $this->editing($offer))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('hajj_omra_package_hotels', 3);
        $this->assertDatabaseCount('hajj_omra_formulas', 2);
    }

    public function test_arabic_only_and_french_only_offers_render_the_same_shared_table_in_preview(): void
    {
        require_once base_path('wp-plugin/ajinsafro-traveler-home/includes/hajj-omra-commercial-table.php');
        foreach (['ar', 'fr'] as $locale) {
            $payload = $this->payload();
            $empty = $locale === 'ar' ? 'fr' : 'ar';
            $payload['title_'.$empty] = '';
            $payload['formulas'][0]['name_'.$empty] = '';
            foreach ($payload['hotels'] as &$hotel) $hotel[$locale === 'ar' ? 'name' : 'name_ar'] = '';
            unset($hotel);
            $offer = $this->createOffer($payload);
            $this->assertNotEmpty($offer->localized('title', $empty));
            $data = $this->api($offer);
            $html = \Ajinsafro\HajjOmra\CommercialTable::render($data['formulas'], $locale, 'DH');
            $this->get(route('admin.hajj-omra.preview', [$offer, 'locale' => $locale]))->assertOk()->assertSee($html, false);
            $this->assertStringContainsString($locale === 'ar' ? 'البرنامج السياحي' : 'Programme touristique', $html);
            $this->assertStringNotContainsString('Ø', $html);
            $this->assertStringContainsString($locale === 'ar' ? 'dir="rtl"' : 'dir="ltr"', $html);
        }
    }

    public function test_editing_sources_updates_api_and_keeps_formula_hotel_tariff_and_booking_ids(): void
    {
        config(['app.public_url' => 'https://wordpress.test', 'app.wp_invalidate_secret' => 'fixture-secret']);
        $offer = $this->createOffer();
        $formula = $offer->formulas[0];
        $tariff = $offer->roomPrices[2];
        $booking = HajjOmraBookingRequest::create(['package_id' => $offer->id, 'formula_id' => $formula->id, 'tariff_id' => $tariff->id,
            'departure_id' => $offer->departures[0]->id, 'full_name' => 'Test', 'phone' => '0600000000', 'email' => 'test@example.com', 'adults' => 1]);
        $data = $this->editing($offer);
        $data['room_prices'][2]['price'] = 15200;
        $data['hotels'][0]['name'] = 'Hôtel Médine rénové';
        $data['hotels'][0]['name_ar'] = 'فندق المدينة المجدد';
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasNoErrors();
        $api = $this->api($offer);
        $this->assertEquals(15200, $api['price_from']);
        $this->assertEquals(15200, $api['formulas'][0]['prices']['quadruple']['price']);
        $this->assertSame('Hôtel Médine rénové', $api['formulas'][0]['accommodations'][0]['name']);
        $this->assertSame('فندق المدينة المجدد', $api['formulas'][0]['accommodations'][0]['name_ar']);
        $this->assertSame($formula->id, $booking->fresh()->formula_id);
        $this->assertSame($tariff->id, $booking->fresh()->tariff_id);
        $this->assertDatabaseCount('hajj_omra_formulas', 1);
        Http::assertSent(fn ($request) => $request->url() === 'https://wordpress.test/wp-json/ajth/v1/invalidate-cache'
            && $request['key'] === 'ajth_hajj_omra_package_'.$offer->slug.'_v1');
        Http::assertSent(fn ($request) => $request['key'] === 'ajth_hajj_omra_packages_v1');
    }

    public function test_inactive_tariffs_and_formulas_disappear_without_falling_back_to_unlinked_prices(): void
    {
        $offer = $this->createOffer();
        $data = $this->editing($offer); $data['room_prices'][2]['is_active'] = 0;
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasNoErrors();
        $api = $this->api($offer);
        $this->assertArrayNotHasKey('quadruple', $api['formulas'][0]['prices']);
        $this->assertEquals(15600, $api['price_from']);
        $data = $this->editing($offer); $data['formulas'][0]['is_active'] = 0;
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasNoErrors();
        $api = $this->api($offer);
        $this->assertSame([], $api['formulas']); $this->assertTrue($api['has_formulas']); $this->assertNull($api['price_from']);
    }

    public function test_incomplete_active_formulas_and_invalid_references_fail_with_validation_and_rollback(): void
    {
        $payload = $this->payload(); $payload['formulas'][0]['tariff_ids'] = [];
        $this->post(route('admin.hajj-omra.store'), $payload)->assertSessionHasErrors('formulas.0.tariff_ids');
        $this->assertDatabaseCount('hajj_omra_packages', 0);
        $missing = $this->payload(); $missing['formulas'][0]['name_fr'] = ''; $missing['formulas'][0]['name_ar'] = '';
        $this->post(route('admin.hajj-omra.store'), $missing)->assertSessionHasErrors('formulas.0.name_fr');
        $missing = $this->payload(); $missing['formulas'][0]['hotels'] = [];
        $this->post(route('admin.hajj-omra.store'), $missing)->assertSessionHasErrors('formulas.0.hotels');
        $payload['status'] = 'draft';
        $offer = $this->createOffer($payload);
        $data = $this->editing($offer); $data['status'] = 'published';
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasErrors('formulas.0.tariff_ids');
        $this->assertSame('draft', $offer->fresh()->status);
        $data = $this->payload(); $data['formulas'][0]['hotels'][0]['hotel_id'] = $offer->hotels[0]->id;
        $this->post(route('admin.hajj-omra.store'), $data)->assertSessionHasErrors('formulas.0.hotels');
        $this->assertDatabaseCount('hajj_omra_packages', 1);
        $data = $this->payload(); $data['formulas'] = ['invalid'];
        $this->post(route('admin.hajj-omra.store'), $data)->assertSessionHasErrors('formulas.0');
    }

    public function test_reservation_validates_formula_tariff_and_departure_as_one_selection(): void
    {
        $offer = $this->createOffer(); $formula = $offer->formulas[0];
        $url = '/api/public/hajj-omra/packages/'.$offer->slug.'/booking-requests';
        $payload = ['full_name' => 'عميل', 'phone' => '0600000000', 'email' => 'guest@example.com', 'adults' => 2,
            'formula_id' => $formula->id, 'tariff_id' => $formula->tariffs[0]->id, 'departure_id' => $offer->departures[0]->id, 'locale' => 'ar'];
        $this->postJson($url, $payload)->assertCreated();
        $this->assertDatabaseHas('hajj_omra_booking_requests', ['formula_id' => $formula->id, 'tariff_id' => $formula->tariffs[0]->id,
            'departure_id' => $offer->departures[0]->id, 'room_type' => 'double']);
        $this->postJson($url, array_replace($payload, ['tariff_id' => $offer->roomPrices[3]->id]))->assertUnprocessable()->assertJsonValidationErrors('tariff_id');
        $this->postJson($url, array_replace($payload, ['departure_id' => $offer->departures[1]->id]))->assertUnprocessable()->assertJsonValidationErrors('departure_id');
        $formula->update(['is_active' => false]);
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('formula_id');
        $this->assertDatabaseCount('hajj_omra_booking_requests', 1);
    }

    public function test_legacy_offer_has_active_tariff_prices_and_no_required_formula(): void
    {
        $payload = $this->payload(); unset($payload['formulas']);
        $payload['room_prices'][3]['is_active'] = 0;
        $offer = $this->createOffer($payload);
        $api = $this->api($offer);
        $this->assertFalse($api['has_formulas']); $this->assertSame([], $api['formulas']);
        $this->assertEquals(14900, $api['price_from']);
        $this->postJson('/api/public/hajj-omra/packages/'.$offer->slug.'/booking-requests', [
            'full_name' => 'Legacy', 'email' => 'legacy@example.com', 'phone' => '0600000000', 'adults' => 1,
            'room_type' => 'double', 'selected_departure_date' => '2027-02-01',
        ])->assertCreated();
    }

    public function test_empty_formula_list_deletes_formulas_and_omitted_formulas_keep_links_safe(): void
    {
        $offer = $this->createOffer(); $data = $this->editing($offer);
        unset($data['formulas'], $data['formulas_present']); $data['hotels'] = [];
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasErrors('formulas');
        $this->assertDatabaseCount('hajj_omra_package_hotels', 2);
        $data = $this->editing($offer); unset($data['formulas']);
        $this->put(route('admin.hajj-omra.update', $offer), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('hajj_omra_formulas', 0);
    }

    public function test_catalog_query_count_does_not_grow_with_offer_count(): void
    {
        $this->createOffer();
        $count = function () {
            DB::enableQueryLog(); DB::flushQueryLog();
            $this->getJson('/api/public/hajj-omra/packages')->assertOk();
            $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'hajj_omra_') && str_starts_with(strtolower($query['query']), 'select'))->count();
            DB::disableQueryLog(); return $queries;
        };
        $first = $count(); $this->createOffer(); $this->createOffer();
        $this->assertSame($first, $count());
        $this->assertLessThanOrEqual(15, $first);
    }
}
