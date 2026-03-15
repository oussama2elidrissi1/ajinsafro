<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationMessage;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard.index');
    }

    public function page(Request $request)
    {
        $submenu = $request->route()->parameter('submenu');
        if ($submenu === 'vue-globale') {
            return $this->vueGlobale();
        }
        return view('admin.dashboard.' . $submenu . '.index');
    }

    /**
     * Vue globale : dashboard riche avec KPIs, tendances, CA, graphiques, tableaux.
     */
    public function vueGlobale()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfDay = $now->copy()->startOfDay();

        $stats = [
            'voyages_count' => Voyage::count(),
            'voyages_featured' => Voyage::where('is_featured', true)->count(),
            'branches_count' => Branch::count(),
            'branches_active' => Branch::where('is_active', true)->count(),
            'reservations_total' => Reservation::count(),
            'reservations_en_cours' => Reservation::where('status', Reservation::STATUS_EN_COURS)->count(),
            'reservations_validees' => Reservation::where('status', Reservation::STATUS_VALIDEE)->count(),
            'reservations_annulees' => Reservation::where('status', Reservation::STATUS_ANNULEE)->count(),
            'clients_count' => Client::count(),
            'messages_count' => ReservationMessage::where('status', ReservationMessage::STATUS_SENT)->count(),
        ];

        $reservationsThisMonth = Reservation::where('created_at', '>=', $startOfMonth)->count();
        $reservationsLastMonth = Reservation::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $stats['reservations_this_month'] = $reservationsThisMonth;
        $stats['reservations_last_month'] = $reservationsLastMonth;
        $stats['reservations_month_evolution'] = $reservationsLastMonth > 0
            ? round((($reservationsThisMonth - $reservationsLastMonth) / $reservationsLastMonth) * 100, 1)
            : ($reservationsThisMonth > 0 ? 100 : 0);

        $reservationsThisWeek = Reservation::where('created_at', '>=', $startOfWeek)->count();
        $reservationsToday = Reservation::where('created_at', '>=', $startOfDay)->count();
        $stats['reservations_this_week'] = $reservationsThisWeek;
        $stats['reservations_today'] = $reservationsToday;

        $hasPriceColumns = Schema::hasColumn('reservations', 'base_price') && Schema::hasColumn('reservations', 'room_supplement_total');
        if ($hasPriceColumns) {
            $revenueValidated = Reservation::where('status', Reservation::STATUS_VALIDEE)
                ->selectRaw('COALESCE(SUM(COALESCE(base_price, 0) + COALESCE(room_supplement_total, 0)), 0) as total')
                ->value('total');
            $revenueThisMonth = Reservation::where('status', Reservation::STATUS_VALIDEE)
                ->where('created_at', '>=', $startOfMonth)
                ->selectRaw('COALESCE(SUM(COALESCE(base_price, 0) + COALESCE(room_supplement_total, 0)), 0) as total')
                ->value('total');
            $revenueLastMonth = Reservation::where('status', Reservation::STATUS_VALIDEE)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->selectRaw('COALESCE(SUM(COALESCE(base_price, 0) + COALESCE(room_supplement_total, 0)), 0) as total')
                ->value('total');
            $stats['revenue_total'] = (float) $revenueValidated;
            $stats['revenue_this_month'] = (float) $revenueThisMonth;
            $stats['revenue_last_month'] = (float) $revenueLastMonth;
            $stats['revenue_month_evolution'] = $revenueLastMonth > 0
                ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : ($revenueThisMonth > 0 ? 100 : 0);
        } else {
            $stats['revenue_total'] = 0.0;
            $stats['revenue_this_month'] = 0.0;
            $stats['revenue_last_month'] = 0.0;
            $stats['revenue_month_evolution'] = 0;
        }

        $paymentBreakdown = Reservation::where('status', Reservation::STATUS_VALIDEE)
            ->select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->payment_type ?: 'Non renseigné' => $row->total]);
        $stats['payment_labels'] = $paymentBreakdown->keys()->toArray();
        $stats['payment_series'] = $paymentBreakdown->values()->toArray();

        $months = collect();
        $reservationsByMonth = [];
        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->locale('fr')->translatedFormat('M Y'));
            $reservationsByMonth[] = Reservation::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            if ($hasPriceColumns) {
                $revenueByMonth[] = (float) Reservation::where('status', Reservation::STATUS_VALIDEE)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->selectRaw('COALESCE(SUM(COALESCE(base_price, 0) + COALESCE(room_supplement_total, 0)), 0) as total')
                    ->value('total');
            } else {
                $revenueByMonth[] = 0.0;
            }
        }
        $stats['chart_months'] = $months->toArray();
        $stats['chart_reservations'] = $reservationsByMonth;
        $stats['chart_revenue'] = $revenueByMonth;

        $stats['donut_labels'] = ['En cours', 'Validées', 'Annulées'];
        $stats['donut_series'] = [
            $stats['reservations_en_cours'],
            $stats['reservations_validees'],
            $stats['reservations_annulees'],
        ];

        $lastReservationsColumns = ['id', 'tour_id', 'client_first_name', 'client_last_name', 'client_email', 'status', 'payment_type', 'created_at', 'passengers_count'];
        if ($hasPriceColumns) {
            $lastReservationsColumns[] = 'base_price';
            $lastReservationsColumns[] = 'room_supplement_total';
        }
        $lastReservations = Reservation::with(['tour:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get($lastReservationsColumns);

        $topVoyages = Reservation::query()
            ->select('tour_id', DB::raw('count(*) as total'))
            ->whereNotNull('tour_id')
            ->groupBy('tour_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $voyageIds = $topVoyages->pluck('tour_id')->filter()->unique()->values()->all();
        $voyages = Voyage::whereIn('id', $voyageIds)->get()->keyBy('id');
        $topVoyages = $topVoyages->map(function ($row) use ($voyages) {
            return [
                'voyage' => $voyages->get($row->tour_id),
                'total' => $row->total,
            ];
        })->filter(fn ($row) => $row['voyage'])->values();

        $recentBranches = Branch::where('is_active', true)->orderByDesc('created_at')->limit(5)->get(['id', 'name', 'city', 'code']);

        return view('admin.dashboard.vue-globale.index', [
            'stats' => $stats,
            'lastReservations' => $lastReservations,
            'topVoyages' => $topVoyages,
            'recentBranches' => $recentBranches,
        ]);
    }
}
