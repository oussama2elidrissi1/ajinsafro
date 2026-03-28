<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();

        $ownershipIds = $this->branchScope->portalOwnershipUserIds($user);
        $personalIds = [$user->id];

        $reservationsQuery = Reservation::query();
        $this->branchScope->scopeReservations($reservationsQuery, $user);
        $this->applyPortalReservationOwnership($reservationsQuery, $ownershipIds);

        $clientsQuery = Client::query();
        $this->branchScope->scopeClients($clientsQuery, $user);
        $this->applyPortalClientOwnership($clientsQuery, $ownershipIds);

        $stats = $this->buildStatsFromQueries($reservationsQuery, $clientsQuery);

        $reservationsForLists = clone $reservationsQuery;
        $this->applyDashboardReservationFilters($request, $reservationsForLists, $user, $ownershipIds);
        $clientsForLists = clone $clientsQuery;
        $this->applyDashboardClientFilters($request, $clientsForLists, $user, $ownershipIds);

        $statsPersonal = null;
        $statsTeamOnly = null;
        $teamAgentStats = collect();
        $directReports = collect();

        if ($isManager) {
            $rqPersonal = Reservation::query();
            $this->branchScope->scopeReservations($rqPersonal, $user);
            $this->applyPortalReservationOwnership($rqPersonal, $personalIds);

            $cqPersonal = Client::query();
            $this->branchScope->scopeClients($cqPersonal, $user);
            $this->applyPortalClientOwnership($cqPersonal, $personalIds);

            $statsPersonal = $this->buildStatsFromQueries($rqPersonal, $cqPersonal);

            $teamOnlyIds = array_values(array_diff($ownershipIds, $personalIds));
            if ($teamOnlyIds !== []) {
                $rqTeam = Reservation::query();
                $this->branchScope->scopeReservations($rqTeam, $user);
                $this->applyPortalReservationOwnership($rqTeam, $teamOnlyIds);

                $cqTeam = Client::query();
                $this->branchScope->scopeClients($cqTeam, $user);
                $this->applyPortalClientOwnership($cqTeam, $teamOnlyIds);

                $statsTeamOnly = $this->buildStatsFromQueries($rqTeam, $cqTeam);
            } else {
                $statsTeamOnly = [
                    'reservations_total' => 0,
                    'reservations_en_cours' => 0,
                    'reservations_validees' => 0,
                    'clients_count' => 0,
                ];
            }

            $directReports = $this->branchScope->portalDirectReports($user);
            $teamAgentStats = $this->buildTeamAgentReservationStats($user, $directReports);
        }

        $recentReservations = (clone $reservationsForLists)
            ->with([
                'tour:id,name',
                'branch:id,name',
                'agent:id,name',
            ])
            ->latest()
            ->limit(8)
            ->get(['id', 'tour_id', 'branch_id', 'agent_id', 'client_first_name', 'client_last_name', 'status', 'created_at']);

        $recentClients = (clone $clientsForLists)
            ->with('branch:id,name')
            ->latest()
            ->limit(8)
            ->get(['id', 'branch_id', 'client_code', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'created_at', 'assigned_to']);

        $calendarEvents = $this->buildCalendarEvents(clone $reservationsForLists);

        $recentActivityReservations = (clone $reservationsForLists)
            ->latest()
            ->limit(6)
            ->get(['id', 'status', 'created_at', 'client_first_name', 'client_last_name']);

        $filterAgentOptions = User::query()
            ->whereIn('id', $ownershipIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'job_title']);

        return view('agent.dashboard', [
            'isManager' => $isManager,
            'stats' => $stats,
            'statsPersonal' => $statsPersonal,
            'statsTeamOnly' => $statsTeamOnly,
            'teamAgentStats' => $teamAgentStats,
            'directReports' => $directReports,
            'recentReservations' => $recentReservations,
            'recentClients' => $recentClients,
            'calendarEvents' => $calendarEvents,
            'recentActivityReservations' => $recentActivityReservations,
            'filterAgentOptions' => $filterAgentOptions,
            'filterAgentId' => $request->integer('agent_id') ?: null,
            'filterReservationStatus' => $request->query('res_status'),
            'filterClientAgentId' => $request->integer('client_agent_id') ?: null,
        ]);
    }

    /**
     * Filtres optionnels (manager / agent) : agent lié au dossier, statut réservation.
     *
     * @param  Builder<\App\Models\Reservation>  $query
     * @param  list<int>  $ownershipIds
     */
    private function applyDashboardReservationFilters(Request $request, Builder $query, User $user, array $ownershipIds): void
    {
        if ($request->filled('agent_id')) {
            $aid = $request->integer('agent_id');
            if (in_array($aid, $ownershipIds, true)) {
                $query->where(function (Builder $q) use ($aid) {
                    $q->where('agent_id', $aid)
                        ->orWhere('sales_manager_id', $aid)
                        ->orWhere('created_by', $aid);
                });
            }
        }

        $status = $request->query('res_status');
        if (is_string($status) && in_array($status, [
            Reservation::STATUS_EN_COURS,
            Reservation::STATUS_VALIDEE,
            Reservation::STATUS_ANNULEE,
        ], true)) {
            $query->where('status', $status);
        }
    }

    /**
     * @param  Builder<\App\Models\Client>  $query
     * @param  list<int>  $ownershipIds
     */
    private function applyDashboardClientFilters(Request $request, Builder $query, User $user, array $ownershipIds): void
    {
        if (! $request->filled('client_agent_id')) {
            return;
        }
        $aid = $request->integer('client_agent_id');
        if (! in_array($aid, $ownershipIds, true)) {
            return;
        }
        $query->where(function (Builder $q) use ($aid) {
            $q->where('assigned_to', $aid)
                ->orWhere('created_by', $aid);
        });
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $reservationsQuery
     * @param  Builder<\App\Models\Client>  $clientsQuery
     * @return array<string, int>
     */
    private function buildStatsFromQueries(Builder $reservationsQuery, Builder $clientsQuery): array
    {
        return [
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
    }

    /**
     * @param  Collection<int, User>  $directReports
     * @return Collection<int, array{user: User, reservations_total: int, reservations_en_cours: int, reservations_validees: int}>
     */
    private function buildTeamAgentReservationStats(User $manager, Collection $directReports): Collection
    {
        return $directReports->map(function (User $member) use ($manager) {
            $q = Reservation::query();
            $this->branchScope->scopeReservations($q, $manager);
            $this->applyPortalReservationOwnership($q, [$member->id]);

            return [
                'user' => $member,
                'reservations_total' => (clone $q)->count(),
                'reservations_en_cours' => (clone $q)->where('status', Reservation::STATUS_EN_COURS)->count(),
                'reservations_validees' => (clone $q)->where('status', Reservation::STATUS_VALIDEE)->count(),
            ];
        });
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $reservationsQuery
     * @return list<array{title: string, start: string, backgroundColor?: string, borderColor?: string}>
     */
    private function buildCalendarEvents(Builder $reservationsQuery): array
    {
        $rows = (clone $reservationsQuery)
            ->with(['tour:id,name', 'travelDate'])
            ->whereNotNull('travel_date_id')
            ->orderByDesc('created_at')
            ->limit(120)
            ->get(['id', 'tour_id', 'travel_date_id', 'status']);

        $palette = [
            Reservation::STATUS_VALIDEE => ['#16a34a', '#16a34a'],
            Reservation::STATUS_EN_COURS => ['#ca8a04', '#ca8a04'],
            Reservation::STATUS_ANNULEE => ['#dc2626', '#dc2626'],
        ];
        $default = ['#0083c4', '#0083c4'];

        $events = [];
        foreach ($rows as $reservation) {
            $date = optional($reservation->travelDate)->date;
            if ($date === null) {
                continue;
            }
            $start = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
            $colors = $palette[$reservation->status] ?? $default;
            $title = ($reservation->tour?->name ? $reservation->tour->name.' · ' : '').'# '.$reservation->id;
            $events[] = [
                'title' => $title,
                'start' => $start,
                'backgroundColor' => $colors[0],
                'borderColor' => $colors[1],
            ];
        }

        return $events;
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
                ->orWhereIn('created_by', $userIds);
        });
    }

    /**
     * @param  list<int>  $userIds
     */
    private function applyPortalClientOwnership(Builder $query, array $userIds): void
    {
        if ($userIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $q) use ($userIds) {
            $q->whereIn('assigned_to', $userIds)
                ->orWhereIn('created_by', $userIds);
        });
    }
}
