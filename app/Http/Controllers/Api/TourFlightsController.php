<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/public/tours/{wpPostId}/flights
 * Returns outbound and inbound flight for front (WP plugin).
 * Convention: outbound = Jour 1, inbound = Dernier jour (N). Front must display outbound on day 1 and inbound on last day.
 */
class TourFlightsController extends Controller
{
    public function __invoke(int $wpPostId): JsonResponse
    {
        $voyage = Voyage::where('wp_post_id', $wpPostId)->with(['outboundFlight', 'inboundFlight'])->first();

        $outbound = null;
        $inbound = null;
        if ($voyage) {
            if ($voyage->outboundFlight) {
                $outbound = $voyage->outboundFlight->toDisplayArray();
            }
            if ($voyage->inboundFlight) {
                $inbound = $voyage->inboundFlight->toDisplayArray();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'outbound' => $outbound,
                'inbound' => $inbound,
            ],
        ]);
    }
}
