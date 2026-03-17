<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHotelRequest;
use App\Http\Requests\Admin\UpdateHotelRequest;
use App\Models\Hotel;
use App\Models\HotelAmenity;
use App\Services\HotelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelBackofficeController extends Controller
{
    public function __construct(
        protected HotelService $service,
    ) {}

    public function index(Request $request): View
    {
        $query = Hotel::query()->withCount('roomTypes');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('country', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $hotels = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.hotels.index', compact('hotels'));
    }

    public function create(): View
    {
        $amenities = HotelAmenity::orderBy('label')->get();
        return view('admin.hotels.create', compact('amenities'));
    }

    public function store(StoreHotelRequest $request): RedirectResponse
    {
        $hotel = $this->service->create($request->validated());
        return redirect()->route('admin.hotels.show', $hotel)->with('success', 'Hôtel créé.');
    }

    public function show(Hotel $hotel): View
    {
        $hotel->load(['images', 'roomTypes', 'amenities', 'reviews' => fn ($q) => $q->limit(5)]);
        return view('admin.hotels.show', compact('hotel'));
    }

    public function edit(Hotel $hotel): View
    {
        $hotel->load(['images', 'roomTypes', 'amenities']);
        $amenities = HotelAmenity::orderBy('label')->get();
        return view('admin.hotels.edit', compact('hotel', 'amenities'));
    }

    public function update(UpdateHotelRequest $request, Hotel $hotel): RedirectResponse
    {
        $this->service->update($hotel, $request->validated());
        return redirect()->route('admin.hotels.show', $hotel)->with('success', 'Hôtel mis à jour.');
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        $hotel->delete();
        return redirect()->route('admin.hotels.index')->with('success', 'Hôtel supprimé.');
    }
}
