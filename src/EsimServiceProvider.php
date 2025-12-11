<?php

namespace StellarSecurity\EsimLaravel;

use Illuminate\Support\ServiceProvider;
use StellarSecurity\EsimLaravel\Client\SimApiClient;

class EsimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/sim.php', 'sim');

        $this->app->singleton(SimApiClient::class, function () {
            return new SimApiClient(
                baseUrl: rtrim(config('sim.base_url'), '/'),
                username: (string) config('sim.username'),
                password: (string) config('sim.password'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/Config/sim.php' => config_path('sim.php'),
        ], 'sim-config');
    }
}
