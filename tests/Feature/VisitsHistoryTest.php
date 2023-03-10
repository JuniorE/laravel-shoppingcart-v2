<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Tests\TestCase;

class VisitsHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_add_product_to_history()
    {
        $this->assertNull(cart()->history());

        cart()->markVisited('5');
        cart()->markVisited('12');
        cart()->markVisited('40');
        cart()->markVisited('24');

        $this->assertCount(4, cart()->history()->visits);
        $this->assertContains('5', cart()->history()->visits);
        $this->assertContains('12', cart()->history()->visits);
        $this->assertContains('40', cart()->history()->visits);
        $this->assertContains('24', cart()->history()->visits);
    }
}
