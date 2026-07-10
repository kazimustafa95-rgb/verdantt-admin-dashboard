<?php

namespace App\Providers;

use App\Auth\ApiUserProvider;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('verdantt_api', fn ($app, array $config) => $app->make(ApiUserProvider::class));
    }
}
