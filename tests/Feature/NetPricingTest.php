<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\Cart as CartModel;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NetPricingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_new_cart_includes_tax_by_default()
    {
        $this->assertTrue(cart()->pricesIncludeTax());
        $this->assertTrue(CartModel::pricesIncludeTaxFor(cart()->id));
    }

    #[Test]
    public function a_cart_row_written_without_the_flag_reads_as_gross()
    {
        // As a row from before the migration: the column default applies.
        $id = DB::table(CartModel::tableName())->insertGetId([
            'identifier' => 'written-before-net-mode',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(CartModel::pricesIncludeTaxFor($id));
    }

    #[Test]
    public function an_unknown_cart_reads_as_gross()
    {
        $this->assertTrue(CartModel::pricesIncludeTaxFor(999999));
    }

    #[Test]
    public function an_empty_cart_can_switch_to_net_prices()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);

        $this->assertFalse($cart->pricesIncludeTax());
        $this->assertFalse(CartModel::pricesIncludeTaxFor($cart->id));
    }

    #[Test]
    public function a_cart_with_lines_cannot_switch_mode()
    {
        $cart = cart();
        $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 1, 'tax_percent' => 0.06]);

        $this->expectException(LogicException::class);

        $cart->setPricesIncludeTax(false);
    }

    #[Test]
    public function setting_the_current_mode_on_a_cart_with_lines_changes_nothing()
    {
        $cart = cart();
        $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 1, 'tax_percent' => 0.06]);
        $cart->getCart()->update(['shipping_method' => 'delivery', 'coupon_code' => 'TEST10']);

        $cart->setPricesIncludeTax(true);

        $this->assertTrue($cart->pricesIncludeTax());
        $row = $cart->getCart()->fresh();
        $this->assertSame('delivery', $row->shipping_method);
        $this->assertSame('TEST10', $row->coupon_code);
    }

    #[Test]
    public function switching_an_empty_cart_drops_its_coupon_and_shipping_method()
    {
        $cart = cart();
        $cart->getCart()->update(['shipping_method' => 'delivery', 'coupon_code' => 'TEST10']);

        $cart->setPricesIncludeTax(false);

        $row = $cart->getCart()->fresh();
        $this->assertNull($row->shipping_method);
        $this->assertNull($row->coupon_code);
    }

    #[Test]
    public function switching_an_empty_cart_recomputes_its_totals()
    {
        // As a basket emptied line by line: the totals still carry the
        // delivery cost and the coupon discount it was priced with.
        $cart = cart();
        $cart->getCart()->update([
            'shipping_method' => 'delivery',
            'coupon_code' => 'TEST10',
            'grand_total' => 12.5,
            'discount' => 2.5,
        ]);

        $cart->setPricesIncludeTax(false);

        $row = $cart->getCart()->fresh();
        $this->assertEquals(0, $row->grand_total);
        $this->assertEquals(0, $row->discount);
    }

    #[Test]
    public function the_tax_code_is_stored_on_the_line()
    {
        $item = cart()->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 1, 'tax_percent' => 0.06, 'tax_code' => 2]);

        $this->assertSame(2, (int) $item->fresh()->tax_code);
    }

    #[Test]
    public function a_net_line_adds_its_tax_on_top()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);

        $item = $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 3, 'tax_percent' => 0.06]);

        $this->assertEqualsWithDelta(30.0, (float) $item->fresh()->total, 0.00001);
        $this->assertEqualsWithDelta(1.8, (float) $item->fresh()->tax_amount, 0.00001);
    }

    #[Test]
    public function a_gross_line_keeps_its_tax_inside()
    {
        $item = cart()->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 3, 'tax_percent' => 0.06]);

        $this->assertEqualsWithDelta(30 - 30 / 1.06, (float) $item->fresh()->tax_amount, 0.0001);
    }

    #[Test]
    public function a_quantity_change_recomputes_the_net_tax()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);
        $item = $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 3, 'tax_percent' => 0.06]);

        $cart->itemsRepository->setQuantity($item, 5);

        $this->assertEqualsWithDelta(3.0, (float) $item->fresh()->tax_amount, 0.00001);
    }

    #[Test]
    public function a_price_change_recomputes_the_net_tax()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);
        $item = $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 2, 'tax_percent' => 0.21]);

        $cart->itemsRepository->setPrice($item, 12.5);

        $this->assertEqualsWithDelta(5.25, (float) $item->fresh()->tax_amount, 0.00001);
    }

    #[Test]
    public function a_tax_rate_change_recomputes_the_net_tax()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);
        $item = $cart->addProduct(['plu' => 5, 'price' => 10, 'quantity' => 2, 'tax_percent' => 0.06]);

        $cart->itemsRepository->setTaxPercent($item, 0.12);

        $this->assertEqualsWithDelta(2.4, (float) $item->fresh()->tax_amount, 0.00001);
    }

    #[Test]
    public function a_deposit_in_a_net_cart_carries_no_tax()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);

        $item = $cart->addProduct([
            'plu' => 6, 'price' => 15, 'quantity' => 2, 'tax_percent' => 0.21, 'type' => ItemTypes::WARRANTY,
        ]);

        $this->assertEqualsWithDelta(0.0, (float) $item->fresh()->tax_amount, 0.00001);
    }

    #[Test]
    public function a_net_cart_totals_its_tax_on_top()
    {
        $cart = cart();
        $cart->setPricesIncludeTax(false);
        $cart->addProduct(['plu' => 5, 'price' => 100, 'quantity' => 1, 'tax_percent' => 0.06]);
        $cart->addProduct(['plu' => 7, 'price' => 20, 'quantity' => 1, 'tax_percent' => 0.21]);
        $cart->addProduct(['plu' => 8, 'price' => 3, 'quantity' => 2, 'tax_percent' => 0.21, 'type' => ItemTypes::WARRANTY]);

        $row = $cart->getCart()->fresh();

        $this->assertEqualsWithDelta(126.0, (float) $row->sub_total, 0.0001);
        $this->assertEqualsWithDelta(10.2, (float) $row->tax_total, 0.0001);
        $this->assertEqualsWithDelta(136.2, (float) $row->grand_total, 0.0001);
    }

    #[Test]
    public function carts_of_different_modes_do_not_merge()
    {
        $guest = cart();
        $guest->addProduct(['plu' => 5, 'price' => 10.6, 'quantity' => 1, 'tax_percent' => 0.06]);

        $customer = cart('a-customer-identifier');
        $customer->setPricesIncludeTax(false);

        $this->expectException(LogicException::class);

        $customer->merge($guest);
    }
}
