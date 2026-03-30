<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if (Route::has('auth.public-login.get')) {
            return route('auth.public-login.get');
        }

        if (Route::has('partner.login')) {
            return route('partner.login');
        }

        if (Route::has('login')) {
            return route('login');
        }

        return rtrim((string) config('app.public_url', config('app.url')), '/') . '/login';
    }
}
