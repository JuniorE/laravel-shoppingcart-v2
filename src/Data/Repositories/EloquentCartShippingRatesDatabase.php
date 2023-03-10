<?php

namespace juniorE\ShoppingCart\Data\Repositories;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Data\Interfaces\CartShippingRatesDatabase;
use juniorE\ShoppingCart\Models\CartShippingRate;

class EloquentCartShippingRatesDatabase implements CartShippingRatesDatabase
{
    public function addShippingRate(array $data): CartShippingRate
    {
        return CartShippingRate::create($data);
    }

    public function setMethod(CartShippingRate $rate, string $method): void
    {
        $rate->update([
            'method' => $method,
        ]);
    }

    public function setMethodDescription(CartShippingRate $rate, string $description): void
    {
        $rate->update([
            'method_description' => $description,
        ]);
    }

    public function setPrice(CartShippingRate $rate, float $price): void
    {
        $rate->update([
            'price' => $price,
        ]);
    }

    public function setMinimumCartPrice(CartShippingRate $rate, float $price): void
    {
        $rate->update([
            'minimum_cart_price' => $price,
        ]);
    }

    public function shippingRatesForMethod(string $method): Collection
    {
        return CartShippingRate::where('method', $method)->get();
    }

    public function removeShippingRate(CartShippingRate $rate): void
    {
        $rate->delete();
    }
}
