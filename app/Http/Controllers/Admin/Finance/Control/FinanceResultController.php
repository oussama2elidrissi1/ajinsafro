<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\StructuralExpense;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Resultats & marges.
 *
 * Separation stricte demandee :
 *   Resultat voyages   = somme des marges reelles des projets
 *   Charges structure  = loyer, salaires, energie...
 *   Resultat de gestion = resultat voyages - charges structure
 *
 * Ce resultat de gestion n'est pas un resultat comptable fiscal : la vue l'indique.
 */
class FinanceResultController extends FinanceControlController
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::REPORTING_VIEW);

        $filters = $this->commonFilters($request);

        $departures = $this->finance->departuresQuery($filters)->with('voyage:id,name')->get();
        $rows = $this->finance->buildProjectRows($departures, $filters);
        $totals = $this->finance->totalsFromRows($rows);
        $structural = $this->finance->structuralTotals($filters['date_from'], $filters['date_to'], $filters['branch_id']);

        return view('admin.finance.control.results', [
            'filters' => $filters,
            'rows' => $rows->sortByDesc('real_margin')->values(),
            'totals' => $totals,
            'structural' => $structural,
            'structuralByCategory' => $this->structuralByCategory($filters),
            'managementResult' => round($totals['real_margin'] - $structural['amount'], 2),
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, amount: float}>
     */
    private function structuralByCategory(array $filters): Collection
    {
        return StructuralExpense::query()
            ->accountable()
            ->forPeriod($filters['date_from'], $filters['date_to'])
            ->when($filters['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->selectRaw('category, SUM(amount) as amount')
            ->groupBy('category')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'label' => StructuralExpense::CATEGORY_LABELS[$row->category] ?? (string) $row->category,
                'amount' => round((float) $row->amount, 2),
            ]);
    }
}
