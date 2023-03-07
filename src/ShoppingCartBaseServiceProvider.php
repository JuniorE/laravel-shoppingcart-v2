<?php


namespace juniorE\ShoppingCart;


use Carbon\Laravel\ServiceProvider;
use Illuminate\Auth\Events\Login;
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
use function Illuminate\Events\queueable;

class ShoppingCartBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishResources();
        $this->registerResources();
    }

    public function register()
    {
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
        $this->loadMigrationsFrom(__DIR__."/../database/migrations");
    }

    private function publishResources()
    {
        $this->publishes([
            __DIR__.'/../config/shoppingcart.php' => config_path('shoppingcart.php'),
        ]);

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('/migrations')
        ], 'migrations');
    }
}
