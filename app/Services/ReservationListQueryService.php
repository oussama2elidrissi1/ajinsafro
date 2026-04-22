<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Source unique pour les listes admin : même périmètre d’accès (agence / portail)
 * et mêmes filtres voyage / date de départ que la vue participants workspace.
 * Filtre voyage : tous les enregistrements {@see Voyage} partageant le même {@see Voyage::wp_post_id}
 * (évite les réservations « invisibles » si plusieurs ids Laravel pour un même tour WordPress).
 */
final class ReservationListQueryService
{
    private ?bool $reservationsHasChannelColumn = null;

    public function __construct(
        private BranchScopeService $branchScope,
    ) {}

    public function baseQuery(User $user, array $context = []): Builder
    {
        $ctx = [
            'tour_id' => (int) ($context['tour_id'] ?? 0),
            'travel_date_id' => (int) ($context['travel_date_id'] ?? 0),
            'departure_id' => (int) ($context['departure_id'] ?? 0),
            'channel' => trim((string) ($context['channel'] ?? '')),
            'catalog_source_code' => trim((string) ($context['catalog_source_code'] ?? '')),
            'shared_operational_aggregate' => ! empty($context['shared_operational_aggregate']),
        ];

        $q = Reservation::query();
        $this->branchScope->scopeReservations($q, $user, $ctx);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user, $ctx);

        return $q;
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyTourFilter(Builder $q, int $tourId): Builder
    {
        if ($tourId <= 0) {
            return $q;
        }

        $ids = ReservationLinkResolver::physicalTourIdsForVoyage($tourId);
        if (count($ids) === 1) {
            $q->where('tour_id', $ids[0]);
        } else {
            $q->whereIn('tour_id', $ids);
        }

        return $q;
    }

    /**
     * Filtre sur {@see TravelDate} id (wp.aj_travel_dates). Null ou 0 = toutes les dates du voyage.
     *
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyTravelDateFilter(Builder $q, ?int $travelDateId): Builder
    {
        if ($travelDateId !== null && $travelDateId > 0) {
            // Some WP installs have duplicate aj_travel_dates rows for the same travel_id + date.
            // To keep hub/workspace filters consistent, expand "travel_date_id" filter to the full set
            // of IDs that represent the same departure date.
            $td = TravelDate::query()->find($travelDateId);
            if ($td && $td->date && (int) ($td->travel_id ?? 0) > 0) {
                $ids = TravelDate::query()
                    ->where('travel_id', (int) $td->travel_id)
                    ->whereDate('date', $td->date->toDateString())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                if (count($ids) > 1) {
                    $q->whereIn('travel_date_id', $ids);
                } else {
                    $q->where('travel_date_id', $travelDateId);
                }
            } else {
                $q->where('travel_date_id', $travelDateId);
            }
        }

        return $q;
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyClientSearch(Builder $q, ?string $search): Builder
    {
        $s = $search !== null ? trim($search) : '';
        if ($s === '') {
            return $q;
        }
        $like = '%'.addcslashes($s, '%_\\').'%';
        $q->where(function ($sub) use ($like) {
            $sub->where('client_first_name', 'like', $like)
                ->orWhere('client_last_name', 'like', $like)
                ->orWhere('client_email', 'like', $like)
                ->orWhere('client_phone', 'like', $like);
        });

        return $q;
    }

    /**
     * Filtre "canal" (ex: réservations créées par les clients portail).
     *
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyChannelFilter(Builder $q, ?string $channel): Builder
    {
        $c = $channel !== null ? trim($channel) : '';
        if ($c === '') {
            return $q;
        }

        if ($c === 'client') {
            // Include both WP bridge bookings and future Laravel client-portal created bookings.
            $q->where(function (Builder $sub) {
                if ($this->reservationsHasChannelColumn()) {
                    $sub->where('channel', 'client')
                        ->orWhereIn('catalog_source_code', ['wp_front_v1', 'front_kiosk']);
                } else {
                    $sub->whereIn('catalog_source_code', ['wp_front_v1', 'front_kiosk']);
                }

                $sub
                    // Some legacy/front flows may not fill catalog_source_code; rely on linked client portal account.
                    ->orWhereHas('client', fn (Builder $cq) => $cq->whereNotNull('user_id'))
                    ->orWhereHas('creator', fn (Builder $u) => $u->where('user_type', 'client'))
                    ->orWhereHas('createdBy', fn (Builder $u) => $u->where('user_type', 'client'));
            });
        }

        return $q;
    }

    private function reservationsHasChannelColumn(): bool
    {
        return $this->reservationsHasChannelColumn
            ??= Schema::connection('mysql')->hasColumn('reservations', 'channel');
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyStatusFilter(Builder $q, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $q;
        }

        $legacy = [
            'EN_COURS' => Reservation::STATUS_PENDING,
            'VALIDEE' => Reservation::STATUS_CONFIRMED,
            'ANNULEE' => Reservation::STATUS_CANCELLED,
        ];
        $normalized = $legacy[$status] ?? $status;

        $allowed = [
            Reservation::STATUS_DRAFT,
            Reservation::STATUS_PENDING,
            Reservation::STATUS_OPTION,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PARTIALLY_PAID,
            Reservation::STATUS_PAID,
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_EXPIRED,
            Reservation::STATUS_REFUNDED,
        ];

        if (in_array($normalized, $allowed, true)) {
            $q->where('status', $normalized);
        }

        return $q;
    }

    /**
     * Compteurs sur l’ensemble filtré (même base que le tableau paginé).
     *
     * @param  Builder<\App\Models\Reservation>  $q
     * @return array{total: int, en_cours: int, validee: int, annulee: int}
     */
    public function aggregateStatusCounts(Builder $q): array
    {
        $rows = (clone $q)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'total' => (int) $rows->sum(),
            'en_cours' => (int) ($rows[Reservation::STATUS_EN_COURS] ?? 0),
            'validee' => (int) ($rows[Reservation::STATUS_VALIDEE] ?? 0),
            'annulee' => (int) ($rows[Reservation::STATUS_ANNULEE] ?? 0),
        ];
    }
}
