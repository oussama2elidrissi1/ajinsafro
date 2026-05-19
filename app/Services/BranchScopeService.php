<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;

class BranchScopeService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_SIEGE_ADMIN = 'siege_admin';

    public const ROLE_BRANCH_ADMIN = 'branch_admin';

    public const ROLE_CHEF_COMMERCIAL = 'chef_commercial';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_COMMERCIAL = 'commercial';

    public const ROLE_AGENT = 'agent';

    public const ROLE_COMMERCIAL_RESERVATIONS_ONLY = 'commercial_reservations_only';

    /**
     * Comptes avec accès global (toutes les agences) : super_admin, siege_admin, ou is_admin (legacy).
     */
    public function canSeeAllBranches(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_ADMIN)
            || $user->hasRole(self::ROLE_SIEGE_ADMIN)
            || $user->hasRole('Super Admin')
            || $user->hasRole('Admin Siège')
            || $user->is_admin;
    }

    public function isCommercialReservationsOnly(User $user): bool
    {
        return $user->hasRole(self::ROLE_COMMERCIAL_RESERVATIONS_ONLY);
    }

    /**
     * IDs des branches que l'utilisateur peut voir.
     *
     * @return int[]|null null = toutes les branches
     */
    public function visibleBranchIds(User $user): ?array
    {
        if ($this->canSeeAllBranches($user)) {
            return null;
        }
        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }
        // Aligné sur l’attribution des réservations (commercial sans agence propre → agence du manager).
        $managerBranchId = $user->manager?->branch_id;
        if ($managerBranchId) {
            return [(int) $managerBranchId];
        }

        return [];
    }

    /**
     * Branch + chef commercial pour une nouvelle réservation (même logique que la liste admin).
     *
     * @return array{branch_id: int|null, sales_manager_id: int|null}
     */
    public function defaultReservationOwnership(User $user, ?int $clientExternalId = null): array
    {
        $branchId = $user->branch_id ?? $user->manager?->branch_id;
        if (($branchId === null || (int) $branchId <= 0) && $clientExternalId !== null && $clientExternalId > 0) {
            $client = Client::query()->find($clientExternalId);
            if ($client && $client->branch_id) {
                $branchId = (int) $client->branch_id;
            }
        }
        $branchId = $branchId && (int) $branchId > 0 ? (int) $branchId : null;

        $salesManagerId = null;
        if ($branchId !== null) {
            $salesManagerId = Branch::query()->whereKey($branchId)->value('manager_user_id');
            $salesManagerId = $salesManagerId ? (int) $salesManagerId : null;
        }
        if ($salesManagerId === null) {
            $fallback = $user->branch?->manager_user_id;
            $salesManagerId = $fallback ? (int) $fallback : null;
        }

        return [
            'branch_id' => $branchId,
            'sales_manager_id' => $salesManagerId,
        ];
    }

    /**
     * True si l'utilisateur est un compte agence (accès limité à une agence).
     */
    public function isBranchScoped(User $user): bool
    {
        return $user->hasRole(self::ROLE_BRANCH_ADMIN)
            || $user->hasRole(self::ROLE_CHEF_COMMERCIAL)
            || $user->hasRole(self::ROLE_MANAGER)
            || $user->hasRole(self::ROLE_COMMERCIAL)
            || $user->hasRole(self::ROLE_AGENT);
    }

    /**
     * True si compte siège global (Tanger accès toutes agences).
     */
    public function isSiegeAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_SIEGE_ADMIN) || $user->hasRole('Admin Siège');
    }

    /**
     * True si compte principal d'une agence (dont agence Tanger).
     */
    public function isBranchAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_BRANCH_ADMIN);
    }

    /**
     * Rôles métier : vision opérationnelle partagée par voyage / départ (hors détail sensible réservation).
     */
    public function isSharedOperationalReservationRole(User $user): bool
    {
        return $user->isBranchAdmin()
            || $user->isChefCommercial()
            || $user->isCommercial()
            || $user->isAgent()
            || $user->isManager()
            || $user->isPartner();
    }

    /**
     * Ne pas restreindre par agence : stats catalogue, ou liste filtrée par voyage / date / départ.
     *
     * @param  array{tour_id?: int|null, travel_date_id?: int|null, departure_id?: int|null, channel?: string|null, catalog_source_code?: string|null, shared_operational_aggregate?: bool}  $context
     */
    public function shouldBypassBranchScopeForSharedOperationalView(User $user, array $context): bool
    {
        if ($this->canSeeAllBranches($user)) {
            return false;
        }
        if (! $this->isSharedOperationalReservationRole($user)) {
            return false;
        }
        if (! empty($context['shared_operational_aggregate'])) {
            return true;
        }

        $tourId = (int) ($context['tour_id'] ?? 0);
        $travelDateId = (int) ($context['travel_date_id'] ?? 0);
        $departureId = (int) ($context['departure_id'] ?? 0);
        $channel = trim((string) ($context['channel'] ?? ''));
        $catalogSourceCode = trim((string) ($context['catalog_source_code'] ?? ''));

        return $tourId > 0
            || $travelDateId > 0
            || $departureId > 0
            || $channel === 'client'
            || in_array($catalogSourceCode, ['wp_front_v1', 'front_kiosk'], true);
    }

    /**
     * Lecture « état du départ / voyage » : dossier d’une autre agence visible sans droits d’édition complets.
     */
    public function userCanViewReservationSharedOperational(User $user, Reservation $reservation): bool
    {
        if ($this->canSeeAllBranches($user)) {
            return true;
        }
        if (! $this->isSharedOperationalReservationRole($user)) {
            return false;
        }

        return (int) ($reservation->tour_id ?? 0) > 0;
    }

    /**
     * Scope les réservations selon le rôle / agence de l'utilisateur.
     *
     * @param  array{tour_id?: int|null, travel_date_id?: int|null, departure_id?: int|null, channel?: string|null, catalog_source_code?: string|null, shared_operational_aggregate?: bool}  $context
     */
    public function scopeReservations(Builder $query, User $user, array $context = []): Builder
    {
        if ($this->isCommercialReservationsOnly($user)) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->where('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        if ($this->shouldBypassBranchScopeForSharedOperationalView($user, $context)) {
            return $query;
        }

        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds === null) {
            return $query;
        }
        if (empty($branchIds)) {
            // Compte agence sans branch_id : sinon liste vide alors que des réservations viennent d’être créées
            // (branch_id null + agent_id / created_by). Le portail restreint déjà via
            // {@see constrainReservationQueryForPortalUser}.
            if (AgentPortalLayout::shouldUse($user)) {
                return $query;
            }

            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Accès fiche / panel / édition : aligné sur la liste (scope agence + propriété portail).
     */
    public function userCanAccessReservation(User $user, Reservation $reservation): bool
    {
        if ($this->canSeeAllBranches($user)) {
            return true;
        }

        $query = Reservation::query()->whereKey($reservation->getKey());
        $this->scopeReservations($query, $user, []);
        $this->constrainReservationQueryForPortalUser($query, $user);

        return $query->exists();
    }

    /**
     * Scope les clients selon le rôle / agence de l'utilisateur.
     */
    public function scopeClients(Builder $query, User $user): Builder
    {
        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds === null) {
            return $query;
        }
        if (empty($branchIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope les utilisateurs selon le rôle / agence (Admin Siège voit tous, Chef Commercial voit son agence).
     */
    public function scopeUsers(Builder $query, User $user): Builder
    {
        if ($this->canSeeAllBranches($user)) {
            return $query;
        }
        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Branches disponibles pour un select (selon l'utilisateur).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Branch>
     */
    public function branchesForSelect(User $user)
    {
        if ($this->canSeeAllBranches($user)) {
            return Branch::active()->orderBy('type')->orderBy('name')->get();
        }
        if ($user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return collect();
    }

    /**
     * IDs utilisateurs pour le périmètre réservations/clients du portail agent :
     * agent → lui seul ; manager → lui + équipe directe (même agence si renseignée).
     *
     * @return list<int>
     */
    public function portalOwnershipUserIds(User $user): array
    {
        if ($user->isManager()) {
            return User::query()
                ->where('manager_id', $user->id)
                ->when($user->branch_id, fn (Builder $q) => $q->where('branch_id', $user->branch_id))
                ->pluck('id')
                ->push($user->id)
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return [$user->id];
    }

    /**
     * Membres directs de l'équipe (sans le manager).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function portalDirectReports(User $manager)
    {
        return User::query()
            ->where('manager_id', $manager->id)
            ->when($manager->branch_id, fn (Builder $q) => $q->where('branch_id', $manager->branch_id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Restreint une requête réservations pour le portail agent/commercial/manager
     * (aligné sur {@see AgentPortalLayout::shouldUse} et {@see portalOwnershipUserIds}).
     */
    public function constrainReservationQueryForPortalUser(Builder $query, User $user, array $context = []): void
    {
        if ($this->isCommercialReservationsOnly($user)) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('created_by', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });

            return;
        }

        if (! \App\Services\View\AgentPortalLayout::shouldUse($user)) {
            return;
        }

        // Agent / commercial / chef commercial rattachés à une agence : toutes les réservations du périmètre
        // {@see scopeReservations}. Les managers gardent le filtre équipe + co-visibilité prestations.
        if ($this->shouldSkipPortalOwnershipReservationScope($user)) {
            return;
        }

        $ids = $this->normalizePortalIds($this->portalOwnershipUserIds($user));
        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($this->shouldUseBranchWideFilteredReservationVisibility($user, $context)) {
            return;
        }

        $sharing = $this->portalReservationSharingContext($user, $ids);

        $query->where(function (Builder $q) use ($ids, $sharing) {
            $this->applyPortalOwnershipReservationScope($q, $ids);
            $this->applyPortalSharedReservationScope($q, $sharing);
        });
    }

    /**
     * @param  list<int>  $ids
     */
    private function applyPortalOwnershipReservationScope(Builder $query, array $ids): void
    {
        $query->whereIn('agent_id', $ids)
            ->orWhereIn('sales_manager_id', $ids)
            ->orWhereIn('created_by', $ids)
            ->orWhereIn('created_by_user_id', $ids);
    }

    /**
     * @param  array{tour_ids: list<int>, wp_tour_post_ids: list<int>, travel_date_ids: list<int>, voyage_flight_ids: list<int>, catalog_source_codes: list<string>, tour_hotel_ids: list<int>}  $sharing
     */
    private function applyPortalSharedReservationScope(Builder $query, array $sharing): void
    {
        if ($sharing['tour_ids'] !== []) {
            $query->orWhereIn('tour_id', $sharing['tour_ids']);
        }

        if ($sharing['wp_tour_post_ids'] !== []) {
            $query->orWhereIn('wp_tour_post_id', $sharing['wp_tour_post_ids']);
        }

        if ($sharing['travel_date_ids'] !== []) {
            $query->orWhereIn('travel_date_id', $sharing['travel_date_ids']);
        }

        if ($sharing['voyage_flight_ids'] !== []) {
            $query->orWhereIn('voyage_flight_id', $sharing['voyage_flight_ids']);
        }

        if ($sharing['catalog_source_codes'] !== []) {
            $query->orWhereIn('catalog_source_code', $sharing['catalog_source_codes']);
        }

        if ($sharing['tour_hotel_ids'] !== []) {
            $query->orWhereHas('reservationRooms', function (Builder $reservationRooms) use ($sharing) {
                $reservationRooms->whereIn('tour_hotel_id', $sharing['tour_hotel_ids']);
            });
        }
    }

    /**
     * @param  list<int>  $ownershipIds
     * @return array{tour_ids: list<int>, wp_tour_post_ids: list<int>, travel_date_ids: list<int>, voyage_flight_ids: list<int>, catalog_source_codes: list<string>, tour_hotel_ids: list<int>}
     */
    private function portalReservationSharingContext(User $user, array $ownershipIds): array
    {
        $sharing = [
            'tour_ids' => [],
            'wp_tour_post_ids' => [],
            'travel_date_ids' => [],
            'voyage_flight_ids' => [],
            'catalog_source_codes' => [],
            'tour_hotel_ids' => [],
        ];

        $seed = Reservation::query()
            ->select([
                'id',
                'tour_id',
                'wp_tour_post_id',
                'travel_date_id',
                'voyage_flight_id',
                'catalog_source_code',
            ])
            ->with(['reservationRooms:id,reservation_id,tour_hotel_id']);

        $this->scopeReservations($seed, $user, []);
        $seed->where(function (Builder $query) use ($ownershipIds) {
            $this->applyPortalOwnershipReservationScope($query, $ownershipIds);
        });

        $reservations = $seed->get();
        if ($reservations->isEmpty()) {
            return $sharing;
        }

        $tourIds = $reservations->pluck('tour_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $sharedTourIds = [];
        foreach ($tourIds as $tourId) {
            foreach (Voyage::allIdsSharingWpTour($tourId) as $physicalId) {
                $physicalId = (int) $physicalId;
                if ($physicalId > 0) {
                    $sharedTourIds[$physicalId] = $physicalId;
                }
            }
        }

        $sharedWpTourIds = $reservations->pluck('wp_tour_post_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($sharedWpTourIds !== []) {
            $voyageIds = Voyage::query()
                ->whereIn('wp_post_id', $sharedWpTourIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            foreach ($voyageIds as $voyageId) {
                foreach (Voyage::allIdsSharingWpTour($voyageId) as $physicalId) {
                    $physicalId = (int) $physicalId;
                    if ($physicalId > 0) {
                        $sharedTourIds[$physicalId] = $physicalId;
                    }
                }
            }
        }

        if ($sharedTourIds !== []) {
            $sharedWpTourIds = array_values(array_unique(array_merge(
                $sharedWpTourIds,
                Voyage::query()
                    ->whereIn('id', array_values($sharedTourIds))
                    ->whereNotNull('wp_post_id')
                    ->pluck('wp_post_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all()
            )));
        }

        $sharing['tour_ids'] = array_values($sharedTourIds);
        $sharing['wp_tour_post_ids'] = $sharedWpTourIds;
        $sharing['travel_date_ids'] = $reservations->pluck('travel_date_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $sharing['voyage_flight_ids'] = $reservations->pluck('voyage_flight_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $sharing['catalog_source_codes'] = $reservations->pluck('catalog_source_code')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
        $sharing['tour_hotel_ids'] = $reservations->flatMap(function (Reservation $reservation) {
            return $reservation->reservationRooms->pluck('tour_hotel_id');
        })
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $sharing;
    }

    private function shouldUseBranchWideFilteredReservationVisibility(User $user, array $context): bool
    {
        if (trim((string) ($context['channel'] ?? '')) === 'client'
            || trim((string) ($context['catalog_source_code'] ?? '')) !== '') {
            return true;
        }

        $branchIds = $this->visibleBranchIds($user);
        if ($branchIds === [] || $branchIds === null) {
            return false;
        }

        return (int) ($context['tour_id'] ?? 0) > 0
            || (int) ($context['travel_date_id'] ?? 0) > 0
            || (int) ($context['voyage_flight_id'] ?? 0) > 0
            || (int) ($context['tour_hotel_id'] ?? 0) > 0;
    }

    /**
     * Ne pas restreindre par agent_id / created_by / sales_manager_id pour les rôles opérationnels
     * d'agence (hors manager) : ils voient tout le portefeuille de leur (leurs) agence(s).
     */
    private function shouldSkipPortalOwnershipReservationScope(User $user): bool
    {
        // Managers should see the full branch portfolio (same expectation as branch admin),
        // otherwise web/client-created reservations (no explicit agent ownership) become invisible in hub lists.
        if ($user->isManager()) {
            $branchIds = $this->visibleBranchIds($user);
            return $branchIds !== null && $branchIds !== [];
        }

        // Responsable d’agence : tout le portefeuille de l’agence (même si le shell portail évolue).
        if ($user->isBranchAdmin()) {
            $branchIds = $this->visibleBranchIds($user);

            return $branchIds !== null && $branchIds !== [];
        }

        if (! ($user->isAgent() || $user->isCommercial() || $user->isChefCommercial())) {
            return false;
        }

        $branchIds = $this->visibleBranchIds($user);

        return $branchIds !== null && $branchIds !== [];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function normalizePortalIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
