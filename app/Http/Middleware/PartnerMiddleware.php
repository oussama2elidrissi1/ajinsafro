<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->isPartner()) {
            if ($request->user()->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect('/')->with('error', 'Accès non autorisé.');
        }

        if (isset($request->user()->is_active) && ! $request->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Votre compte est désactivé.');
        }

        return $next($request);
    }
}
