<?php

namespace juniorE\ShoppingCart\Tests\Feature;

use juniorE\ShoppingCart\Data\Repositories\EloquentCartDatabase;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConfigTest extends TestCase
{
    #[Test]
    public function config_test()
    {
        $this->assertEquals(EloquentCartDatabase::class, config('shoppingcart.database.implementation'));
    }
}
