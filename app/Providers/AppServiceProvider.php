<?php

namespace App\Providers;

use App\Models\AjAirline;
use App\Models\Message;
use App\Models\Voyage;
use App\Observers\VoyageObserver;
use App\Services\Admin\AdminMenuService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register SyncContext as singleton to track sync operations
        $this->app->singleton(\App\Services\Sync\SyncContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Voyage::observe(VoyageObserver::class);

        Route::bind('airline', fn ($value) => AjAirline::findOrFail($value));

        View::composer(['agent.*', 'layouts.partials.sidebar-agent', 'agent_v2.partials.sidebar', 'layouts.partials.sidebar-ajinsafro'], function ($view): void {
            $unreadCount = 0;
            $adminMenu = [];
            $agentPortalMenu = [];
            $user = auth()->user();

            if (auth()->check() && Schema::hasTable('messages')) {
                $unreadCount = Message::query()
                    ->where('recipient_id', auth()->id())
                    ->where('folder_recipient', 'inbox')
                    ->where('read', false)
                    ->count();
            }

            if ($user) {
                $menuService = app(AdminMenuService::class);
                $adminMenu = $menuService->buildForUser($user);

                // Agent portal: keep the navigation intentionally small and scoped to the agent workspace.
                $agentPortalMenu = [];
                if ($user->can('reservations.view')) {
                    if (\Illuminate\Support\Facades\Route::has('agent.catalogue')) {
                        $agentPortalMenu[] = [
                            'key' => 'agent_catalogue_voyages',
                            'label' => 'Catalogue de voyage',
                            'icon' => 'bx bx-briefcase-alt',
                            'route' => 'agent.catalogue',
                            'href' => route('agent.catalogue'),
                            'children' => [],
                            'active' => request()->routeIs('agent.catalogue'),
                            'open' => false,
                            'depth' => 0,
                            'has_direct_access' => true,
                            'is_clickable' => true,
                        ];
                    }

                    if (\Illuminate\Support\Facades\Route::has('admin.reservation-dossiers.index')) {
                        $agentPortalMenu[] = [
                            'key' => 'agent_reservations',
                            'label' => 'Mes reservations',
                            'icon' => 'bx bx-calendar-check',
                            'route' => 'admin.reservation-dossiers.index',
                            'href' => route('admin.reservation-dossiers.index'),
                            'children' => [],
                            'active' => request()->routeIs('admin.reservation-dossiers.*') || request()->routeIs('admin.reservations.index'),
                            'open' => false,
                            'depth' => 0,
                            'has_direct_access' => true,
                            'is_clickable' => true,
                        ];
                    }

                    if (\Illuminate\Support\Facades\Route::has('admin.reservations.custom-requests.index')) {
                        $agentPortalMenu[] = [
                            'key' => 'agent_reservations_a_la_carte',
                            'label' => 'Reservations a la carte',
                            'icon' => 'bx bx-edit-alt',
                            'route' => 'admin.reservations.custom-requests.index',
                            'href' => route('admin.reservations.custom-requests.index'),
                            'children' => [],
                            'active' => request()->routeIs('admin.reservations.custom-requests.*') || request()->routeIs('admin.tailor-made-requests.*'),
                            'open' => false,
                            'depth' => 0,
                            'has_direct_access' => true,
                            'is_clickable' => true,
                        ];
                    }
                }
            }

            $view->with([
                'unreadCount' => $unreadCount,
                'adminSidebarMenu' => $adminMenu,
                'agentPortalAdminMenu' => $agentPortalMenu,
            ]);
        });
    }
}
