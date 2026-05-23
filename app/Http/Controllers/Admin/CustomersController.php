<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use App\Services\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomersController extends Controller
{
    private const CONFIRMED_RESERVATION_STATUSES = [
        Reservation::STATUS_CONFIRMED,
        Reservation::STATUS_PARTIALLY_PAID,
        Reservation::STATUS_PAID,
    ];

    private const NON_CONFIRMED_RESERVATION_STATUSES = [
        Reservation::STATUS_DRAFT,
        Reservation::STATUS_PENDING,
        Reservation::STATUS_OPTION,
        Reservation::STATUS_SHARED_ROOM_PENDING,
        Reservation::STATUS_SHARED_ROOM_PAIRED,
    ];

    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index()
    {
        return view('admin.customers.index');
    }

    public function page(Request $request): View
    {
        $submenu = $request->route()->parameter('submenu');

        if ($submenu === 'prospects') {
            return $this->prospects($request);
        }

        if ($submenu === 'voyageurs') {
            return $this->voyageurs($request);
        }

        return view('admin.customers.'.$submenu.'.index');
    }

    public function prospects(Request $request): View
    {
        $query = Client::query();
        $this->branchScope->scopeClients($query, $request->user());

        $query
            ->whereHas('reservations', function ($q) {
                $q->whereIn('status', self::NON_CONFIRMED_RESERVATION_STATUSES);
            })
            ->whereDoesntHave('reservations', function ($q) {
                $q->whereIn('status', self::CONFIRMED_RESERVATION_STATUSES);
            });

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('national_id_number', 'like', '%'.$search.'%')
                    ->orWhere('passport_number', 'like', '%'.$search.'%');
            });
        }

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $clients = $query
            ->withCount([
                'reservations as reservations_count' => function ($q) {
                    $q->whereIn('status', self::NON_CONFIRMED_RESERVATION_STATUSES);
                },
            ])
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        return view('admin.customers.prospects.index', [
            'clients' => $clients,
        ]);
    }

    public function voyageurs(Request $request): View
    {
        $query = Client::query();
        $this->branchScope->scopeClients($query, $request->user());

        $query->whereHas('reservations', function ($q) {
            $q->whereIn('status', self::CONFIRMED_RESERVATION_STATUSES);
        });

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('national_id_number', 'like', '%'.$search.'%')
                    ->orWhere('passport_number', 'like', '%'.$search.'%');
            });
        }

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $clients = $query
            ->withCount([
                'reservations as reservations_count' => function ($q) {
                    $q->whereIn('status', self::CONFIRMED_RESERVATION_STATUSES);
                },
            ])
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 20))
            ->withQueryString();

        return view('admin.customers.voyageurs.index', [
            'clients' => $clients,
        ]);
    }
}
