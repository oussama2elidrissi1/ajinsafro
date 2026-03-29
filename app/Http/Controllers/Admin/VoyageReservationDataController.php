<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Services\ReservationWorkspaceCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Données voyage pour réservation (alignées sur le catalogue workspace).
 */
class VoyageReservationDataController extends Controller
{
    public function __construct(
        private ReservationWorkspaceCatalogService $catalog,
    ) {}

    public function __invoke(Request $request, Voyage $voyage): JsonResponse
    {
        abort_unless($request->user()->can('reservations.view'), 403);

        $type = (string) $request->query('prestation_type', 'package');
        if (! in_array($type, ['package', 'vol', 'hebergement'], true)) {
            $type = 'package';
        }

        $payload = $this->catalog->getVoyageReservationDataPayload($voyage, $type, $request->user());
        if ($payload === null) {
            abort(404, 'Voyage absent du catalogue workspace.');
        }

        return response()->json($payload);
    }
}
