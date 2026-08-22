<?php

namespace App\Providers;

use App\Models\AjAirline;
use App\Models\Message;
use App\Models\Voyage;
use App\Observers\VoyageObserver;
use App\Services\Admin\AdminMenuService;
use Illuminate\Pagination\Paginator;
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

        // Les layouts (admin, agent, client) sont en Bootstrap : la vue de pagination
        // Tailwind par défaut y affiche des chevrons SVG géants non stylés.
        // Les vues Tailwind (front public, partner_v2) passent explicitement
        // ->links('pagination::tailwind').
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

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

                    if (\Illuminate\Support\Facades\Route::has('agent.reservations.index')) {
                        $agentPortalMenu[] = [
                            'key' => 'agent_reservations',
                            'label' => 'Mes reservations',
                            'icon' => 'bx bx-calendar-check',
                            'route' => 'agent.reservations.index',
                            'href' => route('agent.reservations.index'),
                            'children' => [],
                            'active' => request()->routeIs('agent.reservations.*'),
                            'open' => false,
                            'depth' => 0,
                            'has_direct_access' => true,
                            'is_clickable' => true,
                        ];
                    }

                }

                if ($user->can('custom_requests.view') && \Illuminate\Support\Facades\Route::has('agent.custom-reservations.index')) {
                    $agentPortalMenu[] = [
                        'key' => 'agent_reservations_a_la_carte',
                        'label' => 'Reservations a la carte',
                        'icon' => 'bx bx-edit-alt',
                        'route' => 'agent.custom-reservations.index',
                        'href' => route('agent.custom-reservations.index'),
                        'children' => [],
                        'active' => request()->routeIs('agent.custom-reservations.*'),
                        'open' => false,
                        'depth' => 0,
                        'has_direct_access' => true,
                        'is_clickable' => true,
                    ];
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
