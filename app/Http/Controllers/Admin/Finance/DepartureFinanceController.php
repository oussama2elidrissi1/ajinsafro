<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartureChargeRequest;
use App\Http\Requests\Admin\UpdateDepartureChargeRequest;
use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\Voyage;
use App\Services\DepartureFinanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DepartureFinanceController extends Controller
{
    public function __construct(private readonly DepartureFinanceService $financeService)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('departures_finance.view'), 403);

        $filters = $this->extractFilters($request);
        $departures = $this->filteredDeparturesQuery($filters)
            ->with('voyage:id,name,wp_post_id')
            ->orderByDesc('start_date')
            ->get();

        $summaries = $departures
            ->map(fn (Departure $departure) => $this->financeService->summarizeDeparture($departure))
            ->when($filters['profitability'], function ($collection, string $profitability) {
                return $collection->filter(fn (array $row) => $profitability === 'rentable' ? $row['is_profitable'] : ! $row['is_profitable']);
            })
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        return view('admin.finance.departures.index', [
            'rows' => new LengthAwarePaginator(
                $summaries->forPage($page, $perPage)->values(),
                $summaries->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'filters' => $filters,
            'voyages' => Voyage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Departure $departure): View
    {
        abort_unless($request->user()->can('departures_finance.view'), 403);

        $departure->load(['voyage:id,name,wp_post_id', 'charges.type']);

        return view('admin.finance.departures.show', [
            'data' => $this->financeService->buildInternalTravelSheetData($departure),
            'charges' => $departure->charges()->with('type')->orderByDesc('id')->get(),
            'paymentMethodLabels' => $this->financeService->paymentMethodLabels(),
            'paymentStatusLabels' => $this->financeService->paymentStatusLabels(),
        ]);
    }

    public function create(Request $request, Departure $departure): View
    {
        abort_unless($request->user()->can('departures_finance.manage_charges'), 403);

        $departure->load('voyage:id,name');

        return view('admin.finance.departures.charges.form', [
            'departure' => $departure,
            'charge' => new DepartureCharge(['currency' => 'MAD', 'payment_method' => 'autre', 'payment_status' => 'non_paye']),
            'chargeTypes' => ChargeType::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'paymentMethodLabels' => $this->financeService->paymentMethodLabels(),
            'paymentStatusLabels' => $this->financeService->paymentStatusLabels(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreDepartureChargeRequest $request, Departure $departure): RedirectResponse
    {
        $payload = $request->validated();
        $payload['departure_id'] = $departure->id;
        $payload['voyage_id'] = $departure->voyage_id;
        $payload['currency'] = $payload['currency'] ?? 'MAD';
        if ($payload['currency'] === '') {
            $payload['currency'] = 'MAD';
        }
        $payload['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $payload['attachment'] = $request->file('attachment')->store('departure-charges/'.now()->format('Y/m'), 'public');
        }

        DepartureCharge::query()->create($payload);

        return redirect()->route('admin.finance.departures.show', $departure)->with('success', 'Charge ajoutee.');
    }

    public function edit(Request $request, Departure $departure, DepartureCharge $charge): View
    {
        abort_unless($request->user()->can('departures_finance.manage_charges'), 403);
        $this->ensureChargeBelongsToDeparture($departure, $charge);
        $departure->load('voyage:id,name');

        return view('admin.finance.departures.charges.form', [
            'departure' => $departure,
            'charge' => $charge,
            'chargeTypes' => ChargeType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'paymentMethodLabels' => $this->financeService->paymentMethodLabels(),
            'paymentStatusLabels' => $this->financeService->paymentStatusLabels(),
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateDepartureChargeRequest $request, Departure $departure, DepartureCharge $charge): RedirectResponse
    {
        $this->ensureChargeBelongsToDeparture($departure, $charge);

        $payload = $request->validated();
        $payload['currency'] = $payload['currency'] ?? 'MAD';
        if ($payload['currency'] === '') {
            $payload['currency'] = 'MAD';
        }
        $payload['updated_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            if ($charge->attachment) {
                Storage::disk('public')->delete($charge->attachment);
            }
            $payload['attachment'] = $request->file('attachment')->store('departure-charges/'.now()->format('Y/m'), 'public');
        }

        $charge->update($payload);

        return redirect()->route('admin.finance.departures.show', $departure)->with('success', 'Charge mise a jour.');
    }

    public function destroy(Request $request, Departure $departure, DepartureCharge $charge): RedirectResponse
    {
        abort_unless($request->user()->can('departures_finance.manage_charges'), 403);
        $this->ensureChargeBelongsToDeparture($departure, $charge);
        $charge->delete();

        return redirect()->route('admin.finance.departures.show', $departure)->with('success', 'Charge supprimee.');
    }

    public function attachment(Request $request, Departure $departure, DepartureCharge $charge): Response
    {
        abort_unless($request->user()->can('departures_finance.view'), 403);
        $this->ensureChargeBelongsToDeparture($departure, $charge);
        abort_unless($charge->attachment && Storage::disk('public')->exists($charge->attachment), 404);

        return Storage::disk('public')->response($charge->attachment);
    }

    public function exportExcel(Request $request, Departure $departure): Response
    {
        abort_unless($request->user()->can('departures_finance.export'), 403);

        return response()->view('admin.finance.departures.export-excel', $this->financeService->buildInternalTravelSheetData($departure), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fiche-voyage-interne-'.$departure->id.'-'.now()->format('Ymd-His').'.xls"',
        ]);
    }

    public function pdf(Request $request, Departure $departure): Response
    {
        abort_unless($request->user()->can('departures_finance.export'), 403);

        return $this->buildPdf($departure)->download($this->pdfFilename($departure));
    }

    public function print(Request $request, Departure $departure): Response
    {
        abort_unless($request->user()->can('departures_finance.export'), 403);

        return $this->buildPdf($departure)->stream($this->pdfFilename($departure));
    }

    private function buildPdf(Departure $departure)
    {
        return Pdf::loadView('admin.finance.departures.pdf.internal-travel-sheet', $this->financeService->buildInternalTravelSheetData($departure))
            ->setPaper('a4');
    }

    private function pdfFilename(Departure $departure): string
    {
        return 'fiche-voyage-interne-'.$departure->id.'-'.Str::slug((string) ($departure->voyage?->name ?: 'depart')).'.pdf';
    }

    private function extractFilters(Request $request): array
    {
        return [
            'voyage_id' => $request->filled('voyage_id') ? (int) $request->query('voyage_id') : null,
            'departure_date' => $request->filled('departure_date') ? (string) $request->query('departure_date') : null,
            'month' => $request->filled('month') ? (string) $request->query('month') : null,
            'profitability' => $request->filled('profitability') ? (string) $request->query('profitability') : null,
            'search' => $request->filled('search') ? trim((string) $request->query('search')) : null,
        ];
    }

    private function filteredDeparturesQuery(array $filters): Builder
    {
        return Departure::query()
            ->when($filters['voyage_id'], fn (Builder $query, int $voyageId) => $query->where('voyage_id', $voyageId))
            ->when($filters['departure_date'], fn (Builder $query, string $date) => $query->whereDate('start_date', $date))
            ->when($filters['month'], fn (Builder $query, string $month) => $query->whereYear('start_date', substr($month, 0, 4))->whereMonth('start_date', substr($month, 5, 2)))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $departureId = (int) preg_replace('/\D+/', '', $search);
                $query->where(function (Builder $builder) use ($search, $departureId): void {
                    $builder->orWhereHas('voyage', fn (Builder $voyage) => $voyage->where('name', 'like', '%'.$search.'%'));
                    if ($departureId > 0) {
                        $builder->orWhere('id', $departureId);
                    }
                });
            });
    }

    private function ensureChargeBelongsToDeparture(Departure $departure, DepartureCharge $charge): void
    {
        abort_unless((int) $charge->departure_id === (int) $departure->id, 404);
    }
}
