<?php

namespace Tests\Feature;

use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraPackageHotel;
use App\Models\HajjOmraRoomPrice;
use App\Models\HajjOmraServiceItem;
use App\Models\User;
use App\Services\BranchScopeService;
use App\Services\HajjOmra\HajjOmraPackageService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Refonte de l'editeur d'offres Hajj & Omra : bilingue FR/AR, structures commerciales
 * et integrite des donnees existantes.
 */
class HajjOmraOfferEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite est requis pour ce test isole.');
        }

        parent::setUp();
    }

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate(BranchScopeService::ROLE_SUPER_ADMIN, 'web');
        foreach ([AdminMenuPermissionRegistry::ADMIN_ACCESS_PERMISSION, 'hajj-omra.view'] as $name) {
            $role->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'title_fr' => 'Omra Ramadan 1448',
            'title_ar' => 'عمرة رمضان 1448',
            'type' => HajjOmraPackage::TYPE_OMRA,
            'status' => HajjOmraPackage::STATUS_PUBLISHED,
            'departure_city' => 'Casablanca',
            'destination' => 'Makkah / Madinah',
            'duration_days' => 14,
            'duration_nights' => 13,
            'start_date' => '2027-01-27',
            'return_date' => '2027-02-09',
            'short_description_fr' => 'Quatorze jours entre Makkah et Madinah.',
            'short_description_ar' => 'أربعة عشر يوما بين مكة والمدينة.',
            'description_fr' => 'Programme complet encadre.',
            'description_ar' => 'برنامج كامل مؤطر.',
            'adult_price' => 53500,
            'old_price' => 59000,
            'currency' => 'DH',
            'available_places' => 45,
            'reserved_places' => 37,
            'is_featured' => 1,
            'room_prices' => [
                ['room_type' => 'quadruple', 'price' => 53500, 'stock' => 20, 'is_active' => 1],
                ['room_type' => 'triple', 'price' => 58900, 'stock' => 15, 'is_active' => 1],
                ['room_type' => 'double', 'price' => 69900, 'stock' => 10, 'is_active' => 1],
            ],
            'departures' => [
                ['departure_date' => '2027-01-27', 'return_date' => '2027-02-09', 'departure_city' => 'Casablanca', 'status' => 'published', 'available_places' => 45, 'reserved_places' => 37],
            ],
            'hotels' => [
                ['city' => 'makkah', 'name' => 'Hotel Makkah Towers', 'stars' => 5, 'haram_distance' => '300 m', 'nights' => 7, 'description' => 'Face au Haram.', 'description_ar' => 'مقابل الحرم.'],
                ['city' => 'madinah', 'name' => 'Hotel Madinah Plaza', 'stars' => 4, 'haram_distance' => '150 m', 'nights' => 6],
            ],
            'service_items' => [
                ['kind' => 'included', 'label' => 'Billet d\'avion', 'label_ar' => 'تذكرة الطيران'],
                ['kind' => 'included', 'label' => 'Visa', 'label_ar' => 'التأشيرة'],
                ['kind' => 'excluded', 'label' => 'Depenses personnelles', 'label_ar' => 'المصاريف الشخصية'],
            ],
            'booking_conditions_fr' => 'Acompte de 30 % a la reservation.',
            'required_documents_fr' => 'Passeport valide 6 mois.',
            'meta_title_fr' => 'Omra Ramadan 1448',
            'sort_order' => 0,
        ], $overrides);
    }

    public function test_admin_can_open_the_editor_and_see_the_eight_steps(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.hajj-omra.create'));
        $response->assertOk();

        foreach (['Offre', 'Tarifs', 'Départs', 'Hébergement', 'Programme', 'Prestations', 'Médias', 'Publication'] as $step) {
            $response->assertSee($step, false);
        }

        // La bascule de langue est presente sur les deux langues.
        $response->assertSee('data-lang-switch="fr"', false);
        $response->assertSee('data-lang-switch="ar"', false);
        $response->assertSee('العربية', false);
    }

    public function test_it_creates_a_bilingual_offer_with_all_its_collections(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload())
            ->assertRedirect();

        $package = HajjOmraPackage::query()->firstOrFail();

        // Francais dans la colonne historique, arabe dans la colonne dediee : rien n'est melange.
        $this->assertSame('Omra Ramadan 1448', $package->title);
        $this->assertSame('عمرة رمضان 1448', $package->title_ar);
        $this->assertSame('أربعة عشر يوما بين مكة والمدينة.', $package->short_description_ar);

        $this->assertSame(3, $package->roomPrices()->count());
        $this->assertSame(1, $package->departures()->count());
        $this->assertSame(2, $package->hotels()->count());
        $this->assertSame(3, $package->serviceItems()->count());

        // Le programme est pre-rempli selon la duree, sans clic supplementaire.
        $this->assertSame(14, $package->programDays()->count());
        $this->assertSame([1, 14], [
            (int) $package->programDays()->min('day_number'),
            (int) $package->programDays()->max('day_number'),
        ]);
    }

    public function test_price_from_and_savings_use_the_cheapest_room(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());

        $package = HajjOmraPackage::query()->with(['roomPrices', 'departures'])->firstOrFail();

        $this->assertSame(53500.0, $package->price_from_value);
        // 59 000 - 53 500 = 5 500
        $this->assertSame(5500.0, $package->savings_value);
    }

    /**
     * Regression : l'ancien controleur supprimait puis recreait les departs a chaque
     * enregistrement, ce qui detachait les demandes de reservation deja recues.
     */
    public function test_saving_again_keeps_departure_ids_and_booking_request_links(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());

        $package = HajjOmraPackage::query()->firstOrFail();
        $departure = $package->departures()->firstOrFail();

        $booking = HajjOmraBookingRequest::query()->create([
            'package_id' => $package->id,
            'departure_id' => $departure->id,
            'full_name' => 'Mohamed',
            'phone' => '0600000000',
            'email' => 'mohamed@example.test',
        ]);

        // Reenregistrement en renvoyant l'identifiant du depart, comme le fait le formulaire.
        $payload = $this->payload();
        $payload['departures'][0]['id'] = $departure->id;
        $payload['departures'][0]['reserved_places'] = 40;

        $this->actingAs($admin)
            ->put(route('admin.hajj-omra.update', $package), $payload)
            ->assertRedirect();

        $this->assertSame($departure->id, $package->departures()->firstOrFail()->id);
        $this->assertSame(40, (int) $package->departures()->firstOrFail()->reserved_places);
        $this->assertSame($departure->id, (int) $booking->fresh()->departure_id);
    }

    /**
     * Les anciens champs plats et les JSON restent alimentes : l'API publique
     * consommee par WordPress continue de fonctionner sans modification.
     */
    public function test_legacy_fields_stay_in_sync_for_the_public_api(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());

        $package = HajjOmraPackage::query()->firstOrFail();

        $this->assertSame('Hotel Makkah Towers', $package->makkah_hotel);
        $this->assertSame('300 m', $package->makkah_haram_distance);
        $this->assertSame('Hotel Madinah Plaza', $package->madinah_hotel);
        $this->assertSame(['Billet d\'avion', 'Visa'], $package->included_items);
        $this->assertSame(['Depenses personnelles'], $package->excluded_items);
    }

    public function test_program_generation_is_idempotent_and_preserves_written_days(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());

        $package = HajjOmraPackage::query()->firstOrFail();
        $service = app(HajjOmraPackageService::class);

        $day3 = $package->programDays()->where('day_number', 3)->firstOrFail();
        $day3->update(['title' => 'Makkah', 'description' => 'Journee libre.']);

        // Relance : aucun jour en double, aucun contenu ecrase.
        $this->assertSame(0, $service->generateProgramDays($package));
        $this->assertSame(14, $package->programDays()->count());
        $this->assertSame('Makkah', $day3->fresh()->title);

        // Duree portee a 16 jours : seuls les jours 15 et 16 sont ajoutes.
        $this->assertSame(2, $service->generateProgramDays($package, 16));
        $this->assertSame(16, $package->programDays()->count());
        $this->assertSame('Makkah', $day3->fresh()->title);
    }

    public function test_server_side_validation_rejects_invalid_business_data(): void
    {
        $admin = $this->admin();

        // Titre francais manquant
        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload(['title_fr' => '']))
            ->assertSessionHasErrors('title');

        // Duree nulle
        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload(['duration_days' => 0]))
            ->assertSessionHasErrors('duration_days');

        // Retour avant depart
        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload(['return_date' => '2027-01-01']))
            ->assertSessionHasErrors('return_date');

        // Prix negatif
        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload(['adult_price' => -10]))
            ->assertSessionHasErrors('adult_price');

        // Plus de places reservees que disponibles
        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $this->payload(['available_places' => 10, 'reserved_places' => 40]))
            ->assertSessionHasErrors('reserved_places');

        $this->assertSame(0, HajjOmraPackage::query()->count());
    }

    /** L'arabe reste optionnel : les anciennes offres restent enregistrables. */
    public function test_arabic_is_optional_but_reported_as_missing(): void
    {
        $admin = $this->admin();

        $payload = $this->payload();
        unset($payload['title_ar'], $payload['short_description_ar'], $payload['description_ar']);

        $this->actingAs($admin)
            ->post(route('admin.hajj-omra.store'), $payload)
            ->assertSessionHasNoErrors();

        $package = HajjOmraPackage::query()->firstOrFail();

        $this->assertContains('title', $package->missingArabicFields());
        $this->assertFalse($package->isArabicComplete());

        // L'editeur affiche l'alerte, sans bloquer.
        $this->actingAs($admin)
            ->get(route('admin.hajj-omra.edit', $package))
            ->assertOk()
            ->assertSee('Traduction arabe non complétée', false);
    }

    /** Repli FR quand la traduction arabe manque, valeur arabe sinon. */
    public function test_localized_falls_back_to_french(): void
    {
        $package = new HajjOmraPackage(['title' => 'Omra', 'title_ar' => 'عمرة']);
        $this->assertSame('عمرة', $package->localized('title', 'ar'));
        $this->assertSame('Omra', $package->localized('title', 'fr'));

        $withoutArabic = new HajjOmraPackage(['title' => 'Omra']);
        $this->assertSame('Omra', $withoutArabic->localized('title', 'ar'));
    }

    public function test_preview_renders_in_french_and_arabic(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());
        $package = HajjOmraPackage::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.hajj-omra.preview', $package))
            ->assertOk()
            ->assertSee('Omra Ramadan 1448')
            ->assertSee('dir="ltr"', false);

        $arabic = $this->actingAs($admin)->get(route('admin.hajj-omra.preview', [$package, 'locale' => 'ar']));
        $arabic->assertOk()
            ->assertSee('عمرة رمضان 1448', false)
            ->assertSee('البرنامج', false)
            // Le bloc bascule en RTL...
            ->assertSee('dir="rtl"', false)
            // ...mais les montants restent isoles en LTR pour rester lisibles.
            ->assertSee('dir="ltr"', false);
    }

    public function test_removing_a_room_price_deletes_only_that_row(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.hajj-omra.store'), $this->payload());

        $package = HajjOmraPackage::query()->firstOrFail();
        $kept = $package->roomPrices()->where('room_type', 'quadruple')->firstOrFail();
        $removed = $package->roomPrices()->where('room_type', 'double')->firstOrFail();

        $payload = $this->payload();
        $payload['room_prices'] = [
            ['id' => $kept->id, 'room_type' => 'quadruple', 'price' => 54000, 'stock' => 18, 'is_active' => 1],
        ];

        $this->actingAs($admin)->put(route('admin.hajj-omra.update', $package), $payload);

        $this->assertSame(1, $package->roomPrices()->count());
        $this->assertDatabaseHas('hajj_omra_room_prices', ['id' => $kept->id, 'price' => 54000]);
        $this->assertDatabaseMissing('hajj_omra_room_prices', ['id' => $removed->id]);
    }

    public function test_room_types_include_quintuple(): void
    {
        $this->assertContains('quintuple', HajjOmraRoomPrice::ROOM_TYPES);
        $this->assertArrayHasKey('quintuple', HajjOmraRoomPrice::roomTypeOptions());
    }

    public function test_hotel_and_service_models_expose_arabic_labels(): void
    {
        $hotel = new HajjOmraPackageHotel(['description' => 'Face au Haram', 'description_ar' => 'مقابل الحرم']);
        $this->assertSame('مقابل الحرم', $hotel->localized('description', 'ar'));

        $item = new HajjOmraServiceItem(['label' => 'Visa', 'label_ar' => 'التأشيرة']);
        $this->assertSame('التأشيرة', $item->localized('label', 'ar'));
        $this->assertSame('Visa', $item->localized('label', 'fr'));
    }
}
