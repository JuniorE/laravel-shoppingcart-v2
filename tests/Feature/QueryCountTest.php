<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\Cart as CartModel;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every line a basket gets used to cost more queries than it needed: a
 * line's own amounts were worked out twice per save (creating and saving,
 * or updating and saving), each reading the cart's VAT mode again. On a shop
 * whose database is a network hop away every one of them is felt. (A new
 * line's totals are still worked out twice, by createCartItem() and by its
 * created event: the first pass reads the delivery tier and the coupon's cap
 * off the totals stored before it, and the second puts them right.)
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_carts_vat_mode_is_read_once_for_several_lines()
    {
        $cart = cart();

        $reads = $this->counting('select "prices_include_tax"', function () use ($cart) {
            foreach ([1, 2, 3] as $plu) {
                $cart->addProduct($this->line($plu), true);
            }
        });

        $this->assertLessThanOrEqual(1, $reads);
    }

    #[Test]
    public function a_cart_that_switches_its_vat_mode_is_read_afresh()
    {
        $cart = cart();
        $this->assertTrue(CartModel::pricesIncludeTaxFor($cart->id));

        $cart->setPricesIncludeTax(false);
        $line = $cart->addProduct($this->line(1), true);

        $this->assertFalse(CartModel::pricesIncludeTaxFor($cart->id));
        // Net: the VAT comes on top of the line's 10.
        $this->assertEqualsWithDelta(0.6, (float) $line->tax_amount, 0.0001);
    }

    #[Test]
    public function a_line_still_has_its_amounts_and_the_cart_its_totals()
    {
        $cart = cart();
        $line = $cart->addProduct($this->line(1), true);
        $cart->itemsRepository->setQuantity($line, 3);

        $line->refresh();
        $this->assertEqualsWithDelta(30.0, (float) $line->total, 0.0001);
        $this->assertNotEmpty($line->row_hash);
        $this->assertEqualsWithDelta(30.0, (float) $cart->getCart()->grand_total, 0.0001);
    }

    #[Test]
    public function the_shipping_rate_and_delivery_cost_read_the_cart_once()
    {
        $cart = cart();
        $cart->shippingRateRepository->addShippingRate(['method' => 'truck', 'price' => 5, 'minimum_cart_price' => 0]);
        $cart->addProduct($this->line(1), true);
        $cart->setShippingMethod('truck');

        $this->assertSame(1, $this->counting('select * from "carts"', fn () => $cart->getShippingRate()));
        $this->assertSame(1, $this->counting('select * from "carts"', fn () => $cart->getDeliveryCost()));
        $this->assertEquals(5, $cart->getDeliveryCost());
    }

    #[Test]
    public function a_coupons_discount_reads_the_cart_once()
    {
        $cart = cart();
        $cart->addProduct($this->line(1), true);
        $coupon = $cart->couponsRepository->addCoupon([
            'name' => 'FIVE',
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => 5,
        ]);

        $discount = null;
        $reads = $this->counting('select * from "carts"', function () use ($coupon, $cart, &$discount) {
            $discount = $coupon->discount(10, 0, 0.0, $cart);
        });

        $this->assertSame(1, $reads);
        $this->assertEquals(5, $discount);
    }

    private function line(int $plu): array
    {
        return ['plu' => $plu, 'type' => ItemTypes::PLU, 'price' => 10, 'quantity' => 1, 'tax_percent' => 0.06];
    }

    private function counting(string $prefix, Closure $work): int
    {
        $count = 0;
        DB::listen(function ($query) use ($prefix, &$count) {
            if (str_starts_with($query->sql, $prefix)) {
                $count++;
            }
        });

        $work();

        return $count;
    }
}
