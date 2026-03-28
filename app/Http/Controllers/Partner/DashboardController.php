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
        $partner = $request->user()->partner;
        $reservationsCount = Reservation::where('partner_id', $partner->id)->count();
        $reservationsThisMonth = Reservation::where('partner_id', $partner->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $clientsCount = $partner->clients()->count();

        $commissionsTotal = (float) $partner->commissions()->whereIn('status', [PartnerCommission::STATUS_VALIDATED, PartnerCommission::STATUS_PAID])->sum('amount');
        $commissionsPending = (float) $partner->commissions()->whereIn('status', [PartnerCommission::STATUS_CALCULATED, PartnerCommission::STATUS_PENDING])->sum('amount');
        $commissionsPaid = (float) $partner->commissions()->where('status', PartnerCommission::STATUS_PAID)->sum('amount');

        $recentReservations = Reservation::where('partner_id', $partner->id)
            ->with(['tour:id,name', 'partnerCommission'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $topVoyages = Reservation::where('partner_id', $partner->id)
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
            'clientsCount' => $clientsCount,
            'commissionsTotal' => $commissionsTotal,
            'commissionsPending' => $commissionsPending,
            'commissionsPaid' => $commissionsPaid,
            'recentReservations' => $recentReservations,
            'topVoyages' => $topVoyages,
        ]);
    }
}
