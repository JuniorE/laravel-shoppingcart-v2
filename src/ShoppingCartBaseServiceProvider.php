<?php

namespace juniorE\ShoppingCart;

use Carbon\Laravel\ServiceProvider;
use juniorE\ShoppingCart\Data\Interfaces\CartCouponDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartItemDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartShippingRatesDatabase;
use juniorE\ShoppingCart\Data\Interfaces\VisitsHistoryDatabase;
use juniorE\ShoppingCart\Data\Repositories\EloquentCartCouponDatabase;
use juniorE\ShoppingCart\Data\Repositories\EloquentCartDatabase;
use juniorE\ShoppingCart\Data\Repositories\EloquentCartItemDatabase;
use juniorE\ShoppingCart\Data\Repositories\EloquentCartShippingRatesDatabase;
use juniorE\ShoppingCart\Data\Repositories\EloquentVisitsHistoryDatabase;

class ShoppingCartBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishResources();
        $this->registerResources();
    }

    public function register()
    {
        // One Cart instance per identifier per request: the cart() helper
        // resolves Cart::class with an identifier parameter, and every
        // fresh build restores the cart from the database.
        app()->scoped(CartRegistry::class);
        app()->bind(Cart::class, function ($app, array $parameters) {
            return $app->make(CartRegistry::class)->get($parameters['identifier'] ?? null);
        });

        app()->singleton(BaseCart::class, Cart::class);
        app()->bind(CartDatabase::class, EloquentCartDatabase::class);
        app()->bind(CartShippingRatesDatabase::class, EloquentCartShippingRatesDatabase::class);
        app()->bind(CartItemDatabase::class, EloquentCartItemDatabase::class);
        app()->bind(CartCouponDatabase::class, EloquentCartCouponDatabase::class);
        app()->bind(VisitsHistoryDatabase::class, EloquentVisitsHistoryDatabase::class);

        $this->mergeConfigFrom(
            __DIR__.'/../config/shoppingcart.php', 'shoppingcart'
        );
    }

    private function registerResources()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function publishResources()
    {
        $this->publishes([
            __DIR__.'/../config/shoppingcart.php' => config_path('shoppingcart.php'),
        ]);
    }
}
