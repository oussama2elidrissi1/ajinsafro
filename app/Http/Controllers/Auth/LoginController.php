<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\LoginRedirectService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Send the response after the user was authenticated.
     * Partners go to partner area, others to admin dashboard.
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
            ? new \Illuminate\Http\JsonResponse([], 204)
            : redirect()->to($this->redirectPath());
    }

    /**
     * Redirection après connexion selon le type de compte.
     * Partenaire → espace partenaire ; admin (tous rôles) → dashboard admin via redirectPath().
     */
    protected function authenticated(Request $request, $user)
    {
        /** @var \App\Models\User $user */
        $dest = app(LoginRedirectService::class)->destinationFor($user);

        // If destination equals the default redirectPath (HOME), let the trait handle "intended" logic.
        if ($dest === route('admin.dashboard')) {
            return null;
        }

        return redirect()->to($dest);
    }

    /**
     * Logout must always redirect to Ajinsafro public website.
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to('https://ajinsafro.net/');
    }
}
