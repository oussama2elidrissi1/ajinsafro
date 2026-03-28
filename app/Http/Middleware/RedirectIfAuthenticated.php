<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginRedirectService;
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

                /** @var \App\Models\User $user */
                $dest = app(LoginRedirectService::class)->destinationFor($user);
                return redirect()->away($dest);
            }
        }

        return $next($request);
    }
}
