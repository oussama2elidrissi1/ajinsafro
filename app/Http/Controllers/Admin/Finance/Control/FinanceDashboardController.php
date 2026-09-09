<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\DepartureCharge;
use App\Models\ReservationPayment;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Vue d'ensemble financiere du module.
 */
class FinanceDashboardController extends FinanceControlController
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::PROJECTS_VIEW);

        $filters = $this->commonFilters($request);

        $departures = $this->finance->departuresQuery($filters)->with('voyage:id,name')->get();
        $rows = $this->finance->buildProjectRows($departures, $filters);
        $totals = $this->finance->totalsFromRows($rows);
        $structural = $this->finance->structuralTotals($filters['date_from'], $filters['date_to'], $filters['branch_id']);

        return view('admin.finance.control.dashboard', [
            'filters' => $filters,
            'totals' => $totals,
            'structural' => $structural,
            // Indicateur de gestion : marge des voyages moins les charges de structure.
            'managementResult' => round($totals['real_margin'] - $structural['amount'], 2),
            'missingDocuments' => $this->finance->missingDocumentsCount(),
            'revenueByMonth' => $this->revenueByMonth($filters),
            'chargesByCategory' => $this->chargesByCategory($filters),
            'collectionsByBranch' => $this->collectionsByBranch($filters),
            'topMargins' => $rows->sortByDesc('real_margin')->take(8)->values(),
            'worstMargins' => $rows->sortBy('real_margin')->take(8)->values(),
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    /**
     * CA encaisse par mois : lu depuis les paiements reels, pas depuis les ventes.
     *
     * @return Collection<int, array{period: string, amount: float}>
     */
    private function revenueByMonth(array $filters): Collection
    {
        $driver = DB::connection()->getDriverName();
        $period = $driver === 'sqlite'
            ? "strftime('%Y-%m', reservation_payments.payment_date)"
            : "DATE_FORMAT(reservation_payments.payment_date, '%Y-%m')";

        return ReservationPayment::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->whereIn('reservations.id', $this->finance->validReservationsQuery()->select('reservations.id'))
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('reservations.branch_id', $id))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->selectRaw("{$period} as period, SUM(reservation_payments.amount) as amount")
            ->groupBy(DB::raw($period))
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => ['period' => (string) $row->period, 'amount' => round((float) $row->amount, 2)]);
    }

    /**
     * @return Collection<int, array{label: string, amount: float}>
     */
    private function chargesByCategory(array $filters): Collection
    {
        return DepartureCharge::query()
            ->accountable()
            ->leftJoin('charge_types', 'charge_types.id', '=', 'departure_charges.charge_type_id')
            ->when($filters['branch_id'], fn (Builder $q, $id) => $q->where('departure_charges.branch_id', $id))
            ->when($filters['date_from'], fn (Builder $q, $d) => $q->whereDate('departure_charges.charge_date', '>=', $d))
            ->when($filters['date_to'], fn (Builder $q, $d) => $q->whereDate('departure_charges.charge_date', '<=', $d))
            ->selectRaw("COALESCE(charge_types.name, 'Autre') as label, SUM(departure_charges.amount) as amount")
            ->groupBy('label')
            ->orderByDesc('amount')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'amount' => round((float) $row->amount, 2)]);
    }

    /**
     * @return Collection<int, array{label: string, amount: float}>
     */
    private function collectionsByBranch(array $filters): Collection
    {
        return ReservationPayment::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->leftJoin('branches', 'branches.id', '=', 'reservations.branch_id')
            ->whereIn('reservations.id', $this->finance->validReservationsQuery()->select('reservations.id'))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->selectRaw("COALESCE(branches.name, 'Non rattache') as label, SUM(reservation_payments.amount) as amount")
            ->groupBy('label')
            ->orderByDesc('amount')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'amount' => round((float) $row->amount, 2)]);
    }
}
