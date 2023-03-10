<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Tests\TestCase;

class ExemptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function doesnt_calculate_discount_on_discount_exempt_items()
    {
        $this->assertNotEmpty(config('shoppingcart.discount_exempt_types'));

        $gourmetMeat = [
            'plu' => 5,
            'price' => 49.72,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::PLU,
        ];

        $gourmetWarranty = [
            'plu' => 6,
            'price' => 15,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::WARRANTY,
        ];

        $gourmetRent = [
            'plu' => 6,
            'price' => 4.95,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::RENT,
        ];

        $cart = cart();
        $cart->addProducts($gourmetMeat, $gourmetWarranty, $gourmetRent);

        $this->assertCount(3, $cart->items());

        $coupon10PERCENT = $cart->couponsRepository->addCoupon([
            'name' => '10PERCENT',
            'coupon_type' => \juniorE\ShoppingCart\Enums\CouponTypes::PERCENT,
            'discount_percent' => 0.10,
        ]);

        $cart->addCoupon($coupon10PERCENT);

        $this->assertEquals(5.47, $cart->getCart()->discount);
    }

    /**
     * @test
     */
    public function doesnt_calculate_tax_on_tax_exempt_items()
    {
        $this->assertNotEmpty(config('shoppingcart.tax_exempt_types'));

        $gourmetMeat = [
            'plu' => 5,
            'price' => 49.72,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::PLU,
            'tax_percent' => 0.06,
        ];

        $gourmetWarranty = [
            'plu' => 6,
            'price' => 15,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::WARRANTY,
            'tax_percent' => 0.5, // arbitrary percentage, which should get ignored because it's a warranty
        ];

        $gourmetRent = [
            'plu' => 6,
            'price' => 4.95,
            'quantity' => 1,
            'type' => \juniorE\ShoppingCart\Enums\ItemTypes::RENT,
            'tax_percent' => 0.21,
        ];

        $cart = cart();
        $cart->addProducts($gourmetMeat, $gourmetWarranty, $gourmetRent);

        $this->assertEquals(3.6734, round($cart->getCart()->tax_total, 4));
    }
}
