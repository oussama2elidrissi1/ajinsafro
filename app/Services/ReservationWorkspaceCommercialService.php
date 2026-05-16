<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TravelDeparturePlace;
use App\Models\VoyageDeparturePlace;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Enrichit les lignes du catalogue workspace avec des données commerciales
 * (KPIs, priorités, badges, recommandations) pour aider les commerciaux à vendre.
 */
class ReservationWorkspaceCommercialService
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  mixed  $user
     * @return array{rows: Collection<int, array<string, mixed>>, kpis: array<string, mixed>, assistant: array<string, mixed>}
     */
    public function enrichRows(Collection $rows, $user): array
    {
        $today = Carbon::today();

        $laravelVoyageIds = $rows->pluck('voyage_id')->filter()->unique()->values()->all();
        $wpPostIds = $rows->pluck('wp_post_id')->filter()->unique()->values()->all();

        $laravelCities = $this->batchLaravelDepartureCities($laravelVoyageIds);
        $wpCities = $this->batchWpDepartureCities($wpPostIds);
        $monthlySold = $this->calculateMonthlySoldSeats();

        // First pass: compute raw commercial data
        $enrichedRows = $rows->map(function (array $row) use ($today, $laravelCities, $wpCities) {
            return $this->enrichSingleRow($row, $today, $laravelCities, $wpCities);
        });

        // Second pass: assign TOP VENTE based on global percentile
        $enrichedRows = $this->assignTopVenteBadge($enrichedRows);

        $kpis = $this->computeKpis($enrichedRows, $today, $monthlySold);
        $assistant = $this->computeAssistant($enrichedRows, $today);

        return [
            'rows' => $enrichedRows,
            'kpis' => $kpis,
            'assistant' => $assistant,
        ];
    }

    private function enrichSingleRow(array $row, Carbon $today, array $laravelCities, array $wpCities): array
    {
        $hasLaravel = ! empty($row['voyage_id']);
        $wpPostId = $row['wp_post_id'] ?? null;
        $typeKey = $row['type'] ?? 'package';

        $placesTotal = $row['places_total'] ?? null;
        $stats = $row['stats'] ?? ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
        $soldGlobal = (int) ($stats['validee'] ?? 0);

        $modalDetail = $row['modal_detail'] ?? null;
        $departures = collect($modalDetail['departures'] ?? []);

        $nearestDeparture = null;
        $nearestDate = null;
        $daysUntil = null;
        $capacityTotal = $placesTotal;
        $placesSold = $soldGlobal;
        $placesRemaining = $placesTotal !== null ? max(0, $placesTotal - $soldGlobal) : null;
        $fillRate = null;
        $topDates = [];

        if ($departures->isNotEmpty()) {
            $futureDeps = $departures
                ->filter(function (array $d) use ($today) {
                    return ! empty($d['date_iso']) && $today->lte(Carbon::parse($d['date_iso']));
                })
                ->sortBy('date_iso')
                ->values();

            if ($futureDeps->isNotEmpty()) {
                $nearestDeparture = $futureDeps->first();
                $nearestDate = $nearestDeparture['date_iso'] ?? null;
                $daysUntil = $nearestDate ? (int) $today->diffInDays(Carbon::parse($nearestDate), false) : null;

                if (! empty($nearestDeparture['capacity']) && (int) $nearestDeparture['capacity'] > 0) {
                    $capacityTotal = (int) $nearestDeparture['capacity'];
                    $placesSold = (int) ($nearestDeparture['reservations']['validee'] ?? 0);
                    $placesRemaining = max(0, $capacityTotal - $placesSold);
                }
            }

            $topDates = $futureDeps->take(3)->map(function (array $d) {
                return [
                    'date_label' => $d['date_label'] ?? null,
                    'date_iso' => $d['date_iso'] ?? null,
                    'remaining' => $d['remaining'] ?? null,
                    'status_label' => $d['status_label'] ?? null,
                ];
            })->all();
        } elseif (! empty($row['departure_date'])) {
            $d = Carbon::parse($row['departure_date']);
            $nearestDate = $d->format('Y-m-d');
            $daysUntil = (int) $today->diffInDays($d, false);
        }

        if ($capacityTotal !== null && $capacityTotal > 0) {
            $fillRate = min(100, (int) round(($placesSold / $capacityTotal) * 100));
        }

        $cities = [];
        if ($hasLaravel && ! empty($laravelCities[(int) $row['voyage_id']])) {
            $cities = $laravelCities[(int) $row['voyage_id']];
        } elseif ($wpPostId && ! empty($wpCities[(int) $wpPostId])) {
            $cities = $wpCities[(int) $wpPostId];
        }
        $departureCity = implode(' / ', array_slice($cities, 0, 2));

        $priceSort = 0;
        $priceLabel = $row['price_label'] ?? '';
        if ($priceLabel !== '') {
            $digits = preg_replace('/[^\d]/', '', $priceLabel);
            $priceSort = $digits !== '' ? (int) $digits : 0;
        }

        $availStatus = $this->computeAvailabilityStatus($capacityTotal, $placesRemaining);
        $priority = $this->computePriority($daysUntil, $placesRemaining, $fillRate, $capacityTotal);
        $badge = $this->computeBadge($priority, $placesRemaining, $daysUntil, $fillRate, $placesSold);

        $row['commercial'] = [
            'capacity_total' => $capacityTotal,
            'places_vendues' => $placesSold,
            'places_restantes' => $placesRemaining,
            'taux_remplissage' => $fillRate,
            'prochaine_date_depart' => $nearestDate,
            'jours_avant_depart' => $daysUntil,
            'ville_depart' => $departureCity,
            'villes_list' => $cities,
            'prix_min' => $priceSort,
            'statut_disponibilite' => $availStatus,
            'priorite_vente' => $priority,
            'badge' => $badge,
            'top_dates' => $topDates,
            'has_future_departure' => $nearestDate !== null && $daysUntil !== null && $daysUntil >= 0,
        ];

        $row['data_commercial_priority'] = $priority;
        $row['data_commercial_badge'] = $badge ?? '';
        $row['data_departure_city'] = strtolower(str_replace(['/', ' '], [' ', ' '], $departureCity));
        $row['data_remaining'] = $placesRemaining ?? -1;
        $row['data_sold'] = $placesSold;
        $row['data_days_until'] = $daysUntil ?? 9999;
        $row['data_fill_rate'] = $fillRate ?? -1;
        $row['data_price'] = $priceSort;

        return $row;
    }

    private function assignTopVenteBadge(Collection $rows): Collection
    {
        $soldValues = $rows
            ->map(fn (array $r) => $r['commercial']['places_vendues'] ?? 0)
            ->filter(fn (int $v) => $v > 0)
            ->sort()
            ->values();

        $threshold = 0;
        if ($soldValues->count() > 0) {
            $index = (int) floor($soldValues->count() * 0.9);
            $threshold = $soldValues[$index] ?? $soldValues->last();
        }

        return $rows->map(function (array $row) use ($threshold) {
            $c = $row['commercial'] ?? [];
            $sold = $c['places_vendues'] ?? 0;
            $fillRate = $c['taux_remplissage'] ?? 0;
            $currentBadge = $c['badge'] ?? null;

            // If no urgent badge yet, check TOP VENTE criteria
            if ($currentBadge === null && $sold > 0) {
                if ($sold >= $threshold || ($fillRate > 85 && $sold >= 10)) {
                    $row['commercial']['badge'] = 'TOP VENTE';
                    $row['data_commercial_badge'] = 'TOP VENTE';
                }
            }

            return $row;
        });
    }

    private function computeAvailabilityStatus(?int $capacityTotal, ?int $placesRemaining): string
    {
        if ($capacityTotal === null || $capacityTotal <= 0) {
            return 'unknown';
        }
        if ($placesRemaining !== null && $placesRemaining <= 0) {
            return 'full';
        }
        if ($placesRemaining !== null && $placesRemaining <= 5) {
            return 'low';
        }
        if ($placesRemaining !== null && $placesRemaining <= 10) {
            return 'almost_full';
        }

        return 'ok';
    }

    private function computePriority(?int $daysUntil, ?int $placesRemaining, ?int $fillRate, ?int $capacityTotal): string
    {
        if ($capacityTotal === null || $capacityTotal <= 0) {
            return 'standard';
        }

        if ($placesRemaining !== null && $placesRemaining <= 0) {
            return 'watch';
        }

        if ($placesRemaining !== null && $placesRemaining <= 5 && $daysUntil !== null && $daysUntil <= 14 && $daysUntil >= 0) {
            return 'push_urgent';
        }

        if ($fillRate !== null && $fillRate >= 80) {
            return 'almost_full';
        }

        if ($placesRemaining !== null && $placesRemaining > 20 && $daysUntil !== null && $daysUntil <= 60 && $daysUntil >= 0) {
            return 'high_potential';
        }

        if ($placesRemaining !== null && $placesRemaining > 30) {
            return 'promote';
        }

        if ($daysUntil === null || $daysUntil > 90 || $daysUntil < 0) {
            return 'watch';
        }

        return 'standard';
    }

    private function computeBadge(string $priority, ?int $placesRemaining, ?int $daysUntil, ?int $fillRate, int $placesSold): ?string
    {
        if ($placesRemaining !== null && $placesRemaining <= 0) {
            return null;
        }

        if ($priority === 'push_urgent') {
            return 'À POUSSER';
        }

        if ($priority === 'almost_full') {
            return 'FAIBLE STOCK';
        }

        if ($daysUntil !== null && $daysUntil >= 0 && $daysUntil <= 7) {
            return 'DÉPART PROCHE';
        }

        if ($priority === 'high_potential') {
            return 'FORT POTENTIEL';
        }

        if ($priority === 'promote') {
            return 'DISPONIBLE';
        }

        return null;
    }

    private function batchLaravelDepartureCities(array $voyageIds): array
    {
        if ($voyageIds === []) {
            return [];
        }

        return VoyageDeparturePlace::query()
            ->whereIn('voyage_id', $voyageIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('voyage_id')
            ->map(fn (Collection $group) => $group->pluck('name')->filter()->values()->all())
            ->all();
    }

    private function batchWpDepartureCities(array $wpPostIds): array
    {
        if ($wpPostIds === []) {
            return [];
        }

        return TravelDeparturePlace::query()
            ->whereIn('travel_id', $wpPostIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('travel_id')
            ->map(fn (Collection $group) => $group->pluck('name')->filter()->values()->all())
            ->all();
    }

    private function calculateMonthlySoldSeats(): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return (int) Reservation::query()
            ->where('status', Reservation::STATUS_VALIDEE)
            ->whereDate('created_at', '>=', $startOfMonth)
            ->sum('passengers_count');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function computeKpis(Collection $rows, Carbon $today, int $monthlySold): array
    {
        $nearDepartures = 0;
        $lowStock = 0;
        $pushCount = 0;
        $allCities = [];
        $totalRemaining = 0;

        foreach ($rows as $row) {
            $c = $row['commercial'] ?? [];
            $daysUntil = $c['jours_avant_depart'] ?? null;
            $remaining = $c['places_restantes'] ?? null;
            $priority = $c['priorite_vente'] ?? 'standard';

            if ($daysUntil !== null && $daysUntil >= 0 && $daysUntil <= 7 && $remaining !== null && $remaining > 0) {
                $nearDepartures++;
            }

            if ($remaining !== null && $remaining < 10 && $remaining > 0) {
                $lowStock++;
            }

            if (in_array($priority, ['push_urgent', 'almost_full', 'high_potential'], true)) {
                $pushCount++;
            }

            foreach ($c['villes_list'] ?? [] as $city) {
                if ($city !== '') {
                    $allCities[$city] = true;
                }
            }

            if ($remaining !== null && $remaining > 0) {
                $totalRemaining += $remaining;
            }
        }

        return [
            'near_departures' => $nearDepartures,
            'low_stock' => $lowStock,
            'push_count' => $pushCount,
            'cities_count' => count($allCities),
            'monthly_sold' => $monthlySold,
            'total_remaining' => $totalRemaining,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function computeAssistant(Collection $rows, Carbon $today): array
    {
        $nearCodes = [];
        $lowStockCodes = [];
        $highPotentialCodes = [];
        $cityCounts = [];

        foreach ($rows as $row) {
            $c = $row['commercial'] ?? [];
            $code = $row['code'] ?? '';
            $daysUntil = $c['jours_avant_depart'] ?? null;
            $remaining = $c['places_restantes'] ?? null;
            $priority = $c['priorite_vente'] ?? 'standard';

            if ($daysUntil !== null && $daysUntil >= 0 && $daysUntil <= 7 && $remaining !== null && $remaining > 0) {
                $nearCodes[] = $code;
            }

            if ($remaining !== null && $remaining <= 10 && $remaining > 0) {
                $lowStockCodes[] = $code;
            }

            if ($priority === 'high_potential' || ($remaining !== null && $remaining > 20 && $daysUntil !== null && $daysUntil <= 60 && $daysUntil >= 0)) {
                $highPotentialCodes[] = $code;
            }

            foreach ($c['villes_list'] ?? [] as $city) {
                if ($city !== '') {
                    $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
                }
            }
        }

        arsort($cityCounts);
        $topCities = array_slice($cityCounts, 0, 5, true);

        return [
            'near_departures_codes' => $nearCodes,
            'low_stock_codes' => $lowStockCodes,
            'high_potential_codes' => $highPotentialCodes,
            'top_cities' => $topCities,
        ];
    }
}
