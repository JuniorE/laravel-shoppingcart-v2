<?php

namespace juniorE\ShoppingCart\Data\Interfaces;

use Closure;
use juniorE\ShoppingCart\Models\CartItem;

interface CartItemDatabase
{
    public function emptyCart(int $id);

    /**
     * @param  bool|Closure  $updateSubproducts
     */
    public function setQuantity(CartItem $item, float $quantity, $updateSubproducts = false): void;

    public function setParentCartItem(CartItem $item, int $parentId): void;

    public function setTaxPercent(CartItem $item, float $percent): void;

    public function setCouponCode(CartItem $item, string $code): void;

    public function setPrice(CartItem $item, float $price): void;

    public function setWeight(CartItem $item, float $weight): void;

    public function setPLU(CartItem $item, string $plu): void;

    public function setAdditionalData(CartItem $item, array $data): void;
}
