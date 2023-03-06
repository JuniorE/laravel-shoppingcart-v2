<?php


namespace juniorE\ShoppingCart;


use Carbon\Carbon;
use juniorE\ShoppingCart\Data\Interfaces\CartCouponDatabase;
use juniorE\ShoppingCart\Models\CartCoupon;

class CartCouponRepository implements Contracts\CartCouponRepository
{

    public function addCoupon(array $data): CartCoupon
    {
        return app(CartCouponDatabase::class)->addCoupon($data);
    }

    public function setName(CartCoupon $coupon, string $name): void
    {
        app(CartCouponDatabase::class)->setName($coupon, $name);
    }

    public function setDescription(CartCoupon $coupon, string $description): void
    {
        app(CartCouponDatabase::class)->setDescription($coupon, $description);
    }

    public function setStatus(CartCoupon $coupon, bool $status): void
    {
        app(CartCouponDatabase::class)->setStatus($coupon, $status);
    }

    public function setCouponType(CartCoupon $coupon, int $type): void
    {
        app(CartCouponDatabase::class)->setCouponType($coupon, $type);
    }

    public function setStart(CartCoupon $coupon, Carbon $start): void
    {
        app(CartCouponDatabase::class)->setStart($coupon, $start);
    }

    public function setEnd(CartCoupon $coupon, Carbon $end): void
    {
        app(CartCouponDatabase::class)->setEnd($coupon, $end);
    }

    public function setUsagePerCustomer(CartCoupon $coupon, int $limit): void
    {
        app(CartCouponDatabase::class)->setUsagePerCustomer($coupon, $limit);
    }

    public function setUsagePerCoupon(CartCoupon $coupon, int $limit): void
    {
        app(CartCouponDatabase::class)->setUsagePerCoupon($coupon, $limit);
    }

    public function increaseUsedCounter(CartCoupon $coupon, int $amount = 1): void
    {
        app(CartCouponDatabase::class)->increaseUsedCounter($coupon, $amount);
    }

    public function setConditional(CartCoupon $coupon, bool $conditional): void
    {
        app(CartCouponDatabase::class)->setConditional($coupon, $conditional);
    }

    public function setConditions(CartCoupon $coupon, array $conditions): void
    {
        app(CartCouponDatabase::class)->setConditions($coupon, $conditions);
    }

    public function setEndsOtherCoupons(CartCoupon $coupon, bool $endsOtherCoupons): void
    {
        app(CartCouponDatabase::class)->setEndsOtherCoupons($coupon, $endsOtherCoupons);
    }

    public function setDiscountAmount(CartCoupon $coupon, float $amount): void
    {
        app(CartCouponDatabase::class)->setDiscountAmount($coupon, $amount);
    }

    public function setDiscountPercent(CartCoupon $coupon, float $percent): void
    {
        app(CartCouponDatabase::class)->setDiscountPercent($coupon, $percent);
    }

    public function setDiscountQuantity(CartCoupon $coupon, int $quantity): void
    {
        app(CartCouponDatabase::class)->setDiscountQuantity($coupon, $quantity);
    }

    public function setDiscountStep(CartCoupon $coupon, int $step): void
    {
        app(CartCouponDatabase::class)->setDiscountStep($coupon, $step);
    }

    public function setAppliesToShipping(CartCoupon $coupon, bool $applies): void
    {
        app(CartCouponDatabase::class)->setAppliesToShipping($coupon, $applies);
    }

    public function setFreeShipping(CartCoupon $coupon, bool $freeShipping): void
    {
        app(CartCouponDatabase::class)->setFreeShipping($coupon, $freeShipping);
    }

    public function removeCoupon(CartCoupon $coupon): void
    {
        app(CartCouponDatabase::class)->removeCoupon($coupon);
    }
}
