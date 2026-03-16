<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     * - Invité => affiche la page login.
     * - Connecté => redirection vers l’espace adapté (admin, partenaire ou home).
     * - ?show_login=1 (ou toute valeur) => affiche la page login même si connecté (changer de compte).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Forcer l’affichage du formulaire login (changer de compte, session périmée, etc.)
                if ($request->filled('show_login')) {
                    return $next($request);
                }

                $user = Auth::guard($guard)->user();

                // Accès admin (rôles ou is_admin) → dashboard admin
                if (method_exists($user, 'canAccessAdmin') && $user->canAccessAdmin()) {
                    return redirect(RouteServiceProvider::HOME);
                }

                // Partenaire → espace partenaire
                if (method_exists($user, 'isPartner') && $user->isPartner()) {
                    $partner = $user->partner ?? null;
                    if ($partner && method_exists($partner, 'canAccessPartnerArea') && $partner->canAccessPartnerArea()) {
                        return redirect()->route('partner.dashboard');
                    }
                    return redirect()->route('partner.pending');
                }

                // Ni admin ni partenaire → page d’accueil publique
                return redirect('/');
            }
        }

        return $next($request);
    }
}
