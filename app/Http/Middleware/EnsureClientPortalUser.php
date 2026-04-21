<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureClientPortalUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if (! method_exists($user, 'isClientPortal') || ! $user->isClientPortal()) {
            // If an agent/admin hits the client portal, send them to their own space.
            $dest = app(\App\Services\Auth\LoginRedirectService::class)->destinationFor($user);
            return redirect()->away($dest);
        }

        return $next($request);
    }
}

