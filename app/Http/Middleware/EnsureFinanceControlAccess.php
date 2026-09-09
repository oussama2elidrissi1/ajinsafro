<?php

namespace App\Http\Middleware;

use App\Support\FinanceControlPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde dure du module « Finance & Controle ».
 *
 * Appliquee a l'integralite du groupe de routes : meme si une permission etait attribuee
 * par erreur a un role operationnel, l'acces reste refuse. C'est la seule source de verite,
 * partagee avec le menu via le Gate FinanceControlPermissions::ACCESS_GATE.
 */
class EnsureFinanceControlAccess
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! FinanceControlPermissions::userIsFinanceAdmin($user)) {
            abort(403, 'Module Finance & Controle reserve a l\'administration.');
        }

        return $next($request);
    }
}
