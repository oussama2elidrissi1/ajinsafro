<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoyageController extends Controller
{
    /**
     * Active/published statuses shown on the front.
     */
    private const VISIBLE_STATUSES = ['actif', 'published', 'active'];

    /**
     * List voyages (active only), paginated.
     */
    public function index(Request $request): View
    {
        $voyages = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->select([
                'id', 'name', 'slug', 'destination', 'duration_text',
                'price_from', 'old_price', 'currency', 'featured_image',
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('front.voyages.index', [
            'voyages' => $voyages,
        ]);
    }

    /**
     * Show a single voyage by slug (active only).
     */
    public function show(string $slug): View
    {
        $voyage = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('slug', $slug)
            ->with(['images', 'programDays'])
            ->firstOrFail();

        $nextDeparture = $voyage->departures()
            ->where('status', 'open')
            ->where('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->first();

        return view('front.voyages.show', [
            'voyage' => $voyage,
            'nextDeparture' => $nextDeparture,
        ]);
    }
}
