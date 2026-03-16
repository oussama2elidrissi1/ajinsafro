<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
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
        if ($user->isPartner()) {
            $partner = $user->partner;
            if ($partner && $partner->canAccessPartnerArea()) {
                return redirect()->route('partner.dashboard');
            }
            return redirect()->route('partner.pending');
        }
        // Admin (super_admin, siege_admin, branch_admin, chef_commercial, commercial, agent) : null = use redirectPath() → /admin/dashboard
        return null;
    }
}
