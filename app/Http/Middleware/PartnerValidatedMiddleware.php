<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerValidatedMiddleware
{
    /**
     * Restrict access to partner area to validated partners only.
     * Pending/rejected/suspended partners are redirected to the "en attente" page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $partner = $user->partner;

        if (! $partner) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Profil partenaire introuvable.');
        }

        if (! $partner->canAccessPartnerArea()) {
            if ($request->routeIs('partner.pending') || $request->routeIs('partner.logout')) {
                return $next($request);
            }
            return redirect()->route('partner.pending');
        }

        return $next($request);
    }
}
