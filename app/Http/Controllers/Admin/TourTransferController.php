<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourTransfer;
use App\Models\Wp\WpPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourTransferController extends Controller
{
    /**
     * Liste des tours avec lien vers la gestion des transferts (arrival + departure par tour).
     */
    public function index(): View
    {
        $wpConnectionFailed = false;
        try {
            $tours = WpPost::tours()
                ->orderByDesc('ID')
                ->paginate(20);

            $tourIds = $tours->pluck('ID')->toArray();
            $transfersByTour = [];
            foreach ($tourIds as $tid) {
                $transfersByTour[$tid] = TourTransfer::getForTour($tid);
            }
        } catch (\Throwable $e) {
            \Log::warning('TourTransferController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $tours = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => request()->url()]);
            $transfersByTour = [];
        }

        return view('admin.circuits.tour-transfers.index', compact('tours', 'transfersByTour', 'wpConnectionFailed'));
    }

    /**
     * Formulaire d'édition des transferts du tour (arrival = Jour 1, departure = Dernier jour).
     */
    public function edit(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $transfers = TourTransfer::getForTour($tourId);
        $arrival = $transfers['arrival'];
        $departure = $transfers['departure'];

        return view('admin.circuits.tour-transfers.edit', compact('tour', 'arrival', 'departure'));
    }

    /**
     * Enregistrer les deux transferts (arrival + departure).
     */
    public function update(Request $request, int $tourId): RedirectResponse
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();

        $request->validate([
            'arrival.from_label' => 'nullable|string|max:255',
            'arrival.to_label' => 'nullable|string|max:255',
            'arrival.pickup_time' => 'nullable|string|max:20',
            'arrival.dropoff_time' => 'nullable|string|max:20',
            'arrival.vehicle_type' => 'nullable|string|max:255',
            'arrival.notes' => 'nullable|string|max:2000',
            'departure.from_label' => 'nullable|string|max:255',
            'departure.to_label' => 'nullable|string|max:255',
            'departure.pickup_time' => 'nullable|string|max:20',
            'departure.dropoff_time' => 'nullable|string|max:20',
            'departure.vehicle_type' => 'nullable|string|max:255',
            'departure.notes' => 'nullable|string|max:2000',
        ]);

        $arrivalData = [
            'from_label' => $request->input('arrival.from_label'),
            'to_label' => $request->input('arrival.to_label'),
            'pickup_time' => $request->input('arrival.pickup_time'),
            'dropoff_time' => $request->input('arrival.dropoff_time'),
            'vehicle_type' => $request->input('arrival.vehicle_type'),
            'notes' => $request->input('arrival.notes'),
        ];
        $departureData = [
            'from_label' => $request->input('departure.from_label'),
            'to_label' => $request->input('departure.to_label'),
            'pickup_time' => $request->input('departure.pickup_time'),
            'dropoff_time' => $request->input('departure.dropoff_time'),
            'vehicle_type' => $request->input('departure.vehicle_type'),
            'notes' => $request->input('departure.notes'),
        ];

        $transfers = TourTransfer::getForTour($tourId);

        if ($transfers['arrival']) {
            $transfers['arrival']->update($arrivalData);
        } else {
            TourTransfer::create(array_merge($arrivalData, [
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_ARRIVAL,
            ]));
        }

        if ($transfers['departure']) {
            $transfers['departure']->update($departureData);
        } else {
            TourTransfer::create(array_merge($departureData, [
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_DEPARTURE,
            ]));
        }

        return redirect()
            ->route('admin.circuits.tour-transfers.index')
            ->with('success', 'Transferts du circuit enregistrés.');
    }

    /**
     * Créer un nouveau transfert depuis le drawer (AJAX).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tour_id' => 'required|integer',
            'direction' => 'required|in:arrival,departure',
            'from_label' => 'nullable|string|max:255',
            'to_label' => 'nullable|string|max:255',
            'pickup_time' => 'nullable|string|max:20',
            'dropoff_time' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'day_number' => 'nullable|integer|min:1',
        ]);

        $transfer = TourTransfer::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('Transfert créé avec succès.'),
            'transfer' => [
                'id' => $transfer->id,
                'direction' => $transfer->direction,
                'from_label' => $transfer->from_label ?? '',
                'to_label' => $transfer->to_label ?? '',
                'pickup_time' => $transfer->pickup_time ?? '',
                'dropoff_time' => $transfer->dropoff_time ?? '',
            ],
        ]);
    }
}
