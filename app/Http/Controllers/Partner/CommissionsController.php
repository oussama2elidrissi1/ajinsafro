<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionsController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        $query = $partner->commissions()->with(['reservation.tour', 'rule'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $commissions = $query->paginate(20)->withQueryString();

        $totalValidated = (float) $partner->commissions()->where('status', PartnerCommission::STATUS_VALIDATED)->sum('amount');
        $totalPaid = (float) $partner->commissions()->where('status', PartnerCommission::STATUS_PAID)->sum('amount');
        $totalPending = (float) $partner->commissions()->whereIn('status', [PartnerCommission::STATUS_CALCULATED, PartnerCommission::STATUS_PENDING])->sum('amount');

        return view('partner.v2.commissions.index', [
            'commissions' => $commissions,
            'totalValidated' => $totalValidated,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
        ]);
    }
}
