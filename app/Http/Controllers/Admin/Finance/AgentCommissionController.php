<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionEntry;
use App\Models\Branch;
use App\Models\User;
use App\Models\Voyage;
use App\Services\AgentCommissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentCommissionController extends Controller
{
    public function __construct(
        private readonly AgentCommissionService $agentCommissionService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('commissions.view-all'), 403);

        $filters = $this->extractFilters($request);
        $baseQuery = AgentCommissionEntry::query()
            ->with(['agent:id,name,branch_id', 'branch:id,name,agency_type,type', 'voyage:id,name', 'reservation.departure:id,start_date', 'travelDate:id,date'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');
        $this->applyFilters($baseQuery, $filters);

        $kpis = [
            'payable_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAYABLE)->sum('commission_total'), 2),
            'paid_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAID)->sum('commission_total'), 2),
            'pending_total' => round((float) (clone $baseQuery)
                ->whereIn('commission_status', [AgentCommissionEntry::STATUS_ESTIMATED, AgentCommissionEntry::STATUS_CONFIRMED])
                ->sum('commission_total'), 2),
            'cancelled_total' => round((float) (clone $baseQuery)
                ->whereIn('commission_status', [AgentCommissionEntry::STATUS_CANCELLED, AgentCommissionEntry::STATUS_REVERSED])
                ->sum('commission_total'), 2),
            'top_agents' => AgentCommissionEntry::query()
                ->selectRaw('agent_id, SUM(commission_total) as total_amount')
                ->whereBetween('calculated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereIn('commission_status', [AgentCommissionEntry::STATUS_CONFIRMED, AgentCommissionEntry::STATUS_PAYABLE, AgentCommissionEntry::STATUS_PAID])
                ->groupBy('agent_id')
                ->with('agent:id,name')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get(),
        ];

        return view('admin.finance.commissions.index', [
            'entries' => (clone $baseQuery)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'kpis' => $kpis,
            'agents' => User::query()->orderBy('name')->get(['id', 'name', 'branch_id']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name', 'agency_type', 'type']),
            'voyages' => Voyage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, AgentCommissionEntry $entry): View
    {
        abort_unless($request->user()->can('commissions.view-all'), 403);

        $entry->load([
            'agent:id,name,email,branch_id',
            'branch:id,name,agency_type,type,city',
            'voyage:id,name',
            'reservation.departure:id,start_date,end_date',
            'reservation.travelDate:id,date',
            'logs.creator:id,name',
        ]);

        return view('admin.finance.commissions.show', [
            'entry' => $entry,
        ]);
    }

    public function confirm(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.manage'), 403);
        $this->agentCommissionService->markAsConfirmed($entry->reservation, $request->user());

        return back()->with('success', 'Commission marquee comme confirmee.');
    }

    public function payable(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.manage'), 403);
        $this->agentCommissionService->markAsPayable($entry->reservation, $request->user());

        return back()->with('success', 'Commission marquee comme payable.');
    }

    public function paid(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.mark-paid'), 403);
        $this->agentCommissionService->markAsPaid($entry, $request->user());

        return back()->with('success', 'Commission marquee comme payee.');
    }

    public function cancel(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.manage'), 403);
        $this->agentCommissionService->cancelForReservation($entry->reservation, $request->user());

        return back()->with('success', 'Commission annulee.');
    }

    public function reverse(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.manage'), 403);
        $this->agentCommissionService->reverseForReservation($entry->reservation, $request->user());

        return back()->with('success', 'Commission reversee.');
    }

    public function adjust(Request $request, AgentCommissionEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->can('commissions.manage'), 403);

        $payload = $request->validate([
            'commission_total' => ['required', 'numeric', 'min:0'],
            'commission_adult' => ['nullable', 'numeric', 'min:0'],
            'commission_child' => ['nullable', 'numeric', 'min:0'],
            'commission_baby' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->agentCommissionService->applyManualAdjustment($entry, $payload, $request->user());

        return back()->with('success', 'Ajustement manuel enregistre.');
    }

    public function exportExcel(Request $request): Response
    {
        abort_unless($request->user()->can('commissions.export'), 403);

        return response()->view('admin.finance.commissions.export-excel', [
            'entries' => $this->buildExportQuery($request)->get(),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="commissions-agents-'.now()->format('Ymd-His').'.xls"',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        abort_unless($request->user()->can('commissions.export'), 403);

        return Pdf::loadView('admin.finance.commissions.export-pdf', [
            'entries' => $this->buildExportQuery($request)->get(),
            'generatedAt' => now(),
        ])->download('commissions-agents-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array{month: string|null, commission_status: string|null, voyage_id: int|null, departure_date: string|null, agent_id: int|null, branch_id: int|null, agency_type: string|null}
     */
    private function extractFilters(Request $request): array
    {
        return [
            'month' => $request->filled('month') ? (string) $request->query('month') : null,
            'commission_status' => $request->filled('commission_status') ? (string) $request->query('commission_status') : null,
            'voyage_id' => $request->filled('voyage_id') ? (int) $request->query('voyage_id') : null,
            'departure_date' => $request->filled('departure_date') ? (string) $request->query('departure_date') : null,
            'agent_id' => $request->filled('agent_id') ? (int) $request->query('agent_id') : null,
            'branch_id' => $request->filled('branch_id') ? (int) $request->query('branch_id') : null,
            'agency_type' => $request->filled('agency_type') ? (string) $request->query('agency_type') : null,
        ];
    }

    /**
     * @param  array{month: string|null, commission_status: string|null, voyage_id: int|null, departure_date: string|null, agent_id: int|null, branch_id: int|null, agency_type: string|null}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['month'], fn (Builder $builder, string $month) => $builder->forMonth($month))
            ->when($filters['commission_status'], fn (Builder $builder, string $status) => $builder->forStatus($status))
            ->when($filters['voyage_id'], fn (Builder $builder, int $voyageId) => $builder->where('voyage_id', $voyageId))
            ->when($filters['agent_id'], fn (Builder $builder, int $agentId) => $builder->where('agent_id', $agentId))
            ->when($filters['branch_id'], fn (Builder $builder, int $branchId) => $builder->where('branch_id', $branchId))
            ->when($filters['agency_type'], fn (Builder $builder, string $agencyType) => $builder->whereHas('branch', fn (Builder $branch) => $branch->where('agency_type', $agencyType)))
            ->when($filters['departure_date'], function (Builder $builder, string $date): void {
                $builder->whereHas('reservation.departure', fn (Builder $departure) => $departure->whereDate('start_date', $date));
            });
    }

    private function buildExportQuery(Request $request): Builder
    {
        $query = AgentCommissionEntry::query()
            ->with(['agent:id,name', 'branch:id,name', 'voyage:id,name', 'reservation.departure:id,start_date', 'travelDate:id,date'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');

        $this->applyFilters($query, $this->extractFilters($request));

        return $query;
    }
}
