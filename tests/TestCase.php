<?php

namespace StellarSecurity\EsimLaravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StellarSecurity\EsimLaravel\EsimServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [EsimServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sim.base_url', 'https://sim.example.test/api');
        $app['config']->set('sim.username', 'service-user');
        $app['config']->set('sim.password', 'service-password');
        $app['config']->set('sim.timeout', 35);
        $app['config']->set('sim.connect_timeout', 20);
        $app['config']->set('sim.request_id_header', 'X-Request-ID');
    }
}
