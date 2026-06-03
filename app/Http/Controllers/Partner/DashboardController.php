<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommission;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $reservationQuery = Reservation::query()->where('partner_id', $partner->id);
        if ($request->user()->isPartnerAgent()) {
            $reservationQuery->where(function ($query) use ($request): void {
                $query->where('partner_agent_id', $request->user()->id)
                    ->orWhere('agent_id', $request->user()->id)
                    ->orWhere('created_by', $request->user()->id)
                    ->orWhere('created_by_user_id', $request->user()->id);
            });
        }

        $reservationsCount = (clone $reservationQuery)->count();
        $reservationsThisMonth = (clone $reservationQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $confirmedReservations = (clone $reservationQuery)
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_PARTIALLY_PAID, Reservation::STATUS_PAID])
            ->count();
        $pendingReservations = (clone $reservationQuery)
            ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_DRAFT, Reservation::STATUS_OPTION])
            ->count();
        $salesTotal = (float) (clone $reservationQuery)->sum('total_amount');
        $clientsCount = $partner->clients()->count();

        $commissionsTotal = (float) $partner->commissions()->whereIn('status', [PartnerCommission::STATUS_VALIDATED, PartnerCommission::STATUS_PAID])->sum('amount');
        $commissionsPending = (float) $partner->commissions()->whereIn('status', [PartnerCommission::STATUS_CALCULATED, PartnerCommission::STATUS_PENDING])->sum('amount');
        $commissionsPaid = (float) $partner->commissions()->where('status', PartnerCommission::STATUS_PAID)->sum('amount');

        $recentReservations = (clone $reservationQuery)
            ->with(['tour:id,name', 'partnerCommission'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $recentWalletTransactions = $partner->walletTransactions()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $topVoyages = (clone $reservationQuery)
            ->whereNotNull('tour_id')
            ->selectRaw('tour_id, count(*) as cnt')
            ->groupBy('tour_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->with('tour:id,name')
            ->get();

        return view('partner_v2.dashboard.index', [
            'partner' => $partner,
            'reservationsCount' => $reservationsCount,
            'reservationsThisMonth' => $reservationsThisMonth,
            'confirmedReservations' => $confirmedReservations,
            'pendingReservations' => $pendingReservations,
            'salesTotal' => $salesTotal,
            'clientsCount' => $clientsCount,
            'commissionsTotal' => $commissionsTotal,
            'commissionsPending' => $commissionsPending,
            'commissionsPaid' => $commissionsPaid,
            'recentReservations' => $recentReservations,
            'recentWalletTransactions' => $recentWalletTransactions,
            'topVoyages' => $topVoyages,
        ]);
    }
}
