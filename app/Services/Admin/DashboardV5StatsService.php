<?php

namespace App\Services\Admin;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationMessage;
use App\Models\ReservationPayment;
use App\Models\User;
use App\Models\Voyage;
use App\Services\BranchScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class DashboardV5StatsService
{
    public function __construct(
        private readonly BranchScopeService $branchScope
    ) {}

    public function build(User $user): array
    {
        $now = now('Africa/Casablanca');
        $startOfToday = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfPrevMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfPrevMonth = $now->copy()->subMonth()->endOfMonth();

        $baseReservations = Reservation::query();
        $this->branchScope->scopeReservations($baseReservations, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($baseReservations, $user);

        $baseClients = Client::query();
        $this->branchScope->scopeClients($baseClients, $user);

        $visibleBranchIds = $this->branchScope->visibleBranchIds($user);
        $canSeeAll = $this->branchScope->canSeeAllBranches($user);

        $voyagesCount = $this->countActiveVoyages();
        $agenciesCount = $this->countActiveBranches($visibleBranchIds, $canSeeAll);
        $reservationsCount = (clone $baseReservations)->count();
        $clientsCount = (clone $baseClients)->count();

        $reservationsCurrentMonth = (clone $baseReservations)->where('created_at', '>=', $startOfMonth)->count();
        $reservationsPreviousMonth = (clone $baseReservations)->whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth])->count();
        $reservationsEvolution = $this->percentEvolution($reservationsCurrentMonth, $reservationsPreviousMonth);

        $confirmedStatuses = [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PARTIALLY_PAID,
            Reservation::STATUS_PAID,
        ];
        $cancelledStatuses = [Reservation::STATUS_CANCELLED];
        $pendingStatuses = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_DRAFT,
            Reservation::STATUS_OPTION,
            Reservation::STATUS_EXPIRED,
        ];

        $amountColumn = $this->detectReservationAmountColumn();
        $currency = $this->detectCurrency($baseReservations);

        $revenueTotal = $this->sumRevenue((clone $baseReservations), $amountColumn, $confirmedStatuses);
        $revenueCurrentMonth = $this->sumRevenue(
            (clone $baseReservations)->where('created_at', '>=', $startOfMonth),
            $amountColumn,
            $confirmedStatuses
        );
        $revenuePreviousMonth = $this->sumRevenue(
            (clone $baseReservations)->whereBetween('created_at', [$startOfPrevMonth, $endOfPrevMonth]),
            $amountColumn,
            $confirmedStatuses
        );
        $revenueEvolution = $this->percentEvolution($revenueCurrentMonth, $revenuePreviousMonth);

        $messagesCount = $this->countMessages($visibleBranchIds, $canSeeAll);

        $breakdown = [
            'pending' => (clone $baseReservations)->whereIn('status', $pendingStatuses)->count(),
            'confirmed' => (clone $baseReservations)->whereIn('status', $confirmedStatuses)->count(),
            'cancelled' => (clone $baseReservations)->whereIn('status', $cancelledStatuses)->count(),
        ];
        $breakdown['total'] = $breakdown['pending'] + $breakdown['confirmed'] + $breakdown['cancelled'];
        $breakdown['pending_pct'] = $this->safePercent($breakdown['pending'], $breakdown['total']);
        $breakdown['confirmed_pct'] = $this->safePercent($breakdown['confirmed'], $breakdown['total']);
        $breakdown['cancelled_pct'] = $this->safePercent($breakdown['cancelled'], $breakdown['total']);
        $breakdown['other_pct'] = max(0.0, 100.0 - ($breakdown['pending_pct'] + $breakdown['confirmed_pct'] + $breakdown['cancelled_pct']));

        $recentActivity = [
            'today' => (clone $baseReservations)->where('created_at', '>=', $startOfToday)->count(),
            'week' => (clone $baseReservations)->where('created_at', '>=', $startOfWeek)->count(),
            'month' => $reservationsCurrentMonth,
        ];

        $monthlyEvolution = $this->monthlyEvolution((clone $baseReservations), $amountColumn, $confirmedStatuses);
        $paymentMethods = $this->paymentMethods((clone $baseReservations), $confirmedStatuses, $amountColumn);
        $latestReservations = $this->latestReservations((clone $baseReservations), $amountColumn, $currency);
        $topTours = $this->topTours((clone $baseReservations));
        $activeAgencies = $this->activeAgencies($visibleBranchIds, $canSeeAll);

        return [
            'stats' => [
                'voyages' => $voyagesCount,
                'agencies' => $agenciesCount,
                'reservations' => $reservationsCount,
                'clients' => $clientsCount,
                'reservations_evolution' => $reservationsEvolution,
                'revenue_total' => $revenueTotal,
                'revenue_month' => $revenueCurrentMonth,
                'revenue_evolution' => $revenueEvolution,
                'currency' => $currency,
                'messages' => $messagesCount,
            ],
            'recentActivity' => $recentActivity,
            'reservationBreakdown' => $breakdown,
            'monthlyEvolution' => $monthlyEvolution,
            'paymentMethods' => $paymentMethods,
            'latestReservations' => $latestReservations,
            'topTours' => $topTours,
            'activeAgencies' => $activeAgencies,
        ];
    }

    private function countActiveVoyages(): int
    {
        $query = Voyage::query();
        if (Schema::hasColumn('voyages', 'status')) {
            $query->whereIn('status', ['publish', 'published', 'active']);
        }

        return $query->count();
    }

    private function countActiveBranches(?array $visibleBranchIds, bool $canSeeAll): int
    {
        $query = Branch::query()->whereNull('archived_at');
        if (Schema::hasColumn('branches', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('branches', 'status')) {
            $query->where('status', Branch::STATUS_ACTIVE);
        }
        if (!$canSeeAll && $visibleBranchIds !== null) {
            if ($visibleBranchIds === []) {
                return 0;
            }
            $query->whereIn('id', $visibleBranchIds);
        }

        return $query->count();
    }

    private function detectReservationAmountColumn(): ?string
    {
        $columns = ['total_amount', 'paid_amount', 'total_base', 'amount', 'grand_total', 'total_price'];
        foreach ($columns as $column) {
            if (Schema::hasColumn('reservations', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function detectCurrency(Builder $reservations): string
    {
        if (!Schema::hasColumn('voyages', 'currency')) {
            return 'DH';
        }

        $tourId = (clone $reservations)->whereNotNull('tour_id')->value('tour_id');
        if (!$tourId) {
            return 'DH';
        }

        $raw = (string) (Voyage::query()->whereKey($tourId)->value('currency') ?? 'MAD');
        $normalized = strtoupper(trim($raw));

        return match ($normalized) {
            'EUR' => 'EUR',
            'USD' => 'USD',
            default => 'DH',
        };
    }

    private function sumRevenue(Builder $query, ?string $amountColumn, array $confirmedStatuses): float
    {
        if (!$amountColumn) {
            return 0.0;
        }

        return (float) (clone $query)
            ->whereIn('status', $confirmedStatuses)
            ->sum($amountColumn);
    }

    private function percentEvolution(float|int $current, float|int $previous): float
    {
        if ((float) $previous <= 0.0) {
            return (float) $current > 0.0 ? 100.0 : 0.0;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    private function safePercent(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }

    private function countMessages(?array $visibleBranchIds, bool $canSeeAll): int
    {
        if (!Schema::hasTable('reservation_messages')) {
            return 0;
        }

        $query = ReservationMessage::query();
        if (Schema::hasColumn('reservation_messages', 'status')) {
            $query->where('status', ReservationMessage::STATUS_SENT);
        }

        if (!$canSeeAll && $visibleBranchIds !== null && Schema::hasColumn('reservation_messages', 'from_branch_id')) {
            if ($visibleBranchIds === []) {
                return 0;
            }
            $query->whereIn('from_branch_id', $visibleBranchIds);
        }

        return $query->count();
    }

    private function monthlyEvolution(Builder $baseReservations, ?string $amountColumn, array $confirmedStatuses): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now('Africa/Casablanca')->subMonths($i);
            $monthReservations = (clone $baseReservations)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $monthRevenue = 0.0;
            if ($amountColumn !== null) {
                $monthRevenue = (float) (clone $baseReservations)
                    ->whereIn('status', $confirmedStatuses)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum($amountColumn);
            }

            $months[] = [
                'label' => $date->locale('fr')->translatedFormat('M Y'),
                'reservations' => $monthReservations,
                'revenue' => $monthRevenue,
            ];
        }

        return $months;
    }

    private function paymentMethods(Builder $baseReservations, array $confirmedStatuses, ?string $amountColumn): array
    {
        if (Schema::hasTable('reservation_payments') && Schema::hasColumn('reservation_payments', 'payment_method')) {
            $query = ReservationPayment::query()
                ->selectRaw('payment_method, COUNT(*) as total, COALESCE(SUM(amount), 0) as amount')
                ->whereNotNull('payment_method')
                ->where('payment_method', '<>', '')
                ->whereIn('reservation_id', (clone $baseReservations)->whereIn('status', $confirmedStatuses)->select('id'))
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $total = (int) $query->sum('total');

            return $query->map(function ($row) use ($total) {
                return [
                    'label' => (string) $row->payment_method,
                    'count' => (int) $row->total,
                    'percent' => $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0.0,
                ];
            })->all();
        }

        if (!Schema::hasColumn('reservations', 'payment_type')) {
            return [];
        }

        $query = (clone $baseReservations)
            ->whereIn('status', $confirmedStatuses)
            ->selectRaw('payment_type, COUNT(*) as total')
            ->groupBy('payment_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $total = (int) $query->sum('total');

        return $query->map(function ($row) use ($total) {
            $label = trim((string) ($row->payment_type ?? ''));
            if ($label === '') {
                $label = 'Non renseigne';
            }

            return [
                'label' => $label,
                'count' => (int) $row->total,
                'percent' => $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0.0,
            ];
        })->all();
    }

    private function latestReservations(Builder $baseReservations, ?string $amountColumn, string $currency): array
    {
        $columns = [
            'id',
            'reservation_dossier_id',
            'tour_id',
            'client_external_id',
            'client_first_name',
            'client_last_name',
            'client_email',
            'status',
            'payment_type',
            'created_at',
            'total_amount',
            'paid_amount',
            'total_base',
        ];
        $columns = array_values(array_filter($columns, fn (string $c) => Schema::hasColumn('reservations', $c)));

        $rows = (clone $baseReservations)
            ->with(['tour:id,name', 'client:id,full_name,email'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get($columns);

        return $rows->map(function (Reservation $reservation) use ($amountColumn, $currency) {
            $clientName = trim((string) ($reservation->client_first_name . ' ' . $reservation->client_last_name));
            if ($clientName === '') {
                $clientName = (string) optional($reservation->client)->full_name;
            }
            if ($clientName === '') {
                $clientName = 'Client';
            }

            $amount = 0.0;
            if ($amountColumn && isset($reservation->{$amountColumn})) {
                $amount = (float) $reservation->{$amountColumn};
            }

            return [
                'id' => (int) $reservation->id,
                'dossier_id' => $reservation->reservation_dossier_id ? (int) $reservation->reservation_dossier_id : null,
                'client_name' => $clientName,
                'client_email' => (string) ($reservation->client_email ?: optional($reservation->client)->email ?: ''),
                'tour_name' => (string) optional($reservation->tour)->name,
                'status' => (string) $reservation->status,
                'payment' => (string) ($reservation->payment_type ?? ''),
                'amount' => $amount,
                'currency' => $currency,
                'date' => optional($reservation->created_at)?->format('d/m/Y H:i') ?? '',
            ];
        })->all();
    }

    private function topTours(Builder $baseReservations): array
    {
        $rows = (clone $baseReservations)
            ->whereNotNull('tour_id')
            ->selectRaw('tour_id, COUNT(*) as total')
            ->groupBy('tour_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $tours = Voyage::query()
            ->whereIn('id', $rows->pluck('tour_id')->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($tours) {
            $tour = $tours->get((int) $row->tour_id);
            if (!$tour) {
                return null;
            }

            return [
                'id' => (int) $tour->id,
                'name' => (string) $tour->name,
                'count' => (int) $row->total,
            ];
        })->filter()->values()->all();
    }

    private function activeAgencies(?array $visibleBranchIds, bool $canSeeAll): array
    {
        $query = Branch::query()
            ->select(['id', 'name', 'city', 'code', 'status', 'is_active'])
            ->whereNull('archived_at');
        if (Schema::hasColumn('branches', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('branches', 'status')) {
            $query->where('status', Branch::STATUS_ACTIVE);
        }
        if (!$canSeeAll && $visibleBranchIds !== null) {
            if ($visibleBranchIds === []) {
                return [];
            }
            $query->whereIn('id', $visibleBranchIds);
        }

        $query->withCount('reservations');

        return $query->orderBy('name')
            ->limit(4)
            ->get()
            ->map(fn (Branch $branch) => [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'city' => (string) ($branch->city ?? ''),
                'code' => (string) ($branch->code ?? ''),
                'status' => (string) ($branch->status ?? ''),
                'reservations_count' => (int) ($branch->reservations_count ?? 0),
            ])->all();
    }
}

