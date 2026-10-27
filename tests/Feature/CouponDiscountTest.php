<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartCouponDatabase;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CouponDiscountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_coupon_that_ends_others_is_refused_only_by_the_coupons_on_the_lines_own_cart()
    {
        $five = $this->amountCoupon('FIVE', 5);
        $only = $this->amountCoupon('ONLY', 1, ['ends_other_coupons' => true]);

        $first = cart('first');
        $firstLine = $first->addProduct($this->line(1));
        $first->addCoupon($five);

        $second = cart('second');
        $secondLine = $second->addProduct($this->line(2));

        $second->itemsRepository->setCouponCode($secondLine, $only->name);
        $first->itemsRepository->setCouponCode($firstLine, $only->name);

        $this->assertSame('ONLY', $secondLine->fresh()->coupon_code);
        $this->assertNull($firstLine->fresh()->coupon_code);
    }

    #[Test]
    public function the_coupons_of_a_cart_that_is_gone_are_none()
    {
        $cart = cart('gone');
        $cart->addProduct($this->line(1));
        $id = $cart->id;
        $cart->getCart()->delete();

        $coupons = app(CartCouponDatabase::class)->getCoupons($id);

        $this->assertCount(0, $coupons);
    }

    #[Test]
    public function a_line_of_a_discount_exempt_type_gets_no_discount_from_its_coupon()
    {
        $percent = cart()->couponsRepository->addCoupon([
            'name' => 'TENPCT',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_percent' => 0.10,
            'ends_other_coupons' => false,
        ]);
        $cart = cart();
        $plain = $cart->addProduct($this->line(1));
        $warranty = $cart->addProduct($this->line(2, ['type' => ItemTypes::WARRANTY]));

        $cart->itemsRepository->setCouponCode($plain, $percent->name);
        $cart->itemsRepository->setCouponCode($warranty, $percent->name);

        $this->assertEqualsWithDelta(1.0, (float) $plain->fresh()->discount, 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $warranty->fresh()->discount, 0.0001);
        $this->assertEqualsWithDelta(19.0, (float) $cart->getCart()->grand_total, 0.0001);
    }

    #[Test]
    public function a_coupon_that_ends_others_does_not_replace_the_cart_coupon_already_on_it()
    {
        $first = $this->amountCoupon('FIRST', 2);
        $only = $this->amountCoupon('ONLY', 5, ['ends_other_coupons' => true]);
        $cart = cart();
        $cart->addProduct($this->line(1));

        $cart->addCoupon($first);
        $cart->addCoupon($only);

        // addCoupon() returns without a word when an ends_other_coupons
        // coupon meets a cart that already holds one: the first stays.
        $this->assertSame('FIRST', $cart->getCart()->coupon_code);
        $this->assertEqualsWithDelta(8.0, (float) $cart->getCart()->grand_total, 0.0001);
    }

    #[Test]
    public function a_step_coupon_gives_one_unit_free_per_step_exceeded_on_a_line()
    {
        $step = cart()->couponsRepository->addCoupon([
            'name' => 'STEP2',
            'coupon_type' => CouponTypes::STEP,
            'discount_step' => 2,
        ]);
        $cart = cart();
        $cart->addProduct($this->line(1, ['quantity' => 5]));

        // A unit is free each time the quantity goes past a whole step:
        // two units give none, three give one, five give two.
        $this->assertEquals(0, $step->discount(20, 2, 10.0, $cart));
        $this->assertEquals(10, $step->discount(30, 3, 10.0, $cart));
        $this->assertEquals(20, $step->discount(50, 5, 10.0, $cart));
    }

    #[Test]
    public function a_step_coupon_on_the_cart_gives_nothing()
    {
        $step = cart()->couponsRepository->addCoupon([
            'name' => 'STEP2',
            'coupon_type' => CouponTypes::STEP,
            'discount_step' => 2,
        ]);
        $cart = cart();
        $cart->addProduct($this->line(1, ['quantity' => 5]));

        // The cart's total passes no quantity to discount(), so a step
        // coupon only works on a line.
        $cart->addCoupon($step);

        $this->assertEqualsWithDelta(0.0, (float) $cart->getCart()->discount, 0.0001);
        $this->assertEqualsWithDelta(50.0, (float) $cart->getCart()->grand_total, 0.0001);
    }

    /**
     * The package's contract: discount() works out what a coupon takes off
     * and nothing else. Whether a coupon may be used at all (switched on,
     * inside its dates, under its use limits) is for the application to
     * decide before it puts the coupon on a cart; the shop does.
     */
    #[Test]
    public function discount_does_not_check_a_coupons_status_dates_or_use_limits()
    {
        $off = $this->amountCoupon('OFF', 3, [
            'status' => false,
            'starts_from' => now()->addWeek(),
            'ends_till' => now()->subWeek(),
            'usage_per_customer' => 0,
            'uses_per_coupon' => 1,
            'times_used' => 1,
        ]);
        $cart = cart();
        $cart->addProduct($this->line(1));

        $this->assertEquals(3, $off->discount(10, 0, 0.0, $cart));

        $cart->addCoupon($off);

        $this->assertEqualsWithDelta(7.0, (float) $cart->getCart()->grand_total, 0.0001);
    }

    private function amountCoupon(string $name, float $amount, array $extra = []): CartCoupon
    {
        return cart()->couponsRepository->addCoupon(array_merge([
            'name' => $name,
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => $amount,
            // The column defaults to true.
            'ends_other_coupons' => false,
        ], $extra));
    }

    private function line(int $plu, array $extra = []): array
    {
        return array_merge(['plu' => $plu, 'type' => ItemTypes::PLU, 'price' => 10, 'quantity' => 1], $extra);
    }
}
