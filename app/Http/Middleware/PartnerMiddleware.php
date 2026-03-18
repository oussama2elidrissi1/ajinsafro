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
            $partnerUrl = rtrim((string) config('app.partner_url', 'https://partenaire.ajinsafro.net'), '/');
            return redirect()->away($partnerUrl . '/login');
        }

        if (! $request->user()->isPartner()) {
            if ($request->user()->canAccessAdmin()) {
                $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
                return redirect()->away($adminUrl . '/admin/dashboard');
            }
            return redirect('/')->with('error', 'Accès non autorisé.');
        }

        if (isset($request->user()->is_active) && ! $request->user()->is_active) {
            auth()->logout();
            $partnerUrl = rtrim((string) config('app.partner_url', 'https://partenaire.ajinsafro.net'), '/');
            return redirect()->away($partnerUrl . '/login')->with('error', 'Votre compte est désactivé.');
        }

        return $next($request);
    }
}
