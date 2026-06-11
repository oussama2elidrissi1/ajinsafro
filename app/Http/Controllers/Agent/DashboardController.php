<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BranchScopeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->can('dashboard.view'), 403);
        $isManager = $user->isManager();

        $scope = $this->normalizeScope($request->query('scope'), $isManager);
        $directReports = $isManager
            ? $this->branchScope->portalDirectReports($user)
            : collect();

        $reservationsQuery = Reservation::query();
        $this->branchScope->scopeReservations($reservationsQuery, $user);
        // Aligné sur {@see ReservationListQueryService::baseQuery} : agent / commercial / chef avec agence
        // voient le portefeuille agence (pas seulement les lignes où ils sont agent_id / created_by).
        if ($isManager && $scope === 'mine') {
            $this->applyPortalReservationOwnership($reservationsQuery, [$user->id]);
        } else {
            $this->branchScope->constrainReservationQueryForPortalUser($reservationsQuery, $user);
        }

        $stats = $this->buildDashboardStats(clone $reservationsQuery);

        $recentReservations = (clone $reservationsQuery)
            ->with([
                'tour:id,name',
                'travelDate:id,date',
                'agent:id,name,email',
                'creator:id,name,email',
                'createdBy:id,name,email',
            ])
            ->withCount('passengers')
            ->latest()
            ->limit(6)
            ->get([
                'id',
                'tour_id',
                'travel_date_id',
                'agent_id',
                'created_by',
                'created_by_user_id',
                'dossier_number',
                'reservation_dossier_id',
                'client_first_name',
                'client_last_name',
                'status',
                'total_amount',
                'paid_amount',
                'created_at',
            ]);

        $todayStats = $this->buildTodayStats(clone $reservationsQuery);
        $managerStats = $isManager
            ? $this->buildManagerStats($user, $directReports)
            : null;
        $customRequestStats = $this->buildCustomRequestStats($user);
        $recentCustomRequests = CustomRequest::query()
            ->with(['creator:id,name,email', 'assignedAgent:id,name,email'])
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'request_number',
                'customer_full_name',
                'desired_destination',
                'desired_departure_date',
                'travelers_count',
                'status',
                'priority',
                'created_by',
                'assigned_to',
                'created_at',
            ]);

        return view('agent.dashboard', [
            'stats' => $stats,
            'todayStats' => $todayStats,
            'recentReservations' => $recentReservations,
            'customRequestStats' => $customRequestStats,
            'recentCustomRequests' => $recentCustomRequests,
            'scope' => $scope,
            'isManager' => $isManager,
            'directReports' => $directReports,
            'managerStats' => $managerStats,
        ]);
    }

    /**
     * @return array{reservations_total: int, reservations_validees: int, reservations_en_cours: int, revenue_generated: float}
     */
    private function buildDashboardStats(Builder $reservationsQuery): array
    {
        return [
            'reservations_total' => (clone $reservationsQuery)->count(),
            'reservations_validees' => (clone $reservationsQuery)->where('status', Reservation::STATUS_VALIDEE)->count(),
            'reservations_en_cours' => (clone $reservationsQuery)->where('status', Reservation::STATUS_EN_COURS)->count(),
            'revenue_generated' => (float) (clone $reservationsQuery)->sum('total_amount'),
        ];
    }

    private function buildManagerStats(User $user, $directReports): array
    {
        $personalQuery = Reservation::query();
        $this->branchScope->scopeReservations($personalQuery, $user);
        $this->applyPortalReservationOwnership($personalQuery, [$user->id]);

        $teamOnlyIds = $directReports->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        $teamOnlyQuery = Reservation::query();
        $this->branchScope->scopeReservations($teamOnlyQuery, $user);
        $this->applyPortalReservationOwnership($teamOnlyQuery, $teamOnlyIds);

        $agentRows = $directReports->map(function (User $agent) use ($user): array {
            $query = Reservation::query();
            $this->branchScope->scopeReservations($query, $user);
            $this->applyPortalReservationOwnership($query, [(int) $agent->id]);

            return [
                'user' => $agent,
                'reservations_total' => (clone $query)->count(),
                'reservations_en_cours' => (clone $query)->where('status', Reservation::STATUS_EN_COURS)->count(),
                'reservations_validees' => (clone $query)->where('status', Reservation::STATUS_VALIDEE)->count(),
                'revenue_generated' => (float) (clone $query)->sum('total_amount'),
            ];
        });

        return [
            'personal' => $this->buildDashboardStats($personalQuery),
            'team_only' => $this->buildDashboardStats($teamOnlyQuery),
            'agents' => $agentRows,
        ];
    }

    private function buildCustomRequestStats(User $user): array
    {
        $query = CustomRequest::query()->visibleTo($user);

        return [
            'total' => (clone $query)->count(),
            'new' => (clone $query)->where('status', CustomRequest::STATUS_NEW)->count(),
            'processing' => (clone $query)->whereIn('status', [
                CustomRequest::STATUS_ASSIGNED,
                CustomRequest::STATUS_PROCESSING,
                CustomRequest::STATUS_MISSING_INFO,
                CustomRequest::STATUS_MODIFICATION_REQUESTED,
            ])->count(),
            'quoted' => (clone $query)->whereIn('status', [
                CustomRequest::STATUS_QUOTE_PREPARED,
                CustomRequest::STATUS_QUOTE_SENT,
                CustomRequest::STATUS_WAITING_CUSTOMER,
            ])->count(),
            'confirmed' => (clone $query)->where('status', CustomRequest::STATUS_CONFIRMED)->count(),
        ];
    }

    /**
     * @return array{reservations_today: int, pending_today: int, notifications: array<int, string>}
     */
    private function buildTodayStats(Builder $reservationsQuery): array
    {
        $today = Carbon::today();

        $reservationsToday = (clone $reservationsQuery)
            ->whereDate('created_at', $today)
            ->count();

        $pendingToday = (clone $reservationsQuery)
            ->whereDate('created_at', $today)
            ->where('status', Reservation::STATUS_EN_COURS)
            ->count();

        $notifications = [];

        if ($pendingToday > 0) {
            $notifications[] = $pendingToday.' réservation(s) du jour à suivre.';
        }

        $latestPending = (clone $reservationsQuery)
            ->where('status', Reservation::STATUS_EN_COURS)
            ->latest()
            ->first(['client_first_name', 'client_last_name']);

        if ($latestPending !== null) {
            $clientName = trim(($latestPending->client_first_name ?? '').' '.($latestPending->client_last_name ?? ''));
            $notifications[] = 'Dernier dossier en attente : '.($clientName !== '' ? $clientName : 'client non renseigné').'.';
        }

        if ($notifications === []) {
            $notifications[] = 'Aucune alerte prioritaire aujourd\'hui.';
        }

        return [
            'reservations_today' => $reservationsToday,
            'pending_today' => $pendingToday,
            'notifications' => $notifications,
        ];
    }

    /**
     * @param  list<int>  $userIds
     */
    private function applyPortalReservationOwnership(Builder $query, array $userIds): void
    {
        if ($userIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $q) use ($userIds) {
            $q->whereIn('agent_id', $userIds)
                ->orWhereIn('sales_manager_id', $userIds)
                ->orWhereIn('created_by', $userIds)
                ->orWhereIn('created_by_user_id', $userIds);
        });
    }

    /**
     * @return 'mine'|'team'
     */
    private function normalizeScope(mixed $value, bool $isManager): string
    {
        if ($isManager) {
            return $value === 'mine' ? 'mine' : 'team';
        }

        return 'mine';
    }
}
