<?php

namespace App\Services\Finance;

use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\FinancialDocument;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\StructuralExpense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Moteur de calcul du module « Finance & Controle ».
 *
 * Regles de gestion (volontairement separees, jamais confondues) :
 *   CA vendu          = somme des total_amount des reservations valides du depart
 *   Encaisse          = somme des reservation_payments de ces reservations
 *   Reste clients     = CA vendu - encaisse
 *   Charges prevues   = somme des planned_amount (fallback amount) hors charges annulees
 *   Charges reelles   = somme des amount hors charges annulees
 *   Charges payees    = somme des paid_amount hors charges annulees
 *   Reste fournisseur = charges reelles - charges payees
 *   Marge prev.       = CA vendu - charges prevues
 *   Marge reelle      = CA vendu - charges reelles
 *   Taux de marge     = marge reelle / CA vendu * 100
 *
 * Le cash encaisse n'est JAMAIS assimile a un benefice : encaissement et marge sont
 * calcules par deux chaines distinctes et affiches separement.
 *
 * Toutes les agregations passent par des requetes groupees : aucune boucle par depart,
 * donc pas de N+1 sur les listes.
 */
class FinanceControlService
{
    /**
     * Definition des reservations « valides » (= vendues, non annulees).
     *
     * Strictement alignee sur App\Services\DepartureFinanceService::confirmedReservationsQuery
     * afin que le nouveau module et la page « Finances departs » historique affichent
     * toujours les memes montants.
     */
    public function validReservationsQuery(): Builder
    {
        return Reservation::query()
            ->where(function (Builder $query): void {
                $query->whereIn('status', [
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_PARTIALLY_PAID,
                    Reservation::STATUS_PAID,
                    Reservation::STATUS_VALIDEE,
                ])->orWhereIn('dossier_status', [
                    Reservation::DOSSIER_CONFIRMED,
                    Reservation::DOSSIER_COMPLETED,
                ]);
            })
            ->whereNotIn('status', [
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_EXPIRED,
                Reservation::STATUS_REFUNDED,
                Reservation::STATUS_ANNULEE,
            ])
            ->where(function (Builder $query): void {
                $query->whereNull('dossier_status')
                    ->orWhere('dossier_status', '')
                    ->orWhereNotIn('dossier_status', [Reservation::DOSSIER_CANCELLED, 'cancelled']);
            });
    }

    /**
     * Agregats de ventes par depart.
     *
     * @param  list<int>|null  $departureIds
     * @return Collection<int, object{departure_id:int, reservations_count:int, travelers_count:int, sold_amount:float}>
     */
    public function salesByDeparture(?array $departureIds = null, array $filters = []): Collection
    {
        $travelers = 'COALESCE(NULLIF(passengers_count, 0), COALESCE(adults_count, 0) + COALESCE(children_count, 0) + COALESCE(infants_count, 0))';

        return $this->validReservationsQuery()
            ->whereNotNull('departure_id')
            ->when($departureIds !== null, fn (Builder $q) => $q->whereIn('departure_id', $departureIds))
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $branchId) => $q->where('branch_id', $branchId))
            ->selectRaw('departure_id')
            ->selectRaw('COUNT(*) as reservations_count')
            ->selectRaw("SUM({$travelers}) as travelers_count")
            ->selectRaw('SUM(COALESCE(total_amount, 0)) as sold_amount')
            ->groupBy('departure_id')
            ->get()
            ->keyBy('departure_id');
    }

    /**
     * Encaissements clients agreges par depart.
     *
     * @param  list<int>|null  $departureIds
     * @return Collection<int, object{departure_id:int, collected_amount:float}>
     */
    public function collectionsByDeparture(?array $departureIds = null, array $filters = []): Collection
    {
        return ReservationPayment::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->whereNotNull('reservations.departure_id')
            ->whereIn('reservations.id', $this->validReservationsQuery()->select('reservations.id'))
            ->when($departureIds !== null, fn ($q) => $q->whereIn('reservations.departure_id', $departureIds))
            ->when($filters['branch_id'] ?? null, fn ($q, $branchId) => $q->where('reservations.branch_id', $branchId))
            ->selectRaw('reservations.departure_id as departure_id')
            ->selectRaw('SUM(reservation_payments.amount) as collected_amount')
            ->groupBy('reservations.departure_id')
            ->get()
            ->keyBy('departure_id');
    }

    /**
     * Charges voyage agregees par depart (prevu / reel / paye).
     *
     * @param  list<int>|null  $departureIds
     */
    public function chargesByDeparture(?array $departureIds = null): Collection
    {
        return DepartureCharge::query()
            ->accountable()
            ->when($departureIds !== null, fn (Builder $q) => $q->whereIn('departure_id', $departureIds))
            ->selectRaw('departure_id')
            ->selectRaw('SUM(COALESCE(planned_amount, amount)) as planned_amount')
            ->selectRaw('SUM(amount) as real_amount')
            ->selectRaw('SUM(COALESCE(paid_amount, 0)) as paid_amount')
            ->selectRaw('COUNT(*) as charges_count')
            ->groupBy('departure_id')
            ->get()
            ->keyBy('departure_id');
    }

    /**
     * Construit les lignes « projets de voyage » pour une liste de departs deja filtree.
     *
     * @param  Collection<int, Departure>  $departures
     * @return Collection<int, array<string, mixed>>
     */
    public function buildProjectRows(Collection $departures, array $filters = []): Collection
    {
        $ids = $departures->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return collect();
        }

        $sales = $this->salesByDeparture($ids, $filters);
        $collections = $this->collectionsByDeparture($ids, $filters);
        $charges = $this->chargesByDeparture($ids);

        return $departures->map(function (Departure $departure) use ($sales, $collections, $charges): array {
            $sale = $sales->get($departure->id);
            $collection = $collections->get($departure->id);
            $charge = $charges->get($departure->id);

            return $this->composeProjectRow(
                $departure,
                (int) ($sale->reservations_count ?? 0),
                (int) ($sale->travelers_count ?? 0),
                (float) ($sale->sold_amount ?? 0),
                (float) ($collection->collected_amount ?? 0),
                (float) ($charge->planned_amount ?? 0),
                (float) ($charge->real_amount ?? 0),
                (float) ($charge->paid_amount ?? 0),
                (int) ($charge->charges_count ?? 0),
            );
        })->values();
    }

    /**
     * Fiche financiere complete d'un projet (un depart).
     *
     * @return array<string, mixed>
     */
    public function projectSummary(Departure $departure): array
    {
        return $this->buildProjectRows(collect([$departure]))->first()
            ?? $this->composeProjectRow($departure, 0, 0, 0, 0, 0, 0, 0, 0);
    }

    /**
     * Assemble une ligne projet et derive tous les indicateurs de rentabilite.
     *
     * @return array<string, mixed>
     */
    private function composeProjectRow(
        Departure $departure,
        int $reservationsCount,
        int $travelersCount,
        float $soldAmount,
        float $collectedAmount,
        float $plannedCharges,
        float $realCharges,
        float $paidCharges,
        int $chargesCount
    ): array {
        $soldAmount = round($soldAmount, 2);
        $collectedAmount = round($collectedAmount, 2);
        $realCharges = round($realCharges, 2);
        $paidCharges = round($paidCharges, 2);

        $realMargin = round($soldAmount - $realCharges, 2);

        // Un depart sans aucune vente ni charge n'est pas « deficitaire » : il est vide.
        // La distinction evite de compter comme pertes des projets simplement non renseignes.
        $isEmpty = $reservationsCount === 0
            && $chargesCount === 0
            && $soldAmount === 0.0
            && $realCharges === 0.0;

        return [
            'departure' => $departure,
            'reservations_count' => $reservationsCount,
            'travelers_count' => $travelersCount,
            'charges_count' => $chargesCount,
            'sold_amount' => $soldAmount,
            'collected_amount' => $collectedAmount,
            // Reste client : peut etre negatif en cas de trop-percu, on ne le masque pas.
            'client_remaining' => round($soldAmount - $collectedAmount, 2),
            'planned_charges' => round($plannedCharges, 2),
            'real_charges' => $realCharges,
            'paid_charges' => $paidCharges,
            'supplier_remaining' => round($realCharges - $paidCharges, 2),
            'planned_margin' => round($soldAmount - $plannedCharges, 2),
            'real_margin' => $realMargin,
            'margin_rate' => $soldAmount > 0 ? round($realMargin / $soldAmount * 100, 2) : 0.0,
            'is_profitable' => $realMargin > 0,
            'is_empty' => $isEmpty,
            // Etat de lecture du projet : « empty » signale une donnee absente, pas une perte.
            'state' => match (true) {
                $isEmpty => 'empty',
                $realMargin > 0 => 'profitable',
                $realMargin < 0 => 'deficit',
                default => 'neutral',
            },
        ];
    }

    /**
     * Totaux d'un ensemble de lignes projet (bandeau KPI).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float|int>
     */
    public function totalsFromRows(Collection $rows): array
    {
        $sold = round((float) $rows->sum('sold_amount'), 2);
        $realCharges = round((float) $rows->sum('real_charges'), 2);
        $margin = round($sold - $realCharges, 2);

        return [
            'sold_amount' => $sold,
            'collected_amount' => round((float) $rows->sum('collected_amount'), 2),
            'client_remaining' => round((float) $rows->sum('client_remaining'), 2),
            'planned_charges' => round((float) $rows->sum('planned_charges'), 2),
            'real_charges' => $realCharges,
            'paid_charges' => round((float) $rows->sum('paid_charges'), 2),
            'supplier_remaining' => round((float) $rows->sum('supplier_remaining'), 2),
            'real_margin' => $margin,
            'margin_rate' => $sold > 0 ? round($margin / $sold * 100, 2) : 0.0,
            'profitable_count' => $rows->where('is_profitable', true)->count(),
            // Deficitaire = marge negative averee. Un projet sans aucune donnee est compte a part,
            // sinon les departs non renseignes gonflent artificiellement le nombre de pertes.
            'deficit_count' => $rows->where('state', 'deficit')->count(),
            'empty_count' => $rows->where('is_empty', true)->count(),
            'projects_count' => $rows->count(),
            'collection_rate' => $sold > 0 ? round((float) $rows->sum('collected_amount') / $sold * 100, 1) : 0.0,
        ];
    }

    /**
     * Total des charges de structure sur une periode (hors charges annulees).
     *
     * @return array{amount: float, paid: float, remaining: float}
     */
    public function structuralTotals(?string $from, ?string $to, ?int $branchId = null): array
    {
        $row = StructuralExpense::query()
            ->accountable()
            ->forPeriod($from, $to)
            ->when($branchId, fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->selectRaw('SUM(amount) as amount, SUM(COALESCE(paid_amount, 0)) as paid')
            ->first();

        $amount = round((float) ($row->amount ?? 0), 2);
        $paid = round((float) ($row->paid ?? 0), 2);

        return [
            'amount' => $amount,
            'paid' => $paid,
            'remaining' => round($amount - $paid, 2),
        ];
    }

    /**
     * Departs concernes par une periode, filtres communs a toutes les pages du module.
     */
    public function departuresQuery(array $filters): Builder
    {
        return Departure::query()
            ->when($filters['voyage_id'] ?? null, fn (Builder $q, $id) => $q->where('voyage_id', $id))
            ->when($filters['departure_id'] ?? null, fn (Builder $q, $id) => $q->where('id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_date', '<=', $date))
            ->when($filters['branch_id'] ?? null, function (Builder $q, $branchId) {
                // Un depart appartient a une agence des lors qu'elle y a vendu au moins un dossier.
                $q->whereIn('id', $this->validReservationsQuery()
                    ->where('branch_id', $branchId)
                    ->whereNotNull('departure_id')
                    ->select('departure_id'));
            })
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->whereHas('voyage', fn (Builder $v) => $v->where('name', 'like', '%'.$search.'%'));
            });
    }

    /**
     * Mouvements de tresorerie (entrees et sorties reelles) sur une periode.
     *
     * Entrees  : encaissements clients (reservation_payments)
     * Sorties  : paiements fournisseurs (departure_charges.paid_amount) + charges de structure payees
     *
     * @return array<string, mixed>
     */
    public function treasury(array $filters): array
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $method = $filters['payment_method'] ?? null;

        $inflows = ReservationPayment::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->whereIn('reservations.id', $this->validReservationsQuery()->select('reservations.id'))
            ->when($from, fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->when($branchId, fn ($q, $id) => $q->where('reservations.branch_id', $id))
            ->when($method, fn ($q, $m) => $q->where('reservation_payments.payment_method', $m))
            ->selectRaw('SUM(reservation_payments.amount) as total, COUNT(*) as movements')
            ->first();

        $supplierOutflows = DepartureCharge::query()
            ->accountable()
            ->where('paid_amount', '>', 0)
            ->when($from, fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(paid_at, charge_date)'), '>=', $d))
            ->when($to, fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(paid_at, charge_date)'), '<=', $d))
            ->when($branchId, fn (Builder $q, $id) => $q->where('branch_id', $id))
            ->when($method, fn (Builder $q, $m) => $q->where('payment_method', $m))
            ->selectRaw('SUM(paid_amount) as total, COUNT(*) as movements')
            ->first();

        $structuralOutflows = StructuralExpense::query()
            ->accountable()
            ->where('paid_amount', '>', 0)
            ->when($from, fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(paid_at, expense_date)'), '>=', $d))
            ->when($to, fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(paid_at, expense_date)'), '<=', $d))
            ->when($branchId, fn (Builder $q, $id) => $q->where('branch_id', $id))
            ->when($method, fn (Builder $q, $m) => $q->where('payment_method', $m))
            ->selectRaw('SUM(paid_amount) as total, COUNT(*) as movements')
            ->first();

        $in = round((float) ($inflows->total ?? 0), 2);
        $supplierOut = round((float) ($supplierOutflows->total ?? 0), 2);
        $structuralOut = round((float) ($structuralOutflows->total ?? 0), 2);

        return [
            'inflows' => $in,
            'inflows_count' => (int) ($inflows->movements ?? 0),
            'supplier_outflows' => $supplierOut,
            'supplier_outflows_count' => (int) ($supplierOutflows->movements ?? 0),
            'structural_outflows' => $structuralOut,
            'structural_outflows_count' => (int) ($structuralOutflows->movements ?? 0),
            'outflows' => round($supplierOut + $structuralOut, 2),
            // Solde theorique : flux du module uniquement, ce n'est pas un solde bancaire.
            'balance' => round($in - $supplierOut - $structuralOut, 2),
        ];
    }

    /**
     * Nombre d'operations financieres sans aucune piece justificative.
     *
     * Une operation est consideree justifiee si elle porte deja un fichier natif
     * (proof_file / attachment) ou si un FinancialDocument non rejete lui est rattache.
     */
    public function missingDocumentsCount(float $minAmount = 0): int
    {
        return $this->paymentsMissingDocumentsQuery($minAmount)->count()
            + $this->chargesMissingDocumentsQuery($minAmount)->count()
            + $this->structuralMissingDocumentsQuery($minAmount)->count();
    }

    public function paymentsMissingDocumentsQuery(float $minAmount = 0): Builder
    {
        return ReservationPayment::query()
            ->where('amount', '>=', $minAmount)
            ->where(function (Builder $q) {
                $q->whereNull('proof_file')->orWhere('proof_file', '');
            })
            ->whereDoesntHave('financialDocuments', fn (Builder $q) => $q->where('status', '!=', FinancialDocument::STATUS_REJECTED));
    }

    public function chargesMissingDocumentsQuery(float $minAmount = 0): Builder
    {
        return DepartureCharge::query()
            ->accountable()
            ->where('amount', '>=', $minAmount)
            ->where(function (Builder $q) {
                $q->whereNull('attachment')->orWhere('attachment', '');
            })
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('status', '!=', FinancialDocument::STATUS_REJECTED));
    }

    public function structuralMissingDocumentsQuery(float $minAmount = 0): Builder
    {
        return StructuralExpense::query()
            ->accountable()
            ->where('amount', '>=', $minAmount)
            ->whereDoesntHave('documents', fn (Builder $q) => $q->where('status', '!=', FinancialDocument::STATUS_REJECTED));
    }
}
