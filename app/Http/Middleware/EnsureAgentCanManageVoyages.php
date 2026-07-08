<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentCanManageVoyages
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $identity = Str::lower(trim(($user?->name ?? '') . ' ' . ($user?->email ?? '')));

        abort_unless($user && Str::contains($identity, ['oumaima', 'oumayma']), 403);

        return $next($request);
    }
}
