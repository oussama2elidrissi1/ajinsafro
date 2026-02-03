<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\CheckoutToken;
use App\Services\Package\PricingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Show checkout page.
     * 
     * GET /booking/checkout/{token}
     */
    public function show(Request $request, string $token): View
    {
        $checkoutToken = CheckoutToken::with(['session.voyage', 'voyage'])
            ->where('token', $token)
            ->firstOrFail();

        if ($checkoutToken->isPriceLockExpired()) {
            return view('booking.checkout-expired', [
                'checkoutToken' => $checkoutToken,
            ]);
        }

        $session = $checkoutToken->session;
        $voyage = $checkoutToken->voyage;

        // Get price snapshot or recalculate
        $packageState = $session->price_snapshot_json ?? [];
        
        // If no snapshot, something went wrong
        if (empty($packageState)) {
            abort(400, 'Package state not found');
        }

        $pricing = $packageState['pricing'] ?? [];
        $days = $packageState['days'] ?? [];

        // Get formatted breakdown
        $breakdown = $this->pricingService->getPricingBreakdown($pricing);

        return view('booking.checkout', [
            'checkoutToken' => $checkoutToken,
            'session' => $session,
            'voyage' => $voyage,
            'packageState' => $packageState,
            'pricing' => $pricing,
            'breakdown' => $breakdown,
            'days' => $days,
            'remainingSeconds' => $checkoutToken->remaining_lock_time,
        ]);
    }

    /**
     * Process checkout (placeholder for now).
     * 
     * POST /booking/checkout/{token}
     */
    public function process(Request $request, string $token)
    {
        $checkoutToken = CheckoutToken::with(['session', 'voyage'])
            ->where('token', $token)
            ->firstOrFail();

        if ($checkoutToken->isPriceLockExpired()) {
            return redirect()
                ->route('booking.checkout', ['token' => $token])
                ->with('error', 'Le délai de réservation a expiré.');
        }

        // TODO: Implement payment processing
        // For now, just redirect with success message

        return redirect()
            ->route('front.voyages.show', ['slug' => $checkoutToken->voyage->slug])
            ->with('success', 'Votre réservation a été enregistrée avec succès !');
    }
}
