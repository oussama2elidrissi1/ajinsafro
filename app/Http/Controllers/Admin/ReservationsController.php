<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelDate;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReservationsController extends Controller
{
    public function index(): View
    {
        return view('admin.reservations.index');
    }

    public function page(Request $request): View
    {
        $submenu = $request->route()->parameter('submenu');
        return view('admin.reservations.' . $submenu . '.index');
    }

    /**
     * Calendrier des départs (admin).
     */
    public function calendar(Request $request): View
    {
        $voyages = Voyage::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedVoyageId = (int) $request->query('voyage', 0);

        return view('admin.reservations.calendrier.index', compact('voyages', 'selectedVoyageId'));
    }

    /**
     * Événements JSON pour le calendrier des départs.
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $voyageId = (int) $request->query('voyage', 0);

        $travelDatesQuery = TravelDate::query()
            ->where('is_active', true);

        $voyageForFilter = null;
        if ($voyageId > 0) {
            $voyageForFilter = Voyage::query()->find($voyageId);
            if (!$voyageForFilter || !$voyageForFilter->wp_post_id) {
                return response()->json([]);
            }
            $travelDatesQuery->where('travel_id', $voyageForFilter->wp_post_id);
        }

        $travelDates = $travelDatesQuery
            ->orderBy('date')
            ->get();

        if ($travelDates->isEmpty()) {
            return response()->json([]);
        }

        $voyages = Voyage::query()
            ->whereIn('wp_post_id', $travelDates->pluck('travel_id')->unique()->filter())
            ->get()
            ->keyBy('wp_post_id');

        $events = [];

        foreach ($travelDates as $travelDate) {
            $voyage = $voyages->get($travelDate->travel_id);
            if (!$voyage) {
                continue;
            }

            $events[] = [
                'title' => $voyage->name,
                'start' => $travelDate->date?->format('Y-m-d'),
                'allDay' => true,
                'url' => route('admin.circuits.voyages.edit', ['id' => $voyage->id]),
                'extendedProps' => [
                    'voyage_id' => $voyage->id,
                    'wp_travel_id' => $travelDate->travel_id,
                    'destination' => $voyage->destination,
                    'price_from' => $voyage->price_from,
                    'currency_symbol' => $voyage->currency_symbol,
                    'travel_date_id' => $travelDate->id,
                ],
            ];
        }

        return response()->json($events);
    }
}
