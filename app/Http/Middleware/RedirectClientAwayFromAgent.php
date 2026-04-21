<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectClientAwayFromAgent
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isClientPortal') && $user->isClientPortal()) {
            return redirect()->route('client.dashboard');
        }

        return $next($request);
    }
}

