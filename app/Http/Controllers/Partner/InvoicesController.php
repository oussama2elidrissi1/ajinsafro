<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicesController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;

        $reservations = Reservation::query()
            ->where('partner_id', $partner->id)
            ->with(['tour:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('partner.v2.invoices.index', compact('partner', 'reservations'));
    }

    /**
     * Servir une pièce (reçu paiement / visa) pour une réservation partenaire.
     * On réutilise la même règle de sécurité que l'admin, avec scope partenaire.
     */
    public function file(Request $request, Reservation $reservation): StreamedResponse|\Illuminate\Http\Response
    {
        $partner = $request->user()->partner;
        if ((int) $reservation->partner_id !== (int) $partner->id) {
            abort(403);
        }

        $path = $request->query('path');
        if (!$path || !is_string($path)) {
            abort(404);
        }
        $path = str_replace('\\', '/', trim($path));
        $valid = !str_contains($path, '..') && (str_starts_with($path, 'reservation-receipts/') || str_starts_with($path, 'reservation-visa/'));
        if (! $valid) {
            abort(404);
        }
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

        return response()->stream(function () use ($path) {
            $stream = Storage::disk('public')->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}

