<?php

namespace App\Providers;

use App\Services\Unifi\UnifiService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolve the portal's UniFi client from admin-managed settings
        // (database) with .env as the fallback.
        $this->app->bind(UnifiService::class, fn () => UnifiService::fromSettings());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
    }
}
