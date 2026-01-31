<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StartBookingController extends Controller
{
    /**
     * Page de démarrage de réservation (Booking Start).
     * Reçoit les paramètres depuis WordPress et charge le voyage correspondant.
     */
    public function __invoke(Request $request): View
    {
        $type = $request->query('type', '');
        $slug = $request->query('slug', '');
        $dateRaw = $request->query('date', '');
        $adults = (int) $request->query('adults', 0);
        $children = (int) $request->query('children', 0);
        $infant = (int) $request->query('infant', 0);

        $date = $this->normalizeDate($dateRaw);

        $item = null;
        if ($type === 'tour' && $slug !== '') {
            $item = Voyage::where('slug', $slug)->first();
            if (! $item) {
                abort(404);
            }
        }

        return view('booking.start', [
            'type' => $type,
            'item' => $item,
            'date' => $date,
            'adults' => $adults,
            'children' => $children,
            'infant' => $infant,
        ]);
    }

    /**
     * Convertit une date au format WP (mm/dd/yyyy) en yyyy-mm-dd.
     */
    private function normalizeDate(string $dateRaw): ?string
    {
        $dateRaw = trim($dateRaw);
        if ($dateRaw === '') {
            return null;
        }

        $parsed = Carbon::createFromFormat('m/d/Y', $dateRaw);
        if ($parsed === false) {
            return null;
        }

        return $parsed->format('Y-m-d');
    }
}
