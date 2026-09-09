<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\ReservationPayment;
use App\Services\DepartureFinanceService;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Encaissements clients, en lecture seule.
 *
 * Ce controleur n'ecrit JAMAIS dans `reservations` ni dans `reservation_payments` :
 * il expose les paiements deja enregistres par les agences dans le workspace reservations.
 */
class ClientCollectionController extends FinanceControlController
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::PROJECTS_VIEW);

        $filters = $this->commonFilters($request);
        $method = $this->text($request, 'payment_method');

        $payments = ReservationPayment::query()
            ->select('reservation_payments.*')
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->whereIn('reservations.id', $this->finance->validReservationsQuery()->select('reservations.id'))
            ->with([
                'reservation:id,dossier_number,client_first_name,client_last_name,branch_id,departure_id,voyage_id,total_amount,paid_amount,remaining_amount,status,payment_status',
                'reservation.branch:id,name',
                'reservation.departure:id,voyage_id,start_date',
                'reservation.departure.voyage:id,name',
                'creator:id,name',
                'financialDocuments:id,documentable_type,documentable_id,status',
            ])
            ->when($filters['departure_id'], fn ($q, int $id) => $q->where('reservations.departure_id', $id))
            ->when($filters['voyage_id'], fn ($q, int $id) => $q->where('reservations.voyage_id', $id))
            ->when($filters['branch_id'], fn ($q, int $id) => $q->where('reservations.branch_id', $id))
            ->when($method, fn ($q, string $m) => $q->where('reservation_payments.payment_method', $m))
            ->when($filters['date_from'], fn ($q, string $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, string $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->when($filters['search'], function ($q, string $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('reservations.dossier_number', 'like', '%'.$search.'%')
                        ->orWhere('reservations.client_last_name', 'like', '%'.$search.'%')
                        ->orWhere('reservations.client_first_name', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('reservation_payments.payment_date')
            ->orderByDesc('reservation_payments.id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.finance.control.client-collections', [
            'payments' => $payments,
            'filters' => $filters + ['payment_method' => $method],
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
            'paymentMethods' => DepartureFinanceService::ENTRY_PAYMENT_METHODS,
        ]);
    }
}
