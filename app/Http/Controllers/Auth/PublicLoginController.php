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
        $redirectService = app(LoginRedirectService::class);

        if (Auth::check()) {
            /** @var \App\Models\User $alreadyConnected */
            $alreadyConnected = $request->user();
            return redirect()->away($redirectService->destinationFor($alreadyConnected));
        }

        $credentials = $request->validate([
            'login' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'string', 'max:190'],
            'username' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable'],
        ]);

        $login = trim((string) ($credentials['login'] ?? $credentials['email'] ?? $credentials['username'] ?? ''));
        if ($login === '') {
            return redirect()->away($publicLoginUrl . '?login_error=1');
        }

        $remember = ! empty($credentials['remember']);

        $attempted = false;

        // Try email first when identifier looks like an email address.
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $attempted = Auth::attempt(['email' => $login, 'password' => $credentials['password']], $remember);
        }

        // Fallback on username (or generic login field in users table).
        if (! $attempted) {
            $attempted = Auth::attempt(['name' => $login, 'password' => $credentials['password']], $remember);
        }

        if (! $attempted && ! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            // Last fallback for usernames that might actually be stored in email field.
            $attempted = Auth::attempt(['email' => $login, 'password' => $credentials['password']], $remember);
        }

        if (! $attempted) {
            return redirect()
                ->away($publicLoginUrl . '?login_error=1&login=' . urlencode($login))
                ->withHeaders([
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = $request->user();
        $dest = $redirectService->destinationFor($user);

        return redirect()
            ->away($dest)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
    }
}

