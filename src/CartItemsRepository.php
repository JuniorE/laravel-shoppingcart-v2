<?php

namespace juniorE\ShoppingCart;

use juniorE\ShoppingCart\Data\Interfaces\CartCouponDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartItemDatabase;
use juniorE\ShoppingCart\Models\CartItem;

class CartItemsRepository implements Contracts\CartItemsRepository
{
    public function setQuantity(CartItem $item, float $quantity, $updateSubproducts = false): void
    {
        $this->getDatabase()->setQuantity($item, $quantity, $updateSubproducts);
    }

    public function setParentCartItem(CartItem $item, int $parentId): void
    {
        $this->getDatabase()->setParentCartItem($item, $parentId);
    }

    public function setTaxPercent(CartItem $item, float $percent): void
    {
        $this->getDatabase()->setTaxPercent($item, $percent);
    }

    public function setCouponCode(CartItem $item, string $code): void
    {
        $coupon = app(CartCouponDatabase::class)->getCoupon($code);
        if (! $coupon) {
            return;
        }

        if ($coupon->ends_other_coupons
            && app(CartCouponDatabase::class)->getCoupons($item->parent_id)->count() > 0) {
            return;
        }

        $this->getDatabase()->setCouponCode($item, $code);
    }

    public function setPrice(CartItem $item, float $price): void
    {
        $this->getDatabase()->setPrice($item, $price);
    }

    public function setWeight(CartItem $item, float $weight): void
    {
        $this->getDatabase()->setWeight($item, $weight);
    }

    public function setPLU(CartItem $item, string $plu): void
    {
        $this->getDatabase()->setPLU($item, $plu);
    }

    public function setAdditionalData(CartItem $item, array $data): void
    {
        $this->getDatabase()->setAdditionalData($item, $data);
    }

    private function getDatabase()
    {
        return app(CartItemDatabase::class);
    }
}
