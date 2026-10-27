<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\BaseCart;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\Cart as CartModel;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CartRegistryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_cart_helper_returns_the_same_instance_within_a_request()
    {
        $this->assertSame(cart(), cart());
    }

    #[Test]
    public function an_explicit_identifier_resolves_to_the_same_instance()
    {
        $cart = cart();

        $this->assertSame($cart, cart($cart->identifier));
    }

    #[Test]
    public function different_identifiers_resolve_to_different_carts()
    {
        $first = cart();
        $second = cart(BaseCart::generateIdentifier());

        $this->assertNotSame($first, $second);
        $this->assertNotEquals($first->identifier, $second->identifier);
    }

    #[Test]
    public function totals_reflect_a_removal_done_through_the_shared_instance()
    {
        $cart = cart();
        $removed = $cart->addProduct([
            'plu' => 1,
            'price' => 10,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);
        $cart->addProduct([
            'plu' => 2,
            'price' => 5,
            'quantity' => 2,
            'type' => ItemTypes::PLU,
        ]);

        $cart->removeItem($removed);

        $this->assertCount(1, $cart->items());
        $this->assertEquals(10.0, (float) $cart->getCart()->grand_total);
    }

    #[Test]
    public function a_renamed_cart_is_resolved_fresh_under_its_new_identifier()
    {
        $cart = cart();
        $cart->addProduct([
            'plu' => 1,
            'price' => 10,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);
        $cartId = $cart->getCart()->id;

        $cart->updateIdentifier('customer-123');

        $resolved = cart();

        $this->assertEquals('customer-123', $resolved->identifier);
        $this->assertEquals($cartId, $resolved->getCart()->id);
    }

    #[Test]
    public function a_named_cart_that_does_not_exist_is_created_under_that_name()
    {
        $first = cart('x');
        $second = cart('x');

        $this->assertSame('x', $first->identifier);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CartModel::where('identifier', 'x')->count());
        $this->assertSame(1, CartModel::count());
    }

    #[Test]
    public function a_cart_deleted_mid_request_is_recreated_under_its_own_identifier()
    {
        $cart = cart('x');
        $cart->getCart()->delete();

        $cart->addProduct([
            'plu' => 1,
            'price' => 10,
            'quantity' => 1,
            'type' => ItemTypes::PLU,
        ]);

        $this->assertSame('x', $cart->identifier);
        $this->assertSame(1, CartModel::where('identifier', 'x')->count());
        $this->assertSame($cart->id, cart('x')->getCart()->id);
        $this->assertCount(1, cart('x')->items());
    }
}
