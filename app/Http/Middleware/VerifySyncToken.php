<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        $token = config('sync.token');

        if (!is_string($header) || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $provided = trim(substr($header, 7));
        if (empty($token) || !hash_equals($token, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
