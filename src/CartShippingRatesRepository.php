<?php


namespace juniorE\ShoppingCart;


use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Data\Interfaces\CartShippingRatesDatabase;
use juniorE\ShoppingCart\Models\CartShippingRate;

class CartShippingRatesRepository implements Contracts\CartShippingRatesRepository
{
    /**
     * @param string $method
     * @return Collection|CartShippingRate[]
     */
    public function shippingRatesForMethod(string $method): Collection
    {
        return app(CartShippingRatesDatabase::class)->shippingRatesForMethod($method);
    }

    public function addShippingRate(array $data): CartShippingRate
    {
        return app(CartShippingRatesDatabase::class)->addShippingRate($data);
    }

    public function setMethod(CartShippingRate $rate, string $method): void
    {
        app(CartShippingRatesDatabase::class)->setMethod($rate, $method);
    }

    public function setMethodDescription(CartShippingRate $rate, string $description): void
    {
        app(CartShippingRatesDatabase::class)->setMethodDescription($rate, $description);
    }

    public function setPrice(CartShippingRate $rate, float $price): void
    {
        app(CartShippingRatesDatabase::class)->setPrice($rate, $price);
    }

    public function setMinimumCartPrice(CartShippingRate $rate, float $price): void
    {
        app(CartShippingRatesDatabase::class)->setMinimumCartPrice($rate, $price);
    }

    public function removeShippingRate(CartShippingRate $rate): void
    {
        app(CartShippingRatesDatabase::class)->removeShippingRate($rate);
    }
}
