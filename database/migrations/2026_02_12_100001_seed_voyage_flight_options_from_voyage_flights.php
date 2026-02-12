<?php

use App\Models\VoyageFlightOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time: copy existing voyage_flights (one outbound, one inbound) into voyage_flight_options
     * so existing tours keep working with the new multi-flight UI.
     */
    public function up(): void
    {
        if (!Schema::hasTable('voyage_flights') || !Schema::hasTable('voyage_flight_options')) {
            return;
        }

        $rows = DB::table('voyage_flights')->orderBy('voyage_id')->orderBy('direction')->get();
        $lastDayByVoyage = $this->getLastDayByVoyage();

        foreach ($rows as $r) {
            $dayNumber = $r->direction === 'outbound' ? 1 : ($lastDayByVoyage[$r->voyage_id] ?? 1);
            $type = $r->direction === 'inbound' ? VoyageFlightOption::TYPE_RETURN : VoyageFlightOption::TYPE_OUTBOUND;
            $departAt = $r->departure_date ? $r->departure_date . ' 00:00:00' : null;

            VoyageFlightOption::create([
                'voyage_id' => $r->voyage_id,
                'type' => $type,
                'day_number' => $dayNumber,
                'from_city' => $r->from_city,
                'to_city' => $r->to_city,
                'depart_at' => $departAt,
                'arrive_at' => $departAt,
                'airline_id' => $r->airline_id,
                'flight_number' => $r->flight_number,
                'cabin' => $r->cabin ?? 'economy',
                'baggage_cabin_kg' => $r->baggage_cabin_kg,
                'baggage_checkin_kg' => $r->baggage_checkin_kg,
                'is_included' => true,
                'is_optional' => false,
                'group_key' => $type === VoyageFlightOption::TYPE_OUTBOUND ? 'OUTBOUND' : 'RETURN',
                'sort_order' => $type === VoyageFlightOption::TYPE_OUTBOUND ? 0 : 1,
                'is_tentative' => (bool) $r->is_tentative,
                'notes' => null,
            ]);
        }
    }

    private function getLastDayByVoyage(): array
    {
        $out = [];
        if (!Schema::connection('wp')->hasTable('aj_tour_days')) {
            return $out;
        }
        $wp = DB::connection('wp');
        $tours = DB::table('voyages')->whereNotNull('wp_post_id')->pluck('id', 'wp_post_id');
        foreach ($tours as $wpPostId => $voyageId) {
            $n = $wp->table('aj_tour_days')->where('tour_id', $wpPostId)->count();
            $out[$voyageId] = $n > 0 ? $n : 1;
        }
        return $out;
    }

    public function down(): void
    {
        if (Schema::hasTable('voyage_flight_options')) {
            DB::table('voyage_flight_options')->truncate();
        }
    }
};
