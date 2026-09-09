<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\DepartureCharge;
use App\Models\ReservationPayment;
use App\Models\StructuralExpense;
use App\Services\DepartureFinanceService;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tresorerie : synthese des flux reels.
 *
 * Aucun systeme de caisse n'est cree : les entrees proviennent de `reservation_payments`
 * (deja saisis par les agences) et les sorties des montants payes sur les charges.
 */
class TreasuryController extends FinanceControlController
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::TREASURY_VIEW);

        $filters = $this->commonFilters($request);
        $filters['payment_method'] = $this->text($request, 'payment_method');
        $granularity = in_array($request->query('granularity'), ['jour', 'semaine', 'mois'], true)
            ? (string) $request->query('granularity')
            : 'mois';

        return view('admin.finance.control.treasury', [
            'filters' => $filters,
            'granularity' => $granularity,
            'summary' => $this->finance->treasury($filters),
            'movements' => $this->movements($filters),
            'byPeriod' => $this->byPeriod($filters, $granularity),
            'branches' => $this->branchOptions(),
            'paymentMethods' => DepartureFinanceService::ENTRY_PAYMENT_METHODS,
        ]);
    }

    /**
     * Derniers mouvements, entrees et sorties confondues, tries par date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function movements(array $filters): Collection
    {
        $limit = 150;

        $inflows = ReservationPayment::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->leftJoin('branches', 'branches.id', '=', 'reservations.branch_id')
            ->whereIn('reservations.id', $this->finance->validReservationsQuery()->select('reservations.id'))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('reservations.branch_id', $id))
            ->when($filters['payment_method'], fn ($q, $m) => $q->where('reservation_payments.payment_method', $m))
            ->orderByDesc('reservation_payments.payment_date')
            ->limit($limit)
            ->get([
                'reservation_payments.payment_date as date',
                'reservation_payments.amount as amount',
                'reservation_payments.payment_method as method',
                'reservations.dossier_number as reference',
                'branches.name as agency',
            ])
            ->map(fn ($row) => [
                'direction' => 'entree',
                'kind' => 'Encaissement client',
                'date' => $row->date,
                'label' => $row->reference ?: 'Dossier client',
                'amount' => round((float) $row->amount, 2),
                'method' => $row->method,
                'agency' => $row->agency,
            ]);

        $supplierOutflows = DepartureCharge::query()
            ->accountable()
            ->where('departure_charges.paid_amount', '>', 0)
            ->leftJoin('branches', 'branches.id', '=', 'departure_charges.branch_id')
            ->when($filters['date_from'], fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(departure_charges.paid_at, departure_charges.charge_date)'), '>=', $d))
            ->when($filters['date_to'], fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(departure_charges.paid_at, departure_charges.charge_date)'), '<=', $d))
            ->when($filters['branch_id'], fn (Builder $q, $id) => $q->where('departure_charges.branch_id', $id))
            ->when($filters['payment_method'], fn (Builder $q, $m) => $q->where('departure_charges.payment_method', $m))
            ->orderByDesc('departure_charges.paid_at')
            ->limit($limit)
            ->get([
                'departure_charges.paid_at',
                'departure_charges.charge_date',
                'departure_charges.paid_amount as amount',
                'departure_charges.payment_method as method',
                'departure_charges.title as title',
                'branches.name as agency',
            ])
            ->map(fn ($row) => [
                'direction' => 'sortie',
                'kind' => 'Paiement fournisseur',
                'date' => $row->paid_at ?: $row->charge_date,
                'label' => $row->title,
                'amount' => round((float) $row->amount, 2),
                'method' => $row->method,
                'agency' => $row->agency,
            ]);

        $structuralOutflows = StructuralExpense::query()
            ->accountable()
            ->where('structural_expenses.paid_amount', '>', 0)
            ->leftJoin('branches', 'branches.id', '=', 'structural_expenses.branch_id')
            ->when($filters['date_from'], fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(structural_expenses.paid_at, structural_expenses.expense_date)'), '>=', $d))
            ->when($filters['date_to'], fn (Builder $q, $d) => $q->whereDate(DB::raw('COALESCE(structural_expenses.paid_at, structural_expenses.expense_date)'), '<=', $d))
            ->when($filters['branch_id'], fn (Builder $q, $id) => $q->where('structural_expenses.branch_id', $id))
            ->when($filters['payment_method'], fn (Builder $q, $m) => $q->where('structural_expenses.payment_method', $m))
            ->orderByDesc('structural_expenses.paid_at')
            ->limit($limit)
            ->get([
                'structural_expenses.paid_at',
                'structural_expenses.expense_date',
                'structural_expenses.paid_amount as amount',
                'structural_expenses.payment_method as method',
                'structural_expenses.label as title',
                'branches.name as agency',
            ])
            ->map(fn ($row) => [
                'direction' => 'sortie',
                'kind' => 'Charge de structure',
                'date' => $row->paid_at ?: $row->expense_date,
                'label' => $row->title,
                'amount' => round((float) $row->amount, 2),
                'method' => $row->method,
                'agency' => $row->agency,
            ]);

        return $inflows->concat($supplierOutflows)->concat($structuralOutflows)
            ->sortByDesc(fn (array $row) => (string) $row['date'])
            ->take($limit)
            ->values();
    }

    /**
     * Entrees / sorties agregees par periode selon la granularite demandee.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function byPeriod(array $filters, string $granularity): Collection
    {
        $movements = $this->movements($filters);

        return $movements
            ->groupBy(fn (array $row) => $this->periodKey((string) $row['date'], $granularity))
            ->map(function (Collection $group, string $period): array {
                $in = round((float) $group->where('direction', 'entree')->sum('amount'), 2);
                $out = round((float) $group->where('direction', 'sortie')->sum('amount'), 2);

                return [
                    'period' => $period,
                    'inflows' => $in,
                    'outflows' => $out,
                    'balance' => round($in - $out, 2),
                ];
            })
            ->sortKeysDesc()
            ->values();
    }

    private function periodKey(string $date, string $granularity): string
    {
        $value = substr($date, 0, 10);

        if ($value === '') {
            return 'Non date';
        }

        return match ($granularity) {
            'jour' => $value,
            'semaine' => date('o-\SW', strtotime($value)),
            default => substr($value, 0, 7),
        };
    }
}
