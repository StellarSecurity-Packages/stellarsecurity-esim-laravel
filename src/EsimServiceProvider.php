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
                baseUrl: rtrim((string) config('sim.base_url'), '/'),
                username: (string) config('sim.username'),
                password: (string) config('sim.password'),
                timeoutSeconds: (int) config('sim.timeout', 35),
                connectTimeoutSeconds: (int) config('sim.connect_timeout', 20),
                requestIdHeader: (string) config('sim.request_id_header', 'X-Request-ID'),
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
