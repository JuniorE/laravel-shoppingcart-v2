<?php

namespace juniorE\ShoppingCart\Contracts;

use Carbon\Carbon;
use juniorE\ShoppingCart\Models\CartCoupon;

interface CartCouponRepository
{
    public function addCoupon(array $data): CartCoupon;

    public function setName(CartCoupon $coupon, string $name): void;

    public function setDescription(CartCoupon $coupon, string $description): void;

    public function setStatus(CartCoupon $coupon, bool $status): void;

    public function setCouponType(CartCoupon $coupon, int $type): void;

    public function setStart(CartCoupon $coupon, Carbon $start): void;

    public function setEnd(CartCoupon $coupon, Carbon $end): void;

    public function setUsagePerCustomer(CartCoupon $coupon, int $limit): void;

    public function setUsagePerCoupon(CartCoupon $coupon, int $limit): void;

    public function increaseUsedCounter(CartCoupon $coupon, int $amount = 1): void;

    public function setConditional(CartCoupon $coupon, bool $conditional): void;

    public function setConditions(CartCoupon $coupon, array $conditions): void;

    public function setEndsOtherCoupons(CartCoupon $coupon, bool $endsOtherCoupons): void;

    public function setDiscountAmount(CartCoupon $coupon, float $amount): void;

    public function setDiscountPercent(CartCoupon $coupon, float $percent): void;

    public function setDiscountQuantity(CartCoupon $coupon, int $quantity): void;

    public function setDiscountStep(CartCoupon $coupon, int $step): void;

    public function setAppliesToShipping(CartCoupon $coupon, bool $applies): void;

    public function setFreeShipping(CartCoupon $coupon, bool $freeShipping): void;

    public function removeCoupon(CartCoupon $coupon): void;
}
