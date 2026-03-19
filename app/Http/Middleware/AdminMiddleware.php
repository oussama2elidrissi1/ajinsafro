<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/login');
        }

        if (! $request->user()->canAccessAdmin()) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/admin/dashboard')->with('error', 'Access denied.');
        }

        if (isset($request->user()->is_active) && ! $request->user()->is_active) {
            auth()->logout();
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/login')->with('error', 'Your account is disabled.');
        }

        return $next($request);
    }
}
