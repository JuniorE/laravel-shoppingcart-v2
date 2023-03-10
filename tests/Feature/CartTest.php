<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Cart;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models as Models;
use juniorE\ShoppingCart\Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up tests
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function can_get_cart()
    {
        $this->assertInstanceOf(Cart::class, cart());
    }

    /**
     * @test
     */
    public function can_add_product_to_cart()
    {
        $this->assertCount(0, cart()->items());
        cart()->addProduct([
            'plu' => 5,
        ]);
        $this->assertCount(1, cart()->items());
        $this->assertCount(1, app(CartDatabase::class)
            ->getCartItems(cart()->id));
    }

    /**
     * @test
     */
    public function can_remove_product_from_cart()
    {
        $this->assertCount(0, cart()->items());
        $product = cart()->addProduct([
            'plu' => 5,
        ]);
        $this->assertCount(1, cart()->items());
        cart()->removeItem($product);
        $this->assertCount(0, cart()->items());
    }

    /**
     * @test
     */
    public function can_get_product()
    {
        $product = cart()->addProduct([
            'plu' => 5,
        ]);

        $dbProduct = app(CartDatabase::class)
            ->getCartItem($product->id);

        $this->assertEquals($product->plu, $dbProduct->plu);
        $this->assertEquals($product->id, $dbProduct->id);
        $this->assertEquals($product->cart_id, $dbProduct->cart_id);
    }

    /**
     * @test
     */
    public function can_set_checkout_method()
    {
        $this->assertEquals(null, cart()->getCart()->checkout_method);
        cart()->setCheckoutMethod('invoice');
        $this->assertEquals('invoice', cart()->getCart()->checkout_method);
    }

    /**
     * @test
     */
    public function can_set_conversion_time()
    {
        cart()->getCart()->update(['created_at' => now()->addMinutes(-15)]);
        $this->assertEquals(null, cart()->getCart()->conversion_time);
        cart()->setCheckoutMethod('invoice');
        $this->assertEquals(15, cart()->getCart()->conversion_time);
    }

    /**
     * @test
     */
    public function can_destroy_cart()
    {
        cart()->addProduct([
            'plu' => 5,
        ]);

        $this->assertCount(1, cart()->items());

        $id = cart()->id;
        $identifier = cart()->identifier;

        cart()->destroy();

        $this->assertCount(0, cart()->items());
        $this->assertNotSame($id, cart()->id);
        $this->assertNotSame($identifier, cart()->identifier);
    }

    /**
     * @test
     */
    public function can_cleanup_idle_carts()
    {
        DB::table('carts')->insert([
            ['identifier' => 1, 'updated_at' => now()->subDays(1)],
            ['identifier' => 2, 'updated_at' => now()->subDays(10)],
            ['identifier' => 3, 'updated_at' => now()->subDays(11)],
            ['identifier' => 4, 'updated_at' => now()->subDays(13)],
            ['identifier' => 5, 'updated_at' => now()->subDays(14)],
            ['identifier' => 6, 'updated_at' => now()->subDays(17)],
            ['identifier' => 7, 'updated_at' => now()->subDays(24)],
            ['identifier' => 8, 'updated_at' => now()->subDays(30)],
            ['identifier' => 9, 'updated_at' => now()->subDays(31)],
            ['identifier' => 10, 'updated_at' => now()->subDays(32)],
            ['identifier' => 11, 'updated_at' => now()->subDays(34)],
            ['identifier' => 12, 'updated_at' => now()->subDays(42)],
            ['identifier' => 13, 'updated_at' => now()->subDays(42)],
            ['identifier' => 14, 'updated_at' => now()->subDays(42)],
            ['identifier' => 15, 'updated_at' => now()->subDays(42)],
        ]);

        $this->assertCount(15, Models\Cart::all());

        $this->assertFalse(Models\Cart::all()->every(function (Models\Cart $cart) {
            return now()->diffInDays($cart->updated_at) < 30;
        }));

        Models\Cart::clean();

        $this->assertCount(7, Models\Cart::all());

        $this->assertTrue(Models\Cart::all()->every(function (Models\Cart $cart) {
            return now()->diffInDays($cart->updated_at) < 30;
        }));
    }

    /**
     * @test
     */
    public function can_set_additional_data()
    {
        cart()->addProduct([
            'plu' => 5,
        ]);

        cart()->setAdditionalData([
            'comment' => 'lorem ipsum',
        ]);

        cart()->setAdditionalData([
            'key' => 'value',
            'key2' => 'value2',
        ]);

        $cart = cart()->getCart();

        $this->assertEquals('lorem ipsum', $cart->additional['comment']);
        $this->assertEquals('value', $cart->additional['key']);
        $this->assertEquals('value2', $cart->additional['key2']);

        cart()->setAdditionalData([
            'comment' => 'Peanut Allergy',
        ]);

        $this->assertEquals('Peanut Allergy', cart()->getCart()->additional['comment']);
    }

    /**
     * @test
     */
    public function can_update_identifier()
    {
        $customerId = 10;
        $identifier = md5($customerId);

        cart()->addProduct([
            'plu' => 5,
        ]);

        cart()->updateIdentifier($identifier);

        $this->assertEquals($identifier, cart()->identifier);
        $this->assertEquals($identifier, session('cart_identifier'));
    }

    /**
     * @test
     */
    public function can_add_coupon_to_cart_items()
    {
        $coupon = cart()->couponsRepository->addCoupon([
            'name' => 'WELCOME10',
            'discount_percent' => 0.10,
            'ends_other_coupons' => false,
        ]);

        $product = cart()->addProduct([
            'plu' => 5,
            'price' => 4.99,
            'quantity' => 1,
        ]);

        $product2 = cart()->addProduct([
            'plu' => 6,
            'price' => 4.99,
            'quantity' => 1,
        ]);

        cart()->itemsRepository->setCouponCode($product, $coupon->name);
        cart()->itemsRepository->setCouponCode($product2, $coupon->name);

        $this->assertEquals($coupon->name, $product->coupon_code);
        $this->assertEquals($coupon->name, $product2->coupon_code);
    }

    /**
     * @test
     */
    public function can_set_shipping_method()
    {
        $cart = cart();
        $cart->addProduct([
            'plu' => 5,
        ]);

        $this->assertNull(cart()->getCart()->shipping_method);

        $truck = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 20,
            'minimum_cart_price' => 0,
        ]);

        $cart->setShippingMethod('truck');

        $this->assertEquals('truck', cart()->getCart()->shipping_method);
    }

    /**
     * @test
     */
    public function get_total_price()
    {
        $this->assertEquals(0, cart()->getCart()->grand_total);

        cart()->addProduct([
            'plu' => 5,
            'price' => 4.99,
            'quantity' => 3,
            'tax_percent' => 0.06,
        ]);

        $this->assertEquals(14.97, round(cart()->getCart()->grand_total, 2));
        $this->assertEquals(14.12, round(cart()->getCart()->sub_total, 2));
        $this->assertEquals(0.85, round(cart()->getCart()->tax_total, 2));
    }

    /**
     * @test
     */
    public function can_get_best_shipping_rate_for_cart()
    {
        $truck = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 20,
            'minimum_cart_price' => 0,
        ]);
        $truck2 = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 10,
            'minimum_cart_price' => 50,
        ]);
        $truck3 = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 0,
            'minimum_cart_price' => 100,
        ]);

        cart()->shippingRateRepository->addShippingRate([
            'method' => 'plane',
            'price' => 25,
            'minimum_cart_price' => 110,
        ]);

        cart()->setShippingMethod('truck');

        $this->assertCount(3, cart()->shippingRateRepository->shippingRatesForMethod('truck'));

        cart()->addProduct([
            'plu' => 5,
            'price' => 5,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck->price, cart()->getShippingRate()->price);

        cart()->addProduct([
            'plu' => 2,
            'price' => 45,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck2->price, cart()->getShippingRate()->price);

        cart()->addProduct([
            'plu' => 3,
            'price' => 15,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck2->price, cart()->getShippingRate()->price);

        cart()->addProduct([
            'plu' => 1,
            'price' => 34,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck2->price, cart()->getShippingRate()->price);

        cart()->addProduct([
            'plu' => 6,
            'price' => 2,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck3->price, cart()->getShippingRate()->price);

        cart()->addProduct([
            'plu' => 6,
            'price' => 40,
            'quantity' => 1,
        ]);

        $this->assertEquals($truck3->price, cart()->getShippingRate()->price);
    }

    /**
     * @test
     */
    public function can_add_coupon_and_is_coupon_applied_correctly()
    {
        $frappucino = [
            'plu' => 5,
            'price' => 49.99,
            'quantity' => 1,
        ];

        $machiato = [
            'plu' => 7,
            'price' => 12.99,
            'quantity' => 1,
        ];

        $espresso = [
            'plu' => 3,
            'price' => 4.95,
            'quantity' => 1,
        ];

        $cookie = [
            'plu' => 9,
            'price' => 0.95,
            'quantity' => 1,
        ];

        // The Mocha Cookie Crumble Frappcino Starbucks didn't give me.
        cart()->addProduct($frappucino);

        $this->assertEquals(49.99, cart()->getCart()->grand_total);

        $welcome125 = cart()->couponsRepository->addCoupon([
            'name' => 'WELCOME125',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_percent' => 0.125,
        ]);

        $freeCookies = cart()->couponsRepository->addCoupon([
            'name' => 'FREECOOKIE',
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => 0.95,
            'conditional' => true,
            'conditions' => collect([
                'cart_contains_plus' => [
                    [$frappucino['plu']],
                    [$machiato['plu'], $espresso['plu']],
                ],
            ]),
        ]);

        cart()->addCoupon($welcome125);
        $this->assertCount(2, Models\CartCoupon::all());
        $this->assertEquals($welcome125->name, cart()->getCart()->coupon_code);

        $this->assertEquals(43.74, round(cart()->getCart()->grand_total, 2));

        cart()->destroy();

        cart()->addProduct($frappucino);

        $this->assertEquals($frappucino['price'], cart()->getCart()->grand_total);

        cart()->addProduct($cookie);

        $this->assertEquals(round($frappucino['price'] + $cookie['price'], 4), cart()->getCart()->grand_total);

        cart()->addCoupon($freeCookies);

        $this->assertEquals($frappucino['price'], cart()->getCart()->grand_total);

        cart()->destroy();

        cart()->addProduct($machiato);

        $this->assertEquals($machiato['price'], cart()->getCart()->grand_total);

        cart()->addProduct($cookie);

        $this->assertEquals($machiato['price'] + $cookie['price'], cart()->getCart()->grand_total);

        cart()->addCoupon($freeCookies);

        $this->assertEquals($machiato['price'] + $cookie['price'], cart()->getCart()->grand_total);

        cart()->addProduct($espresso);

        $this->assertEquals($espresso['price'] + $machiato['price'], cart()->getCart()->grand_total);
    }

    /**
     * @test
     */
    public function can_add_coupon_to_cart_item_and_is_coupon_calculated_correctly()
    {
        $cookie = [
            'plu' => 9,
            'price' => 0.95,
            'quantity' => 1,
        ];

        $buyTwoGetOneFree = cart()->couponsRepository->addCoupon([
            'name' => '342',
            'coupon_type' => CouponTypes::STEP,
            'discount_quantity' => 1,
            'discount_step' => 2,
            'conditional' => true,
            'conditions' => [
                'applies_to' => [$cookie['plu']],
            ],
            'ends_other_coupons' => false,
        ]);

        cart()->addProduct($cookie);

        $this->assertEquals(0.95, cart()->getCart()->grand_total);

        cart()->destroy();

        cart()->addProduct(
            collect($cookie)
                ->put('quantity', 2)
                ->toArray()
        );

        $this->assertEquals(0, cart()->getCart()->discount);
        $this->assertEquals(1.90, cart()->getCart()->grand_total);

        cart()->destroy();

        $item = cart()->addProduct(
            collect($cookie)
                ->put('quantity', 3)
                ->toArray()
        );

        $this->assertEquals(2.85, cart()->getCart()->grand_total);

        cart()->itemsRepository->setCouponCode($item, $buyTwoGetOneFree->name);

        $this->assertEquals(0.95, cart()->getCart()->discount);
        $this->assertEquals(1.90, cart()->getCart()->grand_total);

        cart()->destroy();

        $product = cart()->addProduct([
            'plu' => 7,
            'price' => 24.95,
            'quantity' => 2,
        ]);

        $this->assertEqualsWithDelta(49.90, (float) cart()->getCart()->grand_total, 0.005);

        $welcome10 = cart()->couponsRepository->addCoupon([
            'name' => 'WELCOME10',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_percent' => 0.1,
            'ends_other_coupons' => false,
        ]);

        cart()->itemsRepository->setCouponCode($product, $welcome10->name);

        $this->assertEqualsWithDelta(44.91, (float) cart()->getCart()->grand_total, 0.005);

        cart()->destroy();

        $discount10 = cart()->couponsRepository->addCoupon([
            'name' => 'DISCOUNT10',
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => 10,
            'ends_other_coupons' => false,
        ]);

        $product = cart()->addProduct([
            'plu' => 7,
            'price' => 24.95,
            'quantity' => 2,
        ]);

        $this->assertEqualsWithDelta(49.90, (float) cart()->getCart()->grand_total, 0.005);

        cart()->itemsRepository->setCouponCode($product, $discount10->name);

        $this->assertEqualsWithDelta(39.90, (float) cart()->getCart()->grand_total, 0.005);
    }

    /**
     * @test
     */
    public function does_throw_null_pointer_exception_when_cart_gets_cleaned()
    {
        cart()->addProduct([
            'plu' => 5,
        ]);

        cart()->getCart()->update([
            'updated_at' => now()->subDays(40),
        ]);

        $id = cart()->identifier;

        Models\Cart::clean();

        cart()->addProduct([
            'plu' => 5,
        ]);
        $this->assertCount(1, cart()->items());
        $this->assertNotEquals($id, cart()->identifier);
    }

    /**
     * @test
     */
    public function can_restore_cart()
    {
        $cart = new Cart();
        $cart->addProduct([
            'plu' => 4,
        ]);

        $this->assertCount(1, Models\Cart::all());

        $this->assertCount(1, cart()->items());
        $this->assertEquals($cart->identifier, cart()->identifier);

        $oldIdentifier = cart()->identifier;

        session()->forget('cart_identifier');

        $this->assertNotEquals($cart->identifier, cart()->identifier);

        $this->assertCount(0, cart()->items());

        $this->assertNotNull(Models\Cart::where('identifier', '=', $cart->identifier)->first());

        $this->assertEquals($oldIdentifier, cart($oldIdentifier)->identifier);

        $this->assertEquals(4, cart()->items()->first()->plu);
    }

    public function can_get_cart_item_and_items_through_cart_helper()
    {
        $cart = cart();

        $this->assertNull($cart->getItem(1));
        $this->assertCount(0, $cart->getItems($cart->id));
        $this->assertCount(0, $cart->getItems());

        $item = $cart->addProduct([
            'plu' => 5,
        ]);

        $this->assertCount(1, $cart->getItems($cart->id));
        $this->assertCount(1, $cart->getItems());

        $this->assertNotNull($cart->getItem($item->id));
        $this->assertEquals(5, $item->plu);
    }

    /**
     * @test
     */
    public function auto_merge_products_if_possible()
    {
        $product = [
            'plu' => 6,
            'quantity' => 2,
            'additional' => [
                'comment' => 'abc',
            ],
        ];

        $product2 = [
            'plu' => 6,
            'quantity' => 2,
            'additional' => [
                'comment' => 'def',
            ],
        ];

        $product3 = [
            'plu' => 6,
            'quantity' => 6,
            'additional' => [
                'comment' => 'def',
            ],
        ];

        $product4 = [
            'plu' => 6,
            'type' => 1,
        ];

        $product5 = [
            'plu' => 7,
            'type' => 1,
        ];

        $product6 = [
            'plu' => 6,
            'quantity' => 1,
            'additional' => [
                'comment' => 'abc',
            ],
        ];

        $cart = cart();
        $cart->addProduct($product);

        $this->assertCount(1, $cart->items());

        $cart->addProduct($product2);

        $this->assertCount(2, $cart->items());

        $cartProduct = $cart->addProduct($product3);

        $this->assertCount(2, $cart->items());
        $this->assertEquals(8, $cartProduct->quantity);

        $cartProduct = $cart->addProduct($product2);

        $this->assertCount(2, $cart->items());
        $this->assertEquals(10, $cartProduct->quantity);

        $cart->addProduct($product4);

        $this->assertCount(3, $cart->items());

        $cart->addProduct($product5);

        $this->assertCount(4, $cart->items());

        $productAbc = $cart->addProduct($product6);

        $this->assertCount(4, $cart->items());
        $this->assertEquals(3, $productAbc->quantity);

        $cart->addProduct($product6, true);
        $this->assertCount(5, $cart->items());

        $product7 = [
            'plu' => 5,
            'quantity' => 2,
            'price' => 4.95,
            'additional' => [
                'comment' => 'abc',
            ],
        ];

        cart()->destroy();

        cart()->addProduct($product7);
        $this->assertCount(1, cart()->items());
        cart()->addProduct($product7);
        $this->assertCount(1, cart()->items());
        cart()->addProduct($product7);
        $this->assertCount(1, cart()->items());
        $item = cart()->addProduct($product7);
        $this->assertCount(1, cart()->items());
        $this->assertEquals(8, $item->quantity);
    }

    /**
     * @test
     */
    public function get_cart_items_as_a_tree_structure()
    {
        $parent = cart()->addProduct([
            'plu' => 5,
            'price' => 2,
            'quantity' => 6,
        ]);

        cart()->addProduct([
            'plu' => 6,
            'quantity' => 1,
            'parent_id' => $parent->id,
        ]);

        cart()->addProduct([
            'plu' => 7,
            'quantity' => 1,
            'parent_id' => $parent->id,
        ]);

        cart()->addProduct([
            'plu' => 8,
            'quantity' => 1,
            'parent_id' => $parent->id,
        ]);

        $tree = cart()->itemsTree();
        $this->assertCount(1, $tree);
        $this->assertEquals(5, $tree->first()->plu);
        $this->assertCount(3, $tree->first()->subproducts);
        $this->assertEquals(6, $tree->first()->subproducts->first()->plu);
    }

    /**
     * @test
     */
    public function does_price_update_after_deleting_a_cart_item()
    {
        $cart = cart();
        $product = $cart->addProduct([
            'plu' => 5,
            'price' => 9.95,
            'quantity' => 2,
        ]);

        $this->assertEqualsWithDelta(19.90, (float) $cart->getCart()->grand_total, 0.005);

        $cart->removeItem($product);

        $this->assertEquals(0, (float) $cart->getCart()->grand_total);
    }

    /**
     * @test
     */
    public function does_updating_cart_item_cause_price_update_in_cart()
    {
        $cart = cart();
        $item = $cart->addProduct([
            'plu' => 5,
            'price' => 9.95,
            'quantity' => 1,
        ]);

        $this->assertEqualsWithDelta(9.95, $cart->getCart()->grand_total, 0.005);

        $cart->itemsRepository->setQuantity($item, 2);

        $this->assertEqualsWithDelta(19.90, $cart->getCart()->grand_total, 0.005);

        $cart->itemsRepository->setQuantity($item, 5);

        $this->assertEqualsWithDelta(49.75, $cart->getCart()->grand_total, 0.005);
    }

    /**
     * @test
     */
    public function can_get_all_applied_coupons()
    {
        $cart = cart();

        $coupon = $cart->couponsRepository->addCoupon([
            'name' => 'GOEDEBUREN',
            'coupon_type' => CouponTypes::AMOUNT,
            'discount_amount' => 5,
            'ends_other_coupons' => false,
        ]);

        $cartCoupon = $cart->couponsRepository->addCoupon([
            'name' => 'SHIP_IT_TO_ME',
            'free_shipping' => true,
        ]);

        $coupon2 = $cart->couponsRepository->addCoupon([
            'name' => 'DISCOUNT_60%',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_amount' => .6,
            'ends_other_coupons' => true,
        ]);

        $item = $cart->addProduct([
            'plu' => 5,
            'price' => 10,
            'quantity' => 1,
        ]);

        $item2 = $cart->addProduct([
            'plu' => 6,
            'price' => 15,
            'quantity' => 1,
        ]);

        $this->assertCount(0, $cart->getAllCouponsOnCart());

        $cart->addCoupon($cartCoupon);

        $this->assertCount(1, $cart->getAllCouponsOnCart());

        $cart->itemsRepository->setCouponCode($item, $coupon->name);

        $this->assertCount(2, $cart->getAllCouponsOnCart());

        $cart->itemsRepository->setCouponCode($item2, $coupon2->name);

        $this->assertCount(2, $cart->getAllCouponsOnCart());

        $this->assertCount(0, Models\CartCoupon::where('cart_id', $cart->id)->where('coupon_code', 'DISCOUNT_60%')->get());
    }

    /**
     * @test
     */
    public function can_empty_cart()
    {
        $identifier = md5('lmao');
        $cart = cart();
        $cart->updateIdentifier($identifier);

        $this->assertEquals($identifier, $cart->identifier);

        $cart->addProduct([
            'plu' => 5,
            'price' => 4.99,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);
        $cart->addProduct([
            'plu' => 6,
            'price' => 9.99,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);
        $cart->addProduct([
            'plu' => 7,
            'price' => 2.49,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);

        $this->assertCount(3, $cart->items());

        $cart->empty();

        $this->assertCount(0, $cart->items());

        $this->assertEquals($identifier, $cart->identifier);

        $this->assertEquals(0, $cart->getCart()->grand_total);
        $this->assertEquals(0, $cart->getCart()->tax_total);
        $this->assertEquals(0, $cart->getCart()->discount);
    }

    /**
     * @test
     */
    public function can_empty_cart_with_coupon_code()
    {
        $cart = cart();

        $cart->addProduct([
            'plu' => 7,
            'price' => 2.49,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);

        $coupon = $cart->couponsRepository->addCoupon([
            'name' => '10PROCENT',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_percent' => 0.10,
        ]);

        $cart->addCoupon($coupon);

        $this->assertCount(1, $cart->items());
        $this->assertEquals(0.25, $cart->getCart()->discount);

        $cart->empty();

        $this->assertCount(0, $cart->items());
        $this->assertEquals(0, $cart->getCart()->grand_total);
        $this->assertEquals(0, $cart->getCart()->tax_total);
        $this->assertEquals(0, $cart->getCart()->discount);
    }

    /**
     * @test
     */
    public function can_get_subproducts_of_type()
    {
        $cart = cart();
        $parent = $cart->addProduct([
            'plu' => 1,
            'type' => ItemTypes::PLU,
        ]);
        $cart->addProduct([
            'plu' => 2,
            'parent_id' => $parent->id,
            'type' => ItemTypes::PLU,
        ]);
        $cart->addProduct([
            'plu' => 3,
            'parent_id' => $parent->id,
            'type' => ItemTypes::MENU,
        ]);
        $cart->addProduct([
            'plu' => 4,
            'parent_id' => $parent->id,
            'type' => ItemTypes::WARRANTY,
        ]);
        $cart->addProduct([
            'plu' => 5,
            'parent_id' => $parent->id,
            'type' => ItemTypes::WARRANTY,
        ]);
        $cart->addProduct([
            'plu' => 6,
            'parent_id' => $parent->id,
            'type' => ItemTypes::WARRANTY,
        ]);
        $cart->addProduct([
            'plu' => 7,
            'parent_id' => $parent->id,
            'type' => ItemTypes::RENT,
        ]);

        $this->assertCount(1, $parent->getSubproductsOfType(ItemTypes::PLU));
        $this->assertEquals(2, $parent->getSubproductsOfType(ItemTypes::PLU)->first()->plu);

        $this->assertCount(1, $parent->getSubproductsOfType(ItemTypes::MENU));
        $this->assertEquals(3, $parent->getSubproductsOfType(ItemTypes::MENU)->first()->plu);

        $this->assertCount(3, $parent->getSubproductsOfType(ItemTypes::WARRANTY));
        $this->assertEquals(4, $parent->getSubproductsOfType(ItemTypes::WARRANTY)->first()->plu);
        $this->assertEquals(6, $parent->getSubproductsOfType(ItemTypes::WARRANTY)->last()->plu);

        $this->assertCount(1, $parent->getSubproductsOfType(ItemTypes::RENT));
        $this->assertEquals(7, $parent->getSubproductsOfType(ItemTypes::RENT)->first()->plu);
    }

    /**
     * @test
     */
    public function does_shipping_rate_affect_total_price()
    {
        $cart = cart();

        $truck = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 20,
            'minimum_cart_price' => 0,
        ]);
        $truck2 = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 10,
            'minimum_cart_price' => 50,
        ]);

        $cart->addProduct([
            'plu' => 1,
            'quantity' => 1,
            'price' => 5,
            'type' => ItemTypes::PLU,
        ]);

        $cart->setShippingMethod($truck->method);

        $this->assertEquals('truck', $cart->getCart()->shipping_method);
        $this->assertEquals($truck->price, $cart->getShippingRate()->price);
        $this->assertEquals($truck->price + 5, $cart->getCart()->grand_total);

        $cart->addProduct([
            'plu' => 2,
            'quantity' => 1,
            'price' => 45,
            'type' => ItemTypes::PLU,
        ]);

        $this->assertEquals($truck2->price + 50, $cart->getCart()->grand_total);

        $cart->empty();

        $this->assertNull($cart->getCart()->shipping_method);
        $this->assertEquals(0, $cart->getCart()->grand_total);
        $this->assertEquals(0, $cart->getDeliveryCost());
    }

    /**
     * @test
     */
    public function removing_shipping_method_lowers_total_price_if_necessary()
    {
        $cart = cart();

        $truck = $cart->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 20,
            'minimum_cart_price' => 0,
        ]);

        $cart->addProduct([
            'plu' => 1,
            'quantity' => 1,
            'price' => 5,
            'type' => ItemTypes::PLU,
        ]);

        $cart->setShippingMethod($truck->method);

        $this->assertEquals(25, $cart->getCart()->grand_total);

        app(CartDatabase::class)->removeShippingMethod();

        $this->assertEquals(5, $cart->getCart()->grand_total);
    }

    /**
     * @test
     */
    public function can_add_subproducts_after_logging_in_and_out()
    {
        /**
         * @var Cart $cart
         */
        $cart = cart();

        // add product
        $sushi = [
            'plu' => 1,
            'quantity' => 1,
            'price' => 4.95,
            'type' => ItemTypes::PLU,
        ];

        $cart->addProduct($sushi);

        // Login
        $cart->updateIdentifier('logged_in');

        $this->assertCount(1, cart('logged_in')->items());

        // Logout
        $loggedOut = cart('logged_out_2');
        $this->assertCount(0, $loggedOut->items());

        // add product with subproducts
        $overpriced_sauce = [
            'plu' => 2,
            'quantity' => 1,
            'price' => 999.99,
            'type' => ItemTypes::PLU,
        ];

        $bottle = $loggedOut->addProduct($overpriced_sauce);

        $louis_vuitton_bottle = [
            'plu' => 3,
            'quantity' => 1,
            'price' => 9999.99,
            'type' => ItemTypes::WARRANTY,
            'parent_id' => $bottle->id,
        ];
        $loggedOut->addProduct($louis_vuitton_bottle);

        $this->assertCount(2, $loggedOut->items());

        // Log back in
        // Fetch cart from database again
        $cart = cart('logged_in');
        $finalCart = $cart->merge($loggedOut);

        $this->assertEquals(1, $finalCart->itemsTree()[1]->quantity);
        $this->assertCount(3, $finalCart->items());
    }
}
