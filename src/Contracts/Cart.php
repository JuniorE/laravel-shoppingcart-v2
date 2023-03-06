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
     * @param array $product
     * @param bool $forceNewLine
     * @return CartItem
     */
    public function addProduct(array $product, bool $forceNewLine=null): CartItem;

    /**
     * @param ...$products
     * @return Collection|CartItem[]
     */
    public function addProducts(...$products): Collection;

    /**
     * Remove a cart item from the cart
     *
     * @param CartItem $item
     * @return void
     */
    public function removeItem(CartItem $item): void;

    /**
     * Add a coupon to the cart
     *
     * @param CartCoupon $coupon
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
     * @param string $checkoutMethod
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
     * @param string $checkoutMethod
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
     *
     * @return \juniorE\ShoppingCart\Models\Cart
     */
    public function getCart(): \juniorE\ShoppingCart\Models\Cart;

    /**
     * Destroy the shopping cart
     */
    public function destroy(): void;

    /**
     * @param int $id
     * @return CartItem|null
     */
    public function getItem(int $id);

    /**
     * @param int $cartId
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
