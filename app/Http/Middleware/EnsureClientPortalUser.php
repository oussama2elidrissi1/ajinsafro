<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureClientPortalUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || (string) ($user->user_type ?? '') !== 'client') {
            abort(403);
        }

        return $next($request);
    }
}

