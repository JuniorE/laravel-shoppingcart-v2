<?php


use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\BaseCart;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Models\Cart;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;
use juniorE\ShoppingCart\Models\CartShippingRate;
use juniorE\ShoppingCart\Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function a_cart_can_be_saved()
    {
        $this->assertCount(0, Cart::all());

        Cart::create([
            "identifier" => BaseCart::generateIdentifier(),
        ]);

        $this->assertCount(1, Cart::all());
    }

    /**
     * @test
     */
    public function a_cart_coupon_can_be_saved()
    {
        $this->assertCount(0, CartCoupon::all());

        CartCoupon::create([
            "name" => "WELCOME10",
            "coupon_type" => CouponTypes::PERCENT,
            "discount_percent" => 10.0000
        ]);

        $this->assertCount(1, CartCoupon::all());
    }

    /**
     * @test
     */
    public function a_cart_item_can_be_saved()
    {
        $this->assertCount(0, CartItem::all());


        $cart = Cart::create([
            "identifier" => BaseCart::generateIdentifier(),
        ]);

        CartItem::create([
            "cart_id" => $cart->id,
            "plu" => 1
        ]);

        $this->assertCount(1, CartItem::all());
    }

    /**
     * @test
     */
    public function a_cart_shipping_rate_can_be_saved()
    {
        $this->assertCount(0, CartShippingRate::all());

        CartShippingRate::create([
            "method" => "delivery",
            "price" => 15.0000,
            "minimum_cart_price" => 0.0000
        ]);

        $this->assertCount(1, CartShippingRate::all());
    }
}
