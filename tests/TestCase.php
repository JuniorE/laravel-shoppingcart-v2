<?php

namespace juniorE\ShoppingCart\Tests;

use Illuminate\Foundation\Application;
use juniorE\ShoppingCart\ShoppingCartBaseServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array|string[]
     */
    protected function getPackageProviders($app)
    {
        return [
            ShoppingCartBaseServiceProvider::class,

        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
