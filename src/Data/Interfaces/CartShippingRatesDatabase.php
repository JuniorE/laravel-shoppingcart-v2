<?php

namespace juniorE\ShoppingCart\Data\Interfaces;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Models\CartShippingRate;

interface CartShippingRatesDatabase
{
    /**
     * @return Collection|CartShippingRate[]
     */
    public function shippingRatesForMethod(string $method): Collection;

    public function addShippingRate(array $data): CartShippingRate;

    public function setMethod(CartShippingRate $rate, string $method): void;

    public function setMethodDescription(CartShippingRate $rate, string $description): void;

    public function setPrice(CartShippingRate $rate, float $price): void;

    public function setMinimumCartPrice(CartShippingRate $rate, float $price): void;

    public function removeShippingRate(CartShippingRate $rate): void;
}
