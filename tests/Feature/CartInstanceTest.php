<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\Cart as CartModel;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A cart's setters change the cart they are called on. They used to go
 * through the cart() helper, which names the session's cart: the last one
 * constructed, not necessarily this one.
 */
class CartInstanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_shipping_method_is_set_on_the_cart_it_is_called_on()
    {
        cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 5,
            'minimum_cart_price' => 0,
        ]);
        $a = cart('a');
        $a->addProduct($this->line(1));
        // Points the session at b: totalling a's new line named a again.
        cart('b');

        $a->setShippingMethod('truck');

        $this->assertSame('truck', $this->row('a')->shipping_method);
        $this->assertNull($this->row('b')->shipping_method);
        $this->assertEqualsWithDelta(15.0, (float) $this->row('a')->grand_total, 0.0001);
    }

    #[Test]
    public function a_coupon_is_added_to_and_removed_from_the_cart_it_is_called_on()
    {
        $coupon = cart()->couponsRepository->addCoupon([
            'name' => 'FIVE',
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => 5,
        ]);
        $a = cart('a');
        $a->addProduct($this->line(1));
        $b = cart('b');
        $b->addProduct($this->line(2));

        cart('b');
        $a->addCoupon($coupon);

        $this->assertSame('FIVE', $this->row('a')->coupon_code);
        $this->assertNull($this->row('b')->coupon_code);
        $this->assertEqualsWithDelta(5.0, (float) $this->row('a')->grand_total, 0.0001);
        $this->assertEqualsWithDelta(10.0, (float) $this->row('b')->grand_total, 0.0001);

        $b->addCoupon($coupon);
        cart('b');
        $a->removeCoupon();

        $this->assertNull($this->row('a')->coupon_code);
        $this->assertSame('FIVE', $this->row('b')->coupon_code);
        $this->assertEqualsWithDelta(10.0, (float) $this->row('a')->grand_total, 0.0001);
        $this->assertEqualsWithDelta(5.0, (float) $this->row('b')->grand_total, 0.0001);
    }

    #[Test]
    public function checkout_method_and_additional_data_are_set_on_the_cart_they_are_called_on()
    {
        $a = cart('a');
        cart('b');

        $a->setCheckoutMethod('mollie');
        $a->setAdditionalData(['note' => 'a']);

        $this->assertSame('mollie', $this->row('a')->checkout_method);
        $this->assertSame(0, (int) $this->row('a')->conversion_time);
        $this->assertSame(['note' => 'a'], $this->row('a')->additional);
        $this->assertNull($this->row('b')->checkout_method);
        $this->assertNull($this->row('b')->conversion_time);
        $this->assertNull($this->row('b')->additional);
    }

    #[Test]
    public function destroy_clears_the_cart_it_is_called_on()
    {
        $a = cart('a');
        cart('b');

        $a->destroy();

        $this->assertNull(CartModel::where('identifier', 'a')->first());
        $this->assertNotNull(CartModel::where('identifier', 'b')->first());
    }

    #[Test]
    public function get_items_without_an_id_reads_the_cart_it_is_called_on()
    {
        $a = cart('a');
        $a->addProduct($this->line(1));
        cart('b');

        $this->assertCount(1, $a->getItems());
    }

    private function row(string $identifier): CartModel
    {
        return CartModel::where('identifier', $identifier)->firstOrFail();
    }

    private function line(int $plu): array
    {
        return ['plu' => $plu, 'type' => ItemTypes::PLU, 'price' => 10, 'quantity' => 1];
    }
}
