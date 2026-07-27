<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The carts table name is configurable. Set before the application boots, so
 * the migrations in this case really do build the renamed table — a rename
 * that only reached the model would leave every query pointing at a table
 * that does not exist.
 */
class CustomTableNameTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('shoppingcart.database.table', 'webshop_carts');
    }

    #[Test]
    public function the_migrations_build_the_configured_table_and_not_the_default_one()
    {
        $this->assertTrue(Schema::hasTable('webshop_carts'));
        $this->assertFalse(Schema::hasTable('carts'));
    }

    #[Test]
    public function the_model_reads_and_writes_the_configured_table()
    {
        $this->assertSame('webshop_carts', (new Models\Cart)->getTable());

        $cart = cart();

        $this->assertSame(1, DB::table('webshop_carts')->where('identifier', $cart->identifier)->count());
    }

    #[Test]
    public function carts_still_work_end_to_end_under_a_renamed_table()
    {
        $cart = cart();

        $cart->addProduct([
            'plu' => 1,
            'price' => 10,
            'quantity' => 2,
            'type' => ItemTypes::PLU,
        ]);

        $this->assertCount(1, $cart->items());
        $this->assertEquals(20.0, (float) $cart->getCart()->grand_total);

        // The cart_items foreign key has to point at the renamed table, so a
        // delete has to cascade rather than fail.
        $cart->empty();

        $this->assertCount(0, $cart->items());
    }
}
