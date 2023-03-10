<?php

namespace juniorE\ShoppingCart\Contracts;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;

interface Cart
{
    /**
     * Add a product to the cart
     *
     * @param  bool  $forceNewLine
     */
    public function addProduct(array $product, bool $forceNewLine = null): CartItem;

    /**
     * @return Collection|CartItem[]
     */
    public function addProducts(...$products): Collection;

    /**
     * Remove a cart item from the cart
     */
    public function removeItem(CartItem $item): void;

    /**
     * Add a coupon to the cart
     */
    public function addCoupon(CartCoupon $coupon): void;

    /**
     * Get all active coupon codes.
     *
     * @returns Collection|CartItem[]|null
     */
    public function getAllCouponsOnCart();

    /**
     * Set the checkoutMethod, this also marks the cart as 'closed' and
     * calculates the conversion time.
     *
     * @return mixed
     */
    public function setCheckoutMethod(string $checkoutMethod): void;

    /**
     * Get the delivery cost
     *
     * @return mixed
     */
    public function getDeliveryCost();

    /**
     * Set the shippingMethod
     *
     * @return mixed
     */
    public function setShippingMethod(string $checkoutMethod): void;

    /**
     * Get Items from cart
     *
     * @return Collection|CartItem[]
     */
    public function items(): Collection;

    /**
     * Get the cart
     */
    public function getCart(): \juniorE\ShoppingCart\Models\Cart;

    /**
     * Destroy the shopping cart
     */
    public function destroy(): void;

    /**
     * @return CartItem|null
     */
    public function getItem(int $id);

    /**
     * @return Collection|CartItem[]
     */
    public function getItems(int $cartId);

    public function markVisited(string $plu): void;

    public function updateIdentifier(string $identifier): void;

    public function getShippingRate();

    public function contains(array $plus): bool;

    public function itemsTree(): Collection;

    public function merge(Cart $other): Cart;
}
