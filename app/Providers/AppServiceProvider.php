<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep the real route placeholder names (e.g. {restaurant}) in the generated API docs
        // instead of Scribe's renamed ones (e.g. {restaurant_uuid}), so documented URL params match the routes.
        if (class_exists(\Knuckles\Scribe\Scribe::class)) {
            \Knuckles\Scribe\Scribe::normalizeEndpointUrlUsing(fn (string $url) => $url);
        }
    }
}
