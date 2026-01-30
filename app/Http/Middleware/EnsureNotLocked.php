<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotLocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('locked', false)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, ['lock-screen', 'lock-screen.unlock', 'lock-screen.activate', 'logout'])) {
            return $next($request);
        }

        return redirect()->route('lock-screen');
    }
}
