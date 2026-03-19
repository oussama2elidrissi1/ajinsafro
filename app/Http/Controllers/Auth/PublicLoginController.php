<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $publicLoginUrl = rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login';

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable'],
        ]);

        $remember = ! empty($credentials['remember']);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            return redirect()
                ->away($publicLoginUrl . '?login_error=1&email=' . urlencode($credentials['email']))
                ->withHeaders([
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();
        $dest = app(LoginRedirectService::class)->destinationFor($user);

        return redirect()
            ->away($dest)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
    }
}

