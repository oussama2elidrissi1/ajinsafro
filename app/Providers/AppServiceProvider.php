<?php

namespace App\Providers;

use App\Models\AjAirline;
use App\Models\Message;
use App\Models\Voyage;
use App\Observers\VoyageObserver;
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

            if (auth()->check() && Schema::hasTable('messages')) {
                $unreadCount = Message::query()
                    ->where('recipient_id', auth()->id())
                    ->where('folder_recipient', 'inbox')
                    ->where('read', false)
                    ->count();
            }

            $view->with('unreadCount', $unreadCount);
        });
    }
}
