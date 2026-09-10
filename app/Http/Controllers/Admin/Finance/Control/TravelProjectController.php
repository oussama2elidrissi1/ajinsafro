<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\FinanceSupplier;
use App\Models\FinancialDocument;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Projets de voyage : un projet financier = un depart reel.
 */
class TravelProjectController extends FinanceControlController
{
    /** Tris proposes sur la liste des projets. */
    public const SORT_MARGIN = 'marge';

    public const SORT_DATE = 'depart';

    public const SORT_CLIENT_REMAINING = 'reste';

    public const SORTS = [self::SORT_MARGIN, self::SORT_DATE, self::SORT_CLIENT_REMAINING];

    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::PROJECTS_VIEW);

        $filters = $this->commonFilters($request);

        $departures = $this->finance->departuresQuery($filters)
            ->with('voyage:id,name')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $rows = $this->finance->buildProjectRows($departures, $filters);

        if ($filters['profitability'] !== null) {
            $wantProfitable = $filters['profitability'] === 'rentable';
            $rows = $rows->filter(fn (array $row) => $row['is_profitable'] === $wantProfitable)->values();
        }

        // Tri de lecture : la requete reste ordonnee par date, le tri s'applique aux lignes calculees
        // (marge et reste client ne sont pas des colonnes SQL).
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : self::SORT_MARGIN;
        $rows = match ($sort) {
            self::SORT_DATE => $rows->sortByDesc(fn (array $row) => $row['departure']->start_date?->timestamp ?? 0)->values(),
            self::SORT_CLIENT_REMAINING => $rows->sortByDesc('client_remaining')->values(),
            default => $rows->sortByDesc('real_margin')->values(),
        };

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        return view('admin.finance.control.travel-projects.index', [
            'rows' => new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'totals' => $this->finance->totalsFromRows($rows),
            'filters' => $filters,
            'sort' => $sort,
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
            'departureStatuses' => Departure::STATUSES,
        ]);
    }

    /**
     * Fiche financiere complete d'un projet : KPI + 6 onglets.
     */
    public function show(Request $request, Departure $departure): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::PROJECTS_VIEW);

        $departure->loadMissing('voyage:id,name');
        $summary = $this->finance->projectSummary($departure);
        // Chargees une seule fois : la vue fournisseurs est derivee en memoire.
        $charges = $this->charges($departure);

        return view('admin.finance.control.travel-projects.show', [
            'departure' => $departure,
            'summary' => $summary,
            'reservations' => $this->reservationLines($departure),
            'charges' => $charges,
            'suppliers' => $this->supplierBreakdown($charges),
            'documents' => $this->documents($departure),
            'chargeTypes' => ChargeType::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'supplierOptions' => FinanceSupplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'branches' => $this->branchOptions(),
        ]);
    }

    /**
     * Encaissements clients du projet : lecture seule des reservations et paiements existants.
     *
     * Aucune ressaisie n'est demandee, aucune reservation n'est modifiee ici.
     *
     * @return Collection<int, \App\Models\Reservation>
     */
    private function reservationLines(Departure $departure): Collection
    {
        return $this->finance->validReservationsQuery()
            ->where('departure_id', $departure->id)
            ->with([
                'payments' => fn ($query) => $query->orderBy('payment_date')->orderBy('id'),
                'payments.creator:id,name',
                'payments.financialDocuments:id,documentable_type,documentable_id,status,file_path',
                'branch:id,name',
                'agent:id,name',
                'createdBy:id,name',
            ])
            ->orderByDesc('id')
            ->get();
    }

    /** @return Collection<int, DepartureCharge> */
    private function charges(Departure $departure): Collection
    {
        return DepartureCharge::query()
            ->where('departure_id', $departure->id)
            ->with(['type:id,name', 'supplier:id,name', 'branch:id,name', 'creator:id,name', 'validator:id,name', 'documents:id,documentable_type,documentable_id,status'])
            ->orderByDesc('charge_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Vue fournisseurs du projet : facture / paye / du, agregee en memoire sur les charges
     * deja chargees pour eviter une requete supplementaire par fournisseur.
     *
     * @param  Collection<int, DepartureCharge>  $charges
     * @return Collection<int, array<string, mixed>>
     */
    private function supplierBreakdown(Collection $charges): Collection
    {
        return $charges
            ->filter(fn (DepartureCharge $charge) => $charge->status !== DepartureCharge::STATUS_CANCELLED)
            ->groupBy(fn (DepartureCharge $charge) => $charge->supplier?->name ?: ($charge->supplier_name ?: 'Non renseigne'))
            ->map(function (Collection $group, string $name): array {
                $billed = round((float) $group->sum('amount'), 2);
                $paid = round((float) $group->sum('paid_amount'), 2);

                return [
                    'supplier' => $name,
                    'charges_count' => $group->count(),
                    'billed' => $billed,
                    'paid' => $paid,
                    'remaining' => round($billed - $paid, 2),
                    'documents_count' => $group->sum(fn (DepartureCharge $charge) => $charge->documents->count() + ($charge->attachment ? 1 : 0)),
                ];
            })
            ->sortByDesc('billed')
            ->values();
    }

    /** @return Collection<int, FinancialDocument> */
    private function documents(Departure $departure): Collection
    {
        return FinancialDocument::query()
            ->where(function (Builder $query) use ($departure) {
                $query->where('departure_id', $departure->id)
                    ->orWhere(function (Builder $inner) use ($departure) {
                        $inner->where('documentable_type', (new DepartureCharge)->getMorphClass())
                            ->whereIn('documentable_id', DepartureCharge::query()->where('departure_id', $departure->id)->select('id'));
                    });
            })
            ->with(['supplier:id,name', 'branch:id,name', 'creator:id,name'])
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->get();
    }
}
