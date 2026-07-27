<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Cart;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HelpersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_access_helpers()
    {
        $this->assertInstanceOf(Cart::class, cart());
    }
}
