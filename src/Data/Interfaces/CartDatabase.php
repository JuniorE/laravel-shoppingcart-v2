<?php

namespace juniorE\ShoppingCart\Data\Interfaces;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Models\Cart;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;

interface CartDatabase
{
    public function createCart(string $identifier): Cart;

    /**
     * @return Cart|null
     */
    public function getCart(string $identifier);

    public function createCartItem(array $product): CartItem;

    /**
     * @return CartItem|null
     */
    public function getCartItem(int $id);

    /**
     * @return CartItem|null
     */
    public function getCartItemByHash(string $hash);

    /**
     * @return Collection|CartItem[]
     */
    public function getCartItems(?int $cartIdentifier = null);

    public function getCartItemsTree(?int $cartIdentifier = null): Collection;

    public function removeCartItem(CartItem $item): void;

    /*
     * The setters below act on the cart whose id they are given. Without
     * one they fall back to the session's cart (the cart() helper), as they
     * did before they took an id.
     */

    public function setCheckoutMethod(string $method, ?int $cartId = null): void;

    public function removeShippingMethod(?int $cartId = null): void;

    public function setShippingMethod(string $method, ?int $cartId = null): void;

    public function setConversionTime(int $minutes, ?int $cartId = null): void;

    public function updateTotal(?int $cartId = null): void;

    public function setAdditionalData(array $data, ?int $cartId = null): void;

    public function addCoupon(CartCoupon $coupon, ?int $cartId = null): void;

    public function removeCoupon(?int $cartId = null): void;

    public function clear(bool $hard = false, ?int $cartId = null): void;
}
