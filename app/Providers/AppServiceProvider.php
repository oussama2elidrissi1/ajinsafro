<?php

namespace App\Providers;

use App\Models\Voyage;
use App\Observers\VoyageObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
    }
}
