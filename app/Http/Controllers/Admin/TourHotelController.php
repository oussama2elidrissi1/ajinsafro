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
     * Liste des circuits avec leur hôtel (cartes, filtre recherche).
     */
    public function index(Request $request): View
    {
        $wpConnectionFailed = false;
        try {
            $query = WpPost::tours()->orderByDesc('ID');

            if ($request->filled('search')) {
                $term = $request->input('search');
                $query->where('post_title', 'like', '%' . $term . '%');
            }

            $tours = $query->paginate(20)->withQueryString();
            $tourIds = $tours->pluck('ID')->toArray();
            $hotelsByTour = TourHotel::whereIn('tour_id', $tourIds)
                ->withCount('rooms')
                ->get()
                ->keyBy('tour_id');
        } catch (\Throwable $e) {
            \Log::warning('TourHotelController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $tours = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);
            $hotelsByTour = collect();
        }

        return view('admin.circuits.tour-hotels.index', compact('tours', 'hotelsByTour', 'wpConnectionFailed'));
    }

    /**
     * Détail de l'hôtel du circuit (lecture seule + tableau des chambres).
     */
    public function show(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->load('rooms');
        }

        return view('admin.circuits.tour-hotels.show', compact('tour', 'hotel'));
    }

    /**
     * Formulaire d'édition de l'hôtel du tour (tour_id = wp post ID).
     */
    public function edit(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->load('rooms');
        }

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
