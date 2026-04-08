<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
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
        $isManager = $user->isManager();

        $scope = $this->normalizeScope($request->query('scope'), $isManager);

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
            ])
            ->latest()
            ->limit(6)
            ->get([
                'id',
                'tour_id',
                'travel_date_id',
                'client_first_name',
                'client_last_name',
                'status',
                'created_at',
            ]);

        $todayStats = $this->buildTodayStats(clone $reservationsQuery);

        return view('agent.dashboard', [
            'stats' => $stats,
            'todayStats' => $todayStats,
            'recentReservations' => $recentReservations,
            'scope' => $scope,
            'isManager' => $isManager,
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
            'revenue_generated' => (float) (clone $reservationsQuery)->sum('paid_amount'),
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
        if ($isManager && $value === 'team') {
            return 'team';
        }

        return 'mine';
    }
}
