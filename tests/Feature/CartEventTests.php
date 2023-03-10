<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Events\Cart\CartCreatedEvent;
use juniorE\ShoppingCart\Events\Cart\CartDeletedEvent;
use juniorE\ShoppingCart\Events\Cart\CartUpdatedEvent;
use juniorE\ShoppingCart\Events\CartCoupon\CartCouponCreatedEvent;
use juniorE\ShoppingCart\Events\CartCoupon\CartCouponDeletedEvent;
use juniorE\ShoppingCart\Events\CartCoupon\CartCouponUpdatedEvent;
use juniorE\ShoppingCart\Events\CartItems\CartItemCreatedEvent;
use juniorE\ShoppingCart\Events\CartItems\CartItemDeletedEvent;
use juniorE\ShoppingCart\Events\CartItems\CartItemUpdatedEvent;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateCreatedEvent;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateDeletedEvent;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateUpdatedEvent;
use juniorE\ShoppingCart\Tests\TestCase;

class CartEventTests extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function cart_events()
    {
        Event::fake([
            CartCreatedEvent::class,
            CartUpdatedEvent::class,
            CartDeletedEvent::class,
        ]);

        $cart = cart();

        Event::assertDispatchedTimes(CartCreatedEvent::class, 1);

        cart()->updateIdentifier(md5(now()->toISOString()));

        Event::assertDispatchedTimes(CartUpdatedEvent::class, 1);

        cart()->destroy();

        Event::assertDispatchedTimes(CartDeletedEvent::class, 1);
    }

    /**
     * @test
     */
    public function cart_item_events()
    {
        Event::fake([
            CartItemCreatedEvent::class,
            CartItemUpdatedEvent::class,
            CartItemDeletedEvent::class,
        ]);

        $item = cart()->addProduct([
            'plu' => 5,
        ]);

        Event::assertDispatchedTimes(CartItemCreatedEvent::class, 1);

        cart()->itemsRepository->setQuantity($item, 1);

        Event::assertDispatchedTimes(CartItemUpdatedEvent::class, 1);

        cart()->removeItem($item);

        Event::assertDispatchedTimes(CartItemDeletedEvent::class, 1);
    }

    /**
     * @test
     */
    public function cart_shipping_rate_events()
    {
        Event::fake([
            CartShippingRateCreatedEvent::class,
            CartShippingRateUpdatedEvent::class,
            CartShippingRateDeletedEvent::class,
        ]);

        $truck = cart()->shippingRateRepository->addShippingRate([
            'method' => 'truck',
            'price' => 10,
            'minimum_cart_price' => 0,
        ]);

        Event::assertDispatchedTimes(CartShippingRateCreatedEvent::class, 1);

        cart()->shippingRateRepository->setPrice($truck, 20);

        Event::assertDispatchedTimes(CartShippingRateUpdatedEvent::class, 1);

        cart()->shippingRateRepository->removeShippingRate($truck);

        Event::assertDispatchedTimes(CartShippingRateDeletedEvent::class, 1);
    }

    /**
     * @test
     */
    public function cart_coupon_events()
    {
        Event::fake([
            CartCouponCreatedEvent::class,
            CartCouponUpdatedEvent::class,
            CartCouponDeletedEvent::class,
        ]);

        $coupon = cart()->couponsRepository->addCoupon([
            'name' => 'WELCOME10',
            'coupon_type' => CouponTypes::PERCENT,
            'discount_percent' => 0.10,
        ]);

        Event::assertDispatchedTimes(CartCouponCreatedEvent::class, 1);

        cart()->couponsRepository->setFreeShipping($coupon, true);

        Event::assertDispatchedTimes(CartCouponUpdatedEvent::class, 1);

        cart()->couponsRepository->removeCoupon($coupon);

        Event::assertDispatchedTimes(CartCouponDeletedEvent::class, 1);
    }
}
