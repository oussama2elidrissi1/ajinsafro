<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginRedirectService;
use App\Services\Auth\WpPasswordVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $publicLoginUrl = rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login';
        $redirectService = app(LoginRedirectService::class);

        if (Auth::check()) {
            /** @var \App\Models\User $alreadyConnected */
            $alreadyConnected = $request->user();
            $request->session()->forget('url.intended');
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
            return $this->failedLoginResponse($request, $publicLoginUrl, $login);
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
            $attempted = $this->attemptViaWordPressAccount(
                $login,
                (string) $credentials['password'],
                $remember,
                $request
            );
        }

        if (! $attempted) {
            return $this->failedLoginResponse($request, $publicLoginUrl, $login);
        }

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        /** @var \App\Models\User $user */
        $user = $request->user();
        $dest = $redirectService->destinationFor($user);

        return redirect()
            ->away($dest)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
    }

    private function attemptViaWordPressAccount(string $login, string $password, bool $remember, Request $request): bool
    {
        try {
            $query = DB::connection('wp')->table('users');

            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $wpUser = $query->where('user_email', $login)->first();
            } else {
                $wpUser = $query
                    ->where('user_login', $login)
                    ->orWhere('user_email', $login)
                    ->first();
            }

            if (! $wpUser || empty($wpUser->user_pass)) {
                return false;
            }

            $verifier = app(WpPasswordVerifier::class);
            if (! $verifier->verify($password, (string) $wpUser->user_pass)) {
                return false;
            }

            $email = trim((string) ($wpUser->user_email ?? ''));
            if ($email === '') {
                $email = Str::lower(trim((string) ($wpUser->user_login ?? ''))) . '@ajinsafro.local';
            }

            $name = trim((string) ($wpUser->display_name ?? ''));
            if ($name === '') {
                $name = trim((string) ($wpUser->user_login ?? 'Client'));
            }

            $laravelUser = User::query()
                ->where('email', $email)
                ->orWhere('name', trim((string) ($wpUser->user_login ?? '')))
                ->first();

            if (! $laravelUser) {
                $laravelUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(40),
                    'is_active' => true,
                ]);
            } else {
                $laravelUser->name = $laravelUser->name ?: $name;
                $laravelUser->email = $laravelUser->email ?: $email;
                if ($laravelUser->is_active === false) {
                    $laravelUser->is_active = true;
                }
                $laravelUser->save();
            }

            Auth::login($laravelUser, $remember);
            $request->setUserResolver(static fn () => $laravelUser);

            return true;
        } catch (\Throwable $e) {
            Log::error('WP login error: ' . $e->getMessage(), [
                'login' => $login,
                'exception' => $e,
            ]);

            return false;
        }
    }

    private function failedLoginResponse(Request $request, string $publicLoginUrl, string $login): RedirectResponse
    {
        $previousUrl = url()->previous();
        $previousHost = parse_url($previousUrl, PHP_URL_HOST);
        $currentHost = $request->getHost();

        if (is_string($previousHost) && strcasecmp($previousHost, $currentHost) === 0) {
            return back()
                ->withErrors([
                    'login' => 'Identifiants incorrects. Veuillez réessayer.',
                ])
                ->withInput(['login' => $login]);
        }

        $target = $publicLoginUrl . '?login_error=1';
        if ($login !== '') {
            $target .= '&login=' . urlencode($login);
        }

        return redirect()
            ->away($target)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
    }
}
