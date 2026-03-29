<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Validation métier workspace : mêmes sources que {@see ReservationWorkspaceCatalogService} (prix, places, dates).
 */
final class ReservationWorkspaceBookingService
{
    public function __construct(
        private ReservationWorkspaceCatalogService $catalog,
        private BranchScopeService $branchScope,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $passengersNormalized
     * @param  array<int, mixed>  $extrasPayload
     * @return array{authoritative_total: float, resolved_travel_date_id: ?int, booking_snapshot: array<string, mixed>}
     */
    public function validateWorkspaceStoreAndResolveTotals(
        Request $request,
        User $user,
        array $passengersNormalized,
        array $extrasPayload,
    ): array {
        $canonicalTourId = Voyage::canonicalVoyageId((int) $request->input('tour_id'));
        $request->merge(['tour_id' => $canonicalTourId]);
        $voyage = Voyage::query()->findOrFail($canonicalTourId);
        $prestationType = $request->string('prestation_type')->toString();
        $travelDateFromRequest = $request->filled('travel_date_id') ? (int) $request->input('travel_date_id') : null;

        $row = $this->catalog->findCatalogRowForBooking($voyage, $prestationType, $user);
        if ($row === null) {
            throw ValidationException::withMessages([
                'tour_id' => ['Ce voyage n’apparaît pas dans le catalogue workspace (liaison WordPress / Laravel).'],
            ]);
        }

        $prefill = $row['form_prefill'] ?? null;
        if (! is_array($prefill)) {
            throw ValidationException::withMessages([
                'tour_id' => ['Données catalogue indisponibles pour ce voyage.'],
            ]);
        }

        $travelDateId = $this->resolvePersistedTravelDateId($travelDateFromRequest, $prestationType, $prefill);

        $this->validateTravelDate($voyage, $travelDateId, $prefill, $prestationType);

        $paxCount = max(1, count($passengersNormalized));
        $this->assertPlacesAvailable($prefill, $paxCount, $voyage, $travelDateId, $user);

        $counts = $this->countPassengerTypes($passengersNormalized);
        $serverTotal = $this->computeExpectedTotal($voyage, $prefill, $counts, $extrasPayload, $prestationType, $passengersNormalized);

        $clientTotal = round((float) $request->input('montant_total'), 2);
        if (abs($serverTotal - $clientTotal) > 2.0) {
            $cur = data_get($prefill, 'prices.currency', 'MAD');
            throw ValidationException::withMessages([
                'montant_total' => [
                    'Total incohérent avec le barème catalogue (serveur : '.number_format($serverTotal, 2, ',', ' ').' '.$cur.', reçu : '.number_format($clientTotal, 2, ',', ' ').' '.$cur.'). Vérifiez participants et extras.',
                ],
            ]);
        }

        return [
            'authoritative_total' => $serverTotal,
            'resolved_travel_date_id' => $travelDateId,
            'booking_snapshot' => [
                'catalog_row_code' => $row['code'] ?? null,
                'catalog_type' => $row['type'] ?? null,
                'wp_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
                'laravel_voyage_id' => (int) $voyage->id,
                'voyage_name' => $row['name'] ?? $voyage->name,
                'prestation_type' => $prestationType,
                'travel_date_id' => $travelDateId,
                'passenger_counts' => $counts,
                'passengers_submitted' => $paxCount,
                'prices_applied' => $prefill['prices'] ?? [],
                'places_catalog' => $prefill['places'] ?? [],
                'rooms_catalog' => $prefill['rooms'] ?? [],
                'server_total' => $serverTotal,
                'client_total' => $clientTotal,
                'computed_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Réconcilie la date de départ persistée : requête POST, défaut catalogue, ou seule date disponible.
     * Évite les réservations enregistrées sans {@see TravelDate} id alors que le catalogue / le lien participants en ont une.
     *
     * @param  array<string, mixed>  $prefill
     */
    private function resolvePersistedTravelDateId(?int $fromRequest, string $prestationType, array $prefill): ?int
    {
        if ($fromRequest !== null && $fromRequest > 0) {
            return $fromRequest;
        }

        $defaultId = isset($prefill['default_travel_date_id']) ? (int) $prefill['default_travel_date_id'] : 0;
        if ($defaultId > 0) {
            return $defaultId;
        }

        $formTd = isset($prefill['form']['travel_date_id']) ? (int) $prefill['form']['travel_date_id'] : 0;
        if ($formTd > 0) {
            return $formTd;
        }

        $dates = $prefill['travel_dates'] ?? [];
        if (is_array($dates) && count($dates) === 1) {
            $only = $dates[0];
            if (is_array($only)) {
                $id = isset($only['id']) ? (int) $only['id'] : 0;
                if ($id > 0) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function validateTravelDate(Voyage $voyage, ?int $travelDateId, array $prefill, string $prestationType): void
    {
        $dates = $prefill['travel_dates'] ?? [];
        if ($travelDateId === null || $travelDateId <= 0) {
            if ($prestationType === 'package' && count($dates) > 0) {
                throw ValidationException::withMessages([
                    'travel_date_id' => ['Sélectionnez une date de départ parmi les départs disponibles.'],
                ]);
            }

            return;
        }

        $td = TravelDate::query()->find($travelDateId);
        if (! $td) {
            throw ValidationException::withMessages([
                'travel_date_id' => ['Date de départ invalide.'],
            ]);
        }
        ReservationLinkResolver::assertTravelDateBelongsToVoyage($voyage, $td);
        if (! $td->is_active) {
            throw ValidationException::withMessages([
                'travel_date_id' => ['Cette date n’est plus active.'],
            ]);
        }
    }

    private function assertPlacesAvailable(
        array $prefill,
        int $paxCount,
        Voyage $voyage,
        ?int $travelDateId,
        User $user,
    ): void {
        $places = $prefill['places'] ?? [];
        $state = $places['state'] ?? '';
        if ($state !== 'ok') {
            return;
        }
        $total = isset($places['total']) ? (int) $places['total'] : null;
        if ($total === null || $total <= 0) {
            return;
        }

        $remaining = $this->resolveRemainingSeats($voyage, $travelDateId, $total, $user);
        if ($remaining === null) {
            return;
        }
        if ($paxCount > $remaining) {
            throw ValidationException::withMessages([
                'passengers' => [
                    'Capacité insuffisante : '.$remaining.' place(s) disponible(s) pour ce départ, '.$paxCount.' voyageur(s) demandé(s).',
                ],
            ]);
        }
    }

    private function resolveRemainingSeats(Voyage $voyage, ?int $travelDateId, int $totalCapacity, User $user): ?int
    {
        $tourPhysicalIds = Voyage::allIdsSharingWpTour((int) $voyage->id);
        $q = Reservation::query()
            ->whereIn('tour_id', $tourPhysicalIds)
            ->whereIn('status', [Reservation::STATUS_EN_COURS, Reservation::STATUS_VALIDEE]);
        if ($travelDateId !== null && $travelDateId > 0) {
            $q->where('travel_date_id', $travelDateId);
        }
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);
        $booked = (int) (clone $q)->sum('passengers_count');

        return max(0, $totalCapacity - $booked);
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengersNormalized
     * @return array{adult: int, child: int, infant: int}
     */
    private function countPassengerTypes(array $passengersNormalized): array
    {
        $c = ['adult' => 0, 'child' => 0, 'infant' => 0];
        foreach ($passengersNormalized as $p) {
            if (! is_array($p)) {
                continue;
            }
            $t = (string) ($p['type'] ?? 'adult');
            if (! isset($c[$t])) {
                $t = 'adult';
            }
            $c[$t]++;
        }
        if (array_sum($c) === 0) {
            $c['adult'] = 1;
        }

        return $c;
    }

    /**
     * @param  array<int, mixed>  $extrasPayload
     * @param  array<int, array<string, mixed>>  $passengersNormalized
     */
    private function computeExpectedTotal(
        Voyage $voyage,
        array $prefill,
        array $counts,
        array $extrasPayload,
        string $prestationType,
        array $passengersNormalized,
    ): float {
        $pr = $prefill['prices'] ?? [];
        $adult = isset($pr['adult_amount']) && is_numeric($pr['adult_amount']) ? (float) $pr['adult_amount'] : null;
        $child = isset($pr['child_amount']) && is_numeric($pr['child_amount']) ? (float) $pr['child_amount'] : null;

        if ($adult === null || $adult <= 0) {
            if ($voyage->price_from !== null && (float) $voyage->price_from > 0) {
                $adult = (float) $voyage->price_from;
            } else {
                $adult = match ($prestationType) {
                    'vol' => 4000.0,
                    'hebergement' => 5000.0,
                    default => 15000.0,
                };
            }
        }
        if ($child === null || $child < 0) {
            $child = round($adult * 0.75, 2);
        }
        $inf = 0.0;

        $base = $counts['adult'] * $adult + $counts['child'] * $child + $counts['infant'] * $inf;

        $extras = $this->computeExtrasTotalFromCatalog($prefill, $passengersNormalized, $extrasPayload);

        return round($base + $extras, 2);
    }

    /**
     * Recalcule les extras depuis {@see ReservationWorkspaceCatalogService} extras_catalog + types de passagers (pas le montant client).
     *
     * @param  array<int, array<string, mixed>>  $passengersNormalized
     * @param  array<int, mixed>  $extrasPayload
     */
    private function computeExtrasTotalFromCatalog(array $prefill, array $passengersNormalized, array $extrasPayload): float
    {
        $catalog = $prefill['extras_catalog'] ?? [];
        $byId = [];
        foreach ($catalog as $e) {
            if (isset($e['id'])) {
                $byId[(string) $e['id']] = $e;
            }
        }
        $sum = 0.0;
        foreach ($extrasPayload as $ex) {
            if (! is_array($ex)) {
                continue;
            }
            $vid = (string) ($ex['voyage_extra_id'] ?? '');
            if ($vid === '') {
                continue;
            }
            $def = $byId[$vid] ?? null;
            if (! is_array($def)) {
                continue;
            }
            $paxKey = (string) ($ex['pax'] ?? '');
            $ptype = $this->resolveWorkspacePaxTypeForKey($paxKey, $passengersNormalized);
            $unit = match ($ptype) {
                'child' => (float) ($def['price_child'] ?? 0),
                'infant' => 0.0,
                default => (float) ($def['price_adult'] ?? 0),
            };
            $sum += $unit;
        }

        return round($sum, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengersNormalized
     */
    private function resolveWorkspacePaxTypeForKey(string $key, array $passengersNormalized): string
    {
        if ($key === 'titulaire' || $key === '') {
            return (string) ($passengersNormalized[0]['type'] ?? 'adult');
        }
        if (preg_match('/^comp_(\d+)$/', $key, $m)) {
            $i = (int) $m[1] + 1;

            return (string) ($passengersNormalized[$i]['type'] ?? 'adult');
        }

        return 'adult';
    }
}
