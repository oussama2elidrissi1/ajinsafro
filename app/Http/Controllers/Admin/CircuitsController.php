<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Voyage;
use Illuminate\Http\Request;

class CircuitsController extends Controller
{
    public function index()
    {
        return view('admin.circuits.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');

        if ($submenu === 'departs-dates') {
            return $this->departuresDatesPage($request);
        }

        return view('admin.circuits.' . $submenu . '.index');
    }

    private function departuresDatesPage(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $statuses = Departure::STATUSES;
        $stockStatuses = $this->stockConsumingStatuses();

        $voyages = Voyage::query()
            ->select(['id', 'wp_post_id', 'name', 'status'])
            ->whereHas('departures')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->with([
                'departures' => function ($q) use ($status, $stockStatuses) {
                    $q->orderBy('start_date')
                        ->when($status !== '' && in_array($status, Departure::STATUSES, true), fn ($inner) => $inner->where('status', $status))
                        ->with([
                            'departureHotels' => fn ($h) => $h->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                            'departureHotels.rooms' => fn ($r) => $r->orderBy('room_type')->orderBy('id'),
                        ])
                        ->withCount([
                            'reservations as active_reservations_count' => fn ($rq) => $rq->whereIn('status', $stockStatuses),
                        ])
                        ->withSum([
                            'reservations as reserved_passengers_sum' => fn ($rq) => $rq->whereIn('status', $stockStatuses),
                        ], 'passengers_count');
                },
            ])
            ->orderBy('name')
            ->paginate(10);

        $voyages->appends($request->query());

        return view('admin.circuits.departs-dates.index', [
            'voyages' => $voyages,
            'search' => $search,
            'status' => $status,
            'statuses' => $statuses,
        ]);
    }

    private function stockConsumingStatuses(): array
    {
        $consuming = (array) config('booking_lifecycle.stock_consuming_statuses', []);

        if ((bool) config('booking_lifecycle.option_holds_stock', false)) {
            $consuming = array_merge($consuming, (array) config('booking_lifecycle.stock_hold_statuses', []));
        }

        return collect($consuming)
            ->filter(fn ($status) => is_string($status) && $status !== '')
            ->unique()
            ->values()
            ->all();
    }
}
