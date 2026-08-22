<?php

namespace App\Services\Admin;

use App\Models\AgentCommissionEntry;
use App\Models\Branch;
use App\Models\Client;
use App\Models\CustomRequest;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\ReservationMessage;
use App\Models\ReservationPayment;
use App\Models\Setting;
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

        // Évolution du taux de confirmation : confirmées cette semaine vs semaine précédente.
        $confirmedThisWeek = (clone $baseReservations)->whereIn('status', $confirmedStatuses)->where('created_at', '>=', $startOfWeek)->count();
        $confirmedPrevWeek = (clone $baseReservations)->whereIn('status', $confirmedStatuses)
            ->whereBetween('created_at', [$startOfWeek->copy()->subWeek(), $startOfWeek])
            ->count();
        $confirmationWeekEvolution = $this->percentEvolution($confirmedThisWeek, $confirmedPrevWeek);

        // Chaque bloc est isolé : une colonne manquante en production est loguée
        // sans jamais casser l'affichage du dashboard.
        return [
            'destinations' => rescue(fn () => $this->destinationBreakdown((clone $baseReservations)), ['total' => 0, 'segments' => []]),
            'upcomingDepartures' => rescue(fn () => $this->upcomingDepartures(), []),
            'alerts' => rescue(fn () => $this->pilotageAlerts($breakdown, (clone $baseReservations)), []),
            'quality' => rescue(fn () => $this->qualityIndicators($breakdown, $recentActivity), []),
            'channels' => rescue(fn () => $this->salesChannels((clone $baseReservations)), []),
            'objective' => rescue(
                fn () => $this->monthlyObjective($revenueCurrentMonth, $currency),
                ['revenue_month' => $revenueCurrentMonth, 'target' => 0.0, 'progress' => null, 'remaining' => null, 'currency' => $currency]
            ),
            'performanceChart' => rescue(fn () => $this->performanceChart($monthlyEvolution), ['has_data' => false]),
            'confirmationWeekEvolution' => $confirmationWeekEvolution,
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

    /**
     * Répartition des réservations par destination (top 4 + Autres), en pourcentage.
     */
    private function destinationBreakdown(Builder $baseReservations): array
    {
        $rows = (clone $baseReservations)
            ->whereNotNull('tour_id')
            ->selectRaw('tour_id, COUNT(*) as total')
            ->groupBy('tour_id')
            ->get();

        $total = (int) $rows->sum('total');
        if ($total === 0) {
            return ['total' => 0, 'segments' => []];
        }

        $hasDestinationColumn = Schema::hasColumn('voyages', 'destination');
        $voyages = Voyage::query()
            ->whereIn('id', $rows->pluck('tour_id')->all())
            ->get($hasDestinationColumn ? ['id', 'name', 'destination'] : ['id', 'name'])
            ->keyBy('id');

        $byDestination = [];
        foreach ($rows as $row) {
            $voyage = $voyages->get((int) $row->tour_id);
            $destination = $hasDestinationColumn ? $voyage?->destination : null;
            $label = trim((string) ($destination ?: $voyage?->name ?: 'Autres'));
            if ($label === '') {
                $label = 'Autres';
            }
            $byDestination[$label] = ($byDestination[$label] ?? 0) + (int) $row->total;
        }
        arsort($byDestination);

        $palette = ['#0b4778', '#86cce7', '#1eae7d', '#ff7b1b'];
        $segments = [];
        $index = 0;
        $othersCount = 0;
        foreach ($byDestination as $label => $count) {
            if ($index < 4) {
                $segments[] = [
                    'label' => $label,
                    'count' => $count,
                    'percent' => round(($count / $total) * 100),
                    'color' => $palette[$index],
                ];
            } else {
                $othersCount += $count;
            }
            $index++;
        }
        if ($othersCount > 0) {
            $segments[] = [
                'label' => 'Autres',
                'count' => $othersCount,
                'percent' => max(0, 100 - array_sum(array_column($segments, 'percent'))),
                'color' => '#cdd5df',
            ];
        }

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * Prochains départs (capacité, disponibilité, urgence).
     */
    private function upcomingDepartures(): array
    {
        if (!Schema::hasTable('departures')) {
            return [];
        }

        $rows = Departure::query()
            ->with('voyage:id,name,destination')
            ->whereDate('start_date', '>=', now('Africa/Casablanca')->toDateString())
            ->whereNotIn('status', [
                Departure::STATUS_DRAFT,
                Departure::STATUS_CLOSED,
                Departure::STATUS_CANCELED,
                Departure::STATUS_CANCELLED,
            ])
            ->orderBy('start_date')
            ->limit(4)
            ->get();

        return $rows->map(function (Departure $departure) {
            $total = max(0, (int) $departure->total_capacity);
            $available = max(0, (int) ($departure->available_capacity ?? 0));
            $ratio = $total > 0 ? $available / $total : 1.0;

            if ($departure->status === Departure::STATUS_FULL || ($total > 0 && $available === 0)) {
                $statusLabel = 'Complet';
                $statusColor = 'red';
            } elseif ($ratio <= 0.15) {
                $statusLabel = 'Presque complet';
                $statusColor = 'red';
            } elseif ($departure->status === Departure::STATUS_LIMITED || $ratio <= 0.4) {
                $statusLabel = 'Urgent';
                $statusColor = 'orange';
            } else {
                $statusLabel = 'Ouvert';
                $statusColor = 'green';
            }

            return [
                'id' => (int) $departure->id,
                'date' => $departure->start_date?->locale('fr')->translatedFormat('d M Y') ?? '',
                'destination' => (string) ($departure->voyage?->destination ?: '—'),
                'voyage' => (string) ($departure->voyage?->name ?: 'Voyage'),
                'available' => $available,
                'total' => $total,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
            ];
        })->all();
    }

    /**
     * Alertes de pilotage quotidien (compteurs réels).
     */
    private function pilotageAlerts(array $breakdown, Builder $baseReservations): array
    {
        $alerts = [
            [
                'label' => 'Dossiers confirmés',
                'subtitle' => 'Part des réservations confirmées',
                'value' => round($breakdown['confirmed_pct']) . '%',
                'icon' => '✓',
                'color' => 'green',
            ],
            [
                'label' => 'Réservations en attente',
                'subtitle' => 'À confirmer ou relancer',
                'value' => $breakdown['pending'],
                'icon' => '!',
                'color' => 'orange',
            ],
            [
                'label' => 'Acomptes à suivre',
                'subtitle' => 'Paiements partiels en cours',
                'value' => (clone $baseReservations)->where('status', Reservation::STATUS_PARTIALLY_PAID)->count(),
                'icon' => '○',
                'color' => 'orange',
            ],
        ];

        if (Schema::hasTable('agent_commission_entries') && Schema::hasColumn('agent_commission_entries', 'commission_status')) {
            $alerts[] = [
                'label' => 'Commissions à approuver',
                'subtitle' => 'Estimées, en attente de validation',
                'value' => AgentCommissionEntry::query()->where('commission_status', AgentCommissionEntry::STATUS_ESTIMATED)->count(),
                'icon' => '□',
                'color' => 'blue',
            ];
        }

        return $alerts;
    }

    /**
     * Indicateurs de qualité opérationnelle (distincts des alertes).
     */
    private function qualityIndicators(array $breakdown, array $recentActivity): array
    {
        $indicators = [
            [
                'label' => 'Réservations aujourd\'hui',
                'subtitle' => 'Nouvelles ventes du jour',
                'value' => $recentActivity['today'],
                'icon' => '✓',
                'color' => 'green',
            ],
            [
                'label' => 'Taux d\'annulation',
                'subtitle' => 'Part des réservations annulées',
                'value' => round($breakdown['cancelled_pct']) . '%',
                'icon' => '!',
                'color' => 'orange',
            ],
        ];

        if (Schema::hasTable('custom_requests') && Schema::hasColumn('custom_requests', 'priority')) {
            $indicators[] = [
                'label' => 'Demandes à la carte urgentes',
                'subtitle' => 'Priorité urgente à traiter',
                'value' => CustomRequest::query()
                    ->whereIn('priority', ['urgent', 'very_urgent'])
                    ->whereNotIn('status', [CustomRequest::STATUS_CONFIRMED, CustomRequest::STATUS_CANCELLED])
                    ->count(),
                'icon' => '○',
                'color' => 'orange',
            ];
        }

        if (Schema::hasTable('departures')) {
            $indicators[] = [
                'label' => 'Départs presque complets',
                'subtitle' => 'Capacité restante faible',
                'value' => Departure::query()
                    ->whereDate('start_date', '>=', now('Africa/Casablanca')->toDateString())
                    ->where(function ($query) {
                        $query->where('status', Departure::STATUS_FULL)
                            ->orWhere(function ($sub) {
                                $sub->where('total_capacity', '>', 0)
                                    ->whereRaw('COALESCE(available_capacity, 0) <= total_capacity * 0.15');
                            });
                    })
                    ->count(),
                'icon' => '□',
                'color' => 'blue',
            ];
        }

        return $indicators;
    }

    /**
     * Ventes par canal (colonne reservations.channel).
     */
    private function salesChannels(Builder $baseReservations): array
    {
        if (!Schema::hasColumn('reservations', 'channel')) {
            return [];
        }

        $rows = (clone $baseReservations)
            ->selectRaw('channel, COUNT(*) as total')
            ->groupBy('channel')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        $max = max(1, (int) $rows->max('total'));
        $labels = [
            '' => 'Agence',
            'agency' => 'Agence',
            'agence' => 'Agence',
            'client' => 'Client web',
            'web' => 'Client web',
            'partner' => 'Partenaires',
            'partenaire' => 'Partenaires',
            'group_deal' => 'Group deals',
            'group_deals' => 'Group deals',
        ];

        return $rows->map(function ($row) use ($labels, $max) {
            $key = strtolower(trim((string) ($row->channel ?? '')));

            return [
                'label' => $labels[$key] ?? ucfirst($key),
                'count' => (int) $row->total,
                'percent' => (int) round(((int) $row->total / $max) * 100),
            ];
        })->all();
    }

    /**
     * Objectif mensuel de chiffre d'affaires (cible configurable via settings).
     */
    private function monthlyObjective(float $revenueCurrentMonth, string $currency): array
    {
        $target = 0.0;
        if (Schema::hasTable('settings')) {
            $target = (float) Setting::getValue('dashboard_monthly_revenue_target', 0);
        }

        $progress = $target > 0.0 ? min(100, round(($revenueCurrentMonth / $target) * 100)) : null;
        $remaining = $target > 0.0 ? max(0.0, $target - $revenueCurrentMonth) : null;

        return [
            'revenue_month' => $revenueCurrentMonth,
            'target' => $target,
            'progress' => $progress,
            'remaining' => $remaining,
            'currency' => $currency,
        ];
    }

    /**
     * Géométrie SVG du graphe « Performance commerciale » (mêmes repères que la maquette :
     * x de 62 à 728, y de 46 (max) à 246 (zéro)).
     */
    private function performanceChart(array $monthlyEvolution): array
    {
        $count = count($monthlyEvolution);
        if ($count < 2) {
            return ['has_data' => false];
        }

        $revenues = array_map(static fn (array $m) => (float) $m['revenue'], $monthlyEvolution);
        $volumes = array_map(static fn (array $m) => (int) $m['reservations'], $monthlyEvolution);
        $hasData = array_sum($revenues) > 0 || array_sum($volumes) > 0;

        $maxRevenue = max(1.0, max($revenues));
        $maxVolume = max(1, max($volumes));

        $xFor = static fn (int $i) => 62 + ($i * (666 / max(1, $count - 1)));
        $yFor = static fn (float $value, float $max) => 246 - (($value / $max) * 200);

        $revenuePoints = [];
        $volumePoints = [];
        for ($i = 0; $i < $count; $i++) {
            $revenuePoints[] = [round($xFor($i), 1), round($yFor($revenues[$i], $maxRevenue), 1)];
            $volumePoints[] = [round($xFor($i), 1), round($yFor((float) $volumes[$i], (float) $maxVolume), 1)];
        }

        $revenueLine = $this->smoothPath($revenuePoints);
        $volumeLine = $this->smoothPath($volumePoints);
        $lastRevenue = end($revenuePoints);
        $firstRevenue = $revenuePoints[0];
        $revenueArea = $revenueLine . ' L' . $lastRevenue[0] . ',246 L' . $firstRevenue[0] . ',246 Z';

        $peakIndex = (int) array_search(max($revenues), $revenues, true);
        $yLabels = [];
        foreach ([1.0, 0.75, 0.5, 0.25] as $step) {
            $yLabels[] = ['y' => round(246 - (200 * $step)) + 4, 'label' => $this->formatCompactAmount($maxRevenue * $step)];
        }

        return [
            'has_data' => $hasData,
            'revenue_line' => $revenueLine,
            'revenue_area' => $revenueArea,
            'volume_line' => $volumeLine,
            'y_labels' => $yLabels,
            'month_labels' => array_map(static function (array $m, int $i) use ($xFor) {
                return ['x' => round($xFor($i)), 'label' => $m['label']];
            }, $monthlyEvolution, array_keys($monthlyEvolution)),
            'peak' => [
                'x' => $revenuePoints[$peakIndex][0],
                'y' => $revenuePoints[$peakIndex][1],
                'label' => $this->formatCompactAmount($revenues[$peakIndex]),
            ],
        ];
    }

    private function smoothPath(array $points): string
    {
        $path = 'M' . $points[0][0] . ',' . $points[0][1];
        for ($i = 1, $n = count($points); $i < $n; $i++) {
            [$x1, $y1] = $points[$i - 1];
            [$x2, $y2] = $points[$i];
            $dx = round(($x2 - $x1) / 3, 1);
            $path .= ' C' . ($x1 + $dx) . ',' . $y1 . ' ' . ($x2 - $dx) . ',' . $y2 . ' ' . $x2 . ',' . $y2;
        }

        return $path;
    }

    private function formatCompactAmount(float $amount): string
    {
        if ($amount >= 1000000) {
            return rtrim(rtrim(number_format($amount / 1000000, 1, ',', ' '), '0'), ',') . ' M';
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 0, ',', ' ') . ' k';
        }

        return number_format($amount, 0, ',', ' ');
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

