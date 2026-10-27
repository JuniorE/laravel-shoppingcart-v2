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
