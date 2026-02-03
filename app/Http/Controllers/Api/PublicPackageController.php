<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PackageActionRequest;
use App\Models\CheckoutToken;
use App\Models\PackageSession;
use App\Models\TravelDayItem;
use App\Models\Voyage;
use App\Services\Package\PackageStateBuilder;
use App\Services\Package\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class PublicPackageController extends Controller
{
    public function __construct(
        protected PackageStateBuilder $stateBuilder,
        protected PricingService $pricingService
    ) {}

    /**
     * GET /api/public/tours/{voyage_id}/package-state
     * 
     * Get or create package state for a tour.
     */
    public function getPackageState(Request $request, int $voyageId): JsonResponse
    {
        $voyage = Voyage::with(['programDays', 'dayItems', 'images'])->findOrFail($voyageId);

        // Get or create session
        $sessionId = $request->cookie('package_session_id') ?? $request->input('session_id');
        
        if ($sessionId) {
            $session = PackageSession::find($sessionId);
            
            // If session expired or doesn't exist, create new one
            if (!$session || $session->isExpired()) {
                $session = $this->createNewSession($voyage, $request);
            } else {
                // Extend existing session
                $session->extend();
            }
        } else {
            $session = $this->createNewSession($voyage, $request);
        }

        // Build package state
        $packageState = $this->stateBuilder->build($voyage, $session);

        // Set cookie with session ID
        $cookie = Cookie::make('package_session_id', $session->id, 60 * 24); // 24 hours

        return response()
            ->json([
                'success' => true,
                'data' => $packageState->toArray(),
            ])
            ->withCookie($cookie);
    }

    /**
     * POST /api/public/package/session/{session_id}/action
     * 
     * Perform an action on the package (add/remove/modify).
     */
    public function performAction(PackageActionRequest $request, string $sessionId): JsonResponse
    {
        $session = PackageSession::findOrFail($sessionId);
        
        if ($session->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'La session a expiré. Veuillez recharger la page.',
            ], 410);
        }

        $action = $request->input('action');
        $voyage = $session->voyage;

        // Perform the action
        $delta = 0;
        
        switch ($action) {
            case 'add':
                $addData = $request->input('add_data');
                $session->updateState('add', $addData);
                $delta = $addData['price_delta_per_person'] ?? 0;
                break;

            case 'remove':
                $itemId = $request->input('item_id');
                $item = TravelDayItem::findOrFail($itemId);
                $session->updateState('remove', ['item_id' => $itemId]);
                $delta = -$item->price_delta_per_person;
                break;

            case 'modify':
                $itemId = $request->input('item_id');
                $newOption = $request->input('new_option');
                $item = TravelDayItem::findOrFail($itemId);
                $session->updateState('modify', [
                    'item_id' => $itemId,
                    'new_option' => $newOption,
                ]);
                $delta = $this->pricingService->calculateDelta('modify', $item, $newOption);
                break;
        }

        // Rebuild package state
        $packageState = $this->stateBuilder->refresh($voyage, $session);

        // Update delta in response
        $responseData = $packageState->toArray();
        $responseData['pricing']['delta_last_action'] = $delta;

        return response()->json([
            'success' => true,
            'message' => 'Action effectuée avec succès.',
            'data' => $responseData,
        ]);
    }

    /**
     * POST /api/public/checkout/create
     * 
     * Create a checkout token with price lock.
     */
    public function createCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|uuid|exists:package_sessions,id',
        ]);

        $session = PackageSession::findOrFail($validated['session_id']);
        
        if ($session->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'La session a expiré.',
            ], 410);
        }

        $voyage = $session->voyage;

        // Create checkout token
        $token = CheckoutToken::create([
            'session_id' => $session->id,
            'voyage_id' => $voyage->id,
            'currency' => $session->currency,
            'price_locked_until' => now()->addMinutes(15),
        ]);

        // Build final package state and save snapshot
        $packageState = $this->stateBuilder->build($voyage, $session);
        $session->update([
            'price_snapshot_json' => $packageState->toArray(),
        ]);

        $redirectUrl = route('booking.checkout', ['token' => $token->token]);

        return response()->json([
            'success' => true,
            'data' => [
                'checkout_token' => $token->token,
                'redirect_url' => $redirectUrl,
                'expires_at' => $token->price_locked_until->toIso8601String(),
                'remaining_seconds' => $token->remaining_lock_time,
            ],
        ]);
    }

    /**
     * Create a new package session.
     */
    protected function createNewSession(Voyage $voyage, Request $request): PackageSession
    {
        $adults = max(1, (int) $request->input('pax_adults', 2));
        $children = max(0, (int) $request->input('pax_children', 0));
        $infants = max(0, (int) $request->input('pax_infants', 0));
        $currency = $request->input('currency', $voyage->currency ?? 'MAD');

        return PackageSession::create([
            'voyage_id' => $voyage->id,
            'pax_adults' => $adults,
            'pax_children' => $children,
            'pax_infants' => $infants,
            'currency' => $currency,
            'state_json' => [
                'removed_items' => [],
                'added_items' => [],
                'modified_items' => [],
            ],
            'expires_at' => now()->addHours(24),
        ]);
    }
}
