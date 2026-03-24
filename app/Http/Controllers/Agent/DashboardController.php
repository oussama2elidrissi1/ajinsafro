<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $reservationsQuery = Reservation::query();
        $this->branchScope->scopeReservations($reservationsQuery, $user);
        $this->applyAgentOwnershipScope($reservationsQuery, $user->id);

        $clientsQuery = Client::query();
        $this->branchScope->scopeClients($clientsQuery, $user);
        $this->applyClientOwnershipScope($clientsQuery, $user->id);

        $stats = [
            'reservations_total' => (clone $reservationsQuery)->count(),
            'reservations_en_cours' => (clone $reservationsQuery)->where('status', Reservation::STATUS_EN_COURS)->count(),
            'reservations_validees' => (clone $reservationsQuery)->where('status', Reservation::STATUS_VALIDEE)->count(),
            'clients_count' => (clone $clientsQuery)->count(),
            'voyages_count' => Voyage::query()->count(),
            'departures_upcoming' => Departure::query()
                ->whereDate('start_date', '>=', Carbon::today())
                ->where('status', Departure::STATUS_OPEN)
                ->count(),
        ];

        $recentReservations = (clone $reservationsQuery)
            ->with(['tour:id,name', 'branch:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'tour_id', 'branch_id', 'client_first_name', 'client_last_name', 'status', 'created_at']);

        $recentClients = (clone $clientsQuery)
            ->with('branch:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'branch_id', 'client_code', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'created_at']);

        return view('agent.dashboard', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'recentClients' => $recentClients,
        ]);
    }

    private function applyAgentOwnershipScope(Builder $query, int $userId): void
    {
        $query->where(function (Builder $q) use ($userId) {
            $q->where('agent_id', $userId)
                ->orWhere('sales_manager_id', $userId)
                ->orWhere('created_by', $userId);
        });
    }

    private function applyClientOwnershipScope(Builder $query, int $userId): void
    {
        $query->where(function (Builder $q) use ($userId) {
            $q->where('assigned_to', $userId)
                ->orWhere('created_by', $userId);
        });
    }
}
