<?php


namespace juniorE\ShoppingCart\Data\Interfaces;


use Closure;
use juniorE\ShoppingCart\Models\CartItem;

interface CartItemDatabase
{
    public function emptyCart(int $id);

    /**
     * @param CartItem $item
     * @param float $quantity
     * @param bool|Closure $updateSubproducts
     */
    public function setQuantity(CartItem $item, float $quantity, $updateSubproducts=false): void;

    /**
     * @param CartItem $item
     * @param int $parentId
     */
    public function setParentCartItem(CartItem $item, int $parentId): void;

    /**
     * @param CartItem $item
     * @param float $percent
     */
    public function setTaxPercent(CartItem $item, float $percent): void;

    /**
     * @param CartItem $item
     * @param string $code
     */
    public function setCouponCode(CartItem $item, string $code): void;

    /**
     * @param CartItem $item
     * @param float $price
     */
    public function setPrice(CartItem $item, float $price): void;

    /**
     * @param CartItem $item
     * @param float $weight
     */
    public function setWeight(CartItem $item, float $weight): void;

    /**
     * @param CartItem $item
     * @param string $plu
     */
    public function setPLU(CartItem $item, string $plu): void;

    /**
     * @param CartItem $item
     * @param array $data
     */
    public function setAdditionalData(CartItem $item, array $data): void;
}
