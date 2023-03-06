<?php


namespace juniorE\ShoppingCart\Data\Interfaces;

use Illuminate\Support\Collection;
use \juniorE\ShoppingCart\Models\Cart;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;

interface CartDatabase
{
    public function createCart(string $identifier): Cart;

    /**
     * @param string $identifier
     * @return Cart|null
     */
    public function getCart(string $identifier);

    public function createCartItem(array $product): CartItem;

    /**
     * @param int $id
     * @return CartItem|null
     */
    public function getCartItem(int $id);

    /**
     * @param string $hash
     * @return CartItem|null
     */
    public function getCartItemByHash(string $hash);

    /**
     * @param int|null $cartIdentifier
     * @return Collection|CartItem[]
     */
    public function getCartItems(int $cartIdentifier=null);

    public function getCartItemsTree(int $cartIdentifier=null): Collection;

    public function removeCartItem(CartItem $item): void;

    public function setCheckoutMethod(string $method): void;

    public function removeShippingMethod(): void;

    public function setShippingMethod(string $method): void;

    public function setConversionTime(int $minutes): void;

    public function updateTotal(int $cartId=null): void;

    public function setAdditionalData(array $data): void;

    public function addCoupon(CartCoupon $coupon): void;

    public function removeCoupon(): void;

    public function clear(bool $hard=false): void;
}
