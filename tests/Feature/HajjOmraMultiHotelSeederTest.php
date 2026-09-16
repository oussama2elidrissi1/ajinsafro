<?php

namespace Tests\Feature;

use App\Models\HajjOmraPackage;
use Database\Seeders\HajjOmraMultiHotelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'offre de demonstration doit reellement produire le parcours vise :
 * une date -> plusieurs hebergements -> un « a partir de » qui suit l'hebergement choisi.
 */
class HajjOmraMultiHotelSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedOffer(): HajjOmraPackage
    {
        $this->seed(HajjOmraMultiHotelSeeder::class);

        return HajjOmraPackage::where('slug', 'omra-ramadan-multi-hebergements')->firstOrFail();
    }

    private function api(HajjOmraPackage $package): array
    {
        return $this->getJson('/api/public/hajj-omra/packages/'.$package->slug)->assertOk()->json('data');
    }

    public function test_each_departure_offers_two_hotel_options_with_their_own_tariffs(): void
    {
        $data = $this->api($this->seedOffer());

        $this->assertCount(2, $data['departures']);
        $this->assertCount(4, $data['formulas']);

        foreach ($data['departures'] as $departure) {
            $options = array_values(array_filter($data['formulas'], fn ($formula) => $formula['departure_id'] === $departure['id']));
            $this->assertCount(2, $options, 'Chaque date propose deux hebergements.');

            foreach ($options as $option) {
                $this->assertNotEmpty($option['accommodations'], 'Chaque option porte ses hebergements.');
                $this->assertSame(['quadruple', 'triple', 'double'], array_keys($option['prices']));
            }

            // Le « a partir de » de la date est le plus bas des options qui la desservent.
            $lowest = min(array_map(fn ($option) => min(array_column($option['prices'], 'price')), $options));
            $this->assertEquals($lowest, $departure['price_from']);
        }
    }

    public function test_options_and_departures_carry_distinct_prices(): void
    {
        $data = $this->api($this->seedOffer());

        $from = [];
        foreach ($data['formulas'] as $formula) {
            $from[$formula['name_fr'].'|'.$formula['departure_date']] = min(array_column($formula['prices'], 'price'));
        }

        // Les montants ronds reviennent en entiers dans le JSON : comparaison par valeur.
        $this->assertEquals([
            'Confort — Emaar Al Khalil / Saja Al Madinah|2026-11-28' => 21900,
            'Confort — Emaar Al Khalil / Saja Al Madinah|2026-12-05' => 22900,
            'Premium — Swissotel Al Maqam / Dar Al Iman|2026-11-28' => 25900,
            'Premium — Swissotel Al Maqam / Dar Al Iman|2026-12-05' => 26900,
        ], $from);

        // Champ « Prix a partir de » laisse vide : la vitrine affiche l'option la moins chere.
        $this->assertEquals(21900, $data['price_from']);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_anything(): void
    {
        $this->seedOffer();
        $package = $this->seedOffer();

        $this->assertSame(1, HajjOmraPackage::where('slug', 'omra-ramadan-multi-hebergements')->count());
        $this->assertSame(4, $package->formulas()->count());
        $this->assertSame(12, $package->roomPrices()->count());
        $this->assertSame(4, $package->hotels()->count());
        $this->assertSame(2, $package->departures()->count());
    }
}
