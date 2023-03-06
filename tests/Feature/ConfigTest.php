<?php


namespace juniorE\ShoppingCart\Tests\Feature;

use juniorE\ShoppingCart\Data\Repositories\EloquentCartDatabase;
use juniorE\ShoppingCart\Tests\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function config_test()
    {
        $this->assertEquals(EloquentCartDatabase::class, config("shoppingcart.database.implementation"));
    }
}
