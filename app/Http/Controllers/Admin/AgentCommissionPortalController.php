<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionEntry;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AgentCommissionPortalController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->canViewPortal($request), 403);

        $user = $request->user();
        $filters = $this->extractCommonFilters($request);
        $baseQuery = AgentCommissionEntry::query()
            ->with(['voyage:id,name', 'reservation.departure:id,start_date', 'agent:id,name', 'travelDate:id,date'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');

        $this->scopePortalQuery($baseQuery, $request);
        $this->applyCommonFilters($baseQuery, $filters);

        $kpis = [
            'month_total' => round((float) (clone $baseQuery)
                ->whereBetween('calculated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereNotIn('commission_status', [AgentCommissionEntry::STATUS_CANCELLED, AgentCommissionEntry::STATUS_REVERSED])
                ->sum('commission_total'), 2),
            'confirmed_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_CONFIRMED)->sum('commission_total'), 2),
            'pending_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_ESTIMATED)->sum('commission_total'), 2),
            'payable_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAYABLE)->sum('commission_total'), 2),
            'paid_total' => round((float) (clone $baseQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAID)->sum('commission_total'), 2),
            'sold_count' => (int) (clone $baseQuery)->count(),
        ];

        $entries = (clone $baseQuery)->paginate(20)->withQueryString();

        $voyageIds = AgentCommissionEntry::query()
            ->when($user->cannot('commissions.view-all'), function (Builder $query) use ($request): void {
                $this->scopePortalQuery($query, $request);
            })
            ->distinct()
            ->pluck('voyage_id')
            ->filter()
            ->values();

        return view('admin.agent-commissions.index', [
            'entries' => $entries,
            'filters' => $filters,
            'voyages' => Voyage::query()->whereIn('id', $voyageIds)->orderBy('name')->get(['id', 'name']),
            'kpis' => $kpis,
            'isManagerScope' => $user->can('commissions.view-team') && ! $user->can('commissions.view-all'),
        ]);
    }

    public function show(Request $request, AgentCommissionEntry $entry): View
    {
        abort_unless($this->canViewPortal($request), 403);

        $query = AgentCommissionEntry::query()->whereKey($entry->id);
        $this->scopePortalQuery($query, $request);
        abort_unless($query->exists(), 403);

        $entry->load([
            'agent:id,name,email',
            'branch:id,name,agency_type,type,city',
            'voyage:id,name',
            'reservation.departure:id,start_date,end_date',
            'reservation.travelDate:id,date',
            'logs.creator:id,name',
        ]);

        return view('admin.agent-commissions.show', [
            'entry' => $entry,
        ]);
    }

    private function canViewPortal(Request $request): bool
    {
        $user = $request->user();

        return $user->can('commissions.view-own')
            || $user->can('commissions.view-team')
            || $user->can('commissions.view-all');
    }

    private function scopePortalQuery(Builder $query, Request $request): void
    {
        $user = $request->user();

        if ($user->can('commissions.view-all')) {
            return;
        }

        if ($user->can('commissions.view-team')) {
            $ids = $this->branchScope->portalOwnershipUserIds($user);
            $query->where(function (Builder $builder) use ($user, $ids): void {
                if ($user->branch_id) {
                    $builder->where('branch_id', $user->branch_id);
                    if ($ids !== []) {
                        $builder->orWhereIn('agent_id', $ids);
                    }
                    return;
                }

                $builder->whereIn('agent_id', $ids);
            });

            return;
        }

        $query->where('agent_id', $user->id);
    }

    /**
     * @return array{month: string|null, commission_status: string|null, voyage_id: int|null, departure_date: string|null}
     */
    private function extractCommonFilters(Request $request): array
    {
        return [
            'month' => $request->filled('month') ? (string) $request->query('month') : null,
            'commission_status' => $request->filled('commission_status') ? (string) $request->query('commission_status') : null,
            'voyage_id' => $request->filled('voyage_id') ? (int) $request->query('voyage_id') : null,
            'departure_date' => $request->filled('departure_date') ? (string) $request->query('departure_date') : null,
        ];
    }

    /**
     * @param  array{month: string|null, commission_status: string|null, voyage_id: int|null, departure_date: string|null}  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        $query->when($filters['month'], fn (Builder $builder, string $month) => $builder->forMonth($month))
            ->when($filters['commission_status'], fn (Builder $builder, string $status) => $builder->forStatus($status))
            ->when($filters['voyage_id'], fn (Builder $builder, int $voyageId) => $builder->where('voyage_id', $voyageId))
            ->when($filters['departure_date'], function (Builder $builder, string $date): void {
                $builder->whereHas('reservation.departure', fn (Builder $departure) => $departure->whereDate('start_date', $date));
            });
    }
}
