<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourHotel;
use App\Models\Wp\WpPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourHotelController extends Controller
{
    /**
     * Liste des tours avec lien vers la gestion de l'hôtel (un hôtel principal par tour).
     */
    public function index(): View
    {
        $wpConnectionFailed = false;
        try {
            $tours = WpPost::tours()
                ->orderByDesc('ID')
                ->paginate(20);

            $tourIds = $tours->pluck('ID')->toArray();
            $hotelsByTour = TourHotel::whereIn('tour_id', $tourIds)
                ->get()
                ->keyBy('tour_id');
        } catch (\Throwable $e) {
            \Log::warning('TourHotelController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $tours = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => request()->url()]);
            $hotelsByTour = collect();
        }

        return view('admin.circuits.tour-hotels.index', compact('tours', 'hotelsByTour', 'wpConnectionFailed'));
    }

    /**
     * Formulaire d'édition de l'hôtel du tour (tour_id = wp post ID).
     */
    public function edit(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $hotel = TourHotel::getForTour($tourId);

        return view('admin.circuits.tour-hotels.edit', compact('tour', 'hotel'));
    }

    /**
     * Enregistrer ou créer l'hôtel du tour (un seul par tour).
     */
    public function update(Request $request, int $tourId): RedirectResponse
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();

        $validated = $request->validate([
            'hotel_name' => 'nullable|string|max:255',
            'stars' => 'nullable|integer|min:0|max:5',
            'address' => 'nullable|string|max:500',
            'room_type' => 'nullable|string|max:255',
            'meal_plan' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->update($validated);
        } else {
            TourHotel::create(array_merge($validated, ['tour_id' => $tourId]));
        }

        return redirect()
            ->route('admin.circuits.tour-hotels.index')
            ->with('success', 'Hôtel du circuit enregistré.');
    }
}
