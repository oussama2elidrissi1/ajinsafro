<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Public website (ajinsafro.net)
            $publicDomain = config('app.public_domain');
            $publicRoutes = Route::middleware('web');
            if (is_string($publicDomain) && $publicDomain !== '') {
                $publicRoutes->domain($publicDomain);
            }
            $publicRoutes->group(base_path('routes/public.php'));

            // Internal back-office (booking.ajinsafro.net)
            $adminDomain = config('app.admin_domain');
            $adminRoutes = Route::middleware('web');
            if (is_string($adminDomain) && $adminDomain !== '') {
                $adminRoutes->domain($adminDomain);
            }
            $adminRoutes->group(base_path('routes/admin.php'));

            // Partner portal on dedicated subdomain
            Route::middleware('web')
                ->domain(config('app.partner_domain', 'partenaire.ajinsafro.net'))
                ->group(base_path('routes/partner.php'));
        });
    }
}
