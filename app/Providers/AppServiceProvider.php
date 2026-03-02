<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\YandexMapsClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YandexMapsClient::class, fn() => new YandexMapsClient());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
