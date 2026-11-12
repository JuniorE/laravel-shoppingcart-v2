<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A shop can have the cart tables without these migrations recorded: an
 * older webshop's database (vleeshalle, 2026-11-02) stopped its first
 * `migrate` at "Table 'carts' already exists". Every migration keeps what is
 * already there.
 */
class MigrationsRerunTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function every_migration_runs_again_on_a_database_that_already_has_the_cart_tables()
    {
        $columns = fn () => collect(['carts', 'cart_coupons', 'cart_items', 'cart_shipping_rates', 'visits_histories'])
            ->mapWithKeys(fn (string $table) => [$table => Schema::getColumnListing($table)])
            ->all();
        $before = $columns();

        DB::table('migrations')->delete();

        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]), Artisan::output());
        $this->assertSame($before, $columns());
        $this->assertSame(8, DB::table('migrations')->count());
    }

    /**
     * On such a shop the tables are older than the migrations recorded for
     * them, so a `migrate:rollback` of that batch dropped carts, cart_items
     * and cart_shipping_rates with their rows. Two more migrations dropped
     * cart_shipping_rates in their down() (a copy-paste), so rolling back
     * either one removed the shop's delivery tiers. A rollback takes
     * nothing away any more: no table, no column and no row.
     */
    #[Test]
    public function a_rollback_of_every_migration_keeps_the_tables_and_what_they_hold()
    {
        $tables = ['carts', 'cart_coupons', 'cart_items', 'cart_shipping_rates', 'visits_histories'];
        $columns = fn () => collect($tables)->mapWithKeys(fn (string $table) => [$table => Schema::getColumnListing($table)])->all();

        DB::table('cart_shipping_rates')->insert(['method' => 'delivery', 'price' => 4.95, 'minimum_cart_price' => 0]);
        $cart = DB::table('carts')->insertGetId(['identifier' => 'rollback-test']);
        $before = $columns();

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--force' => true, '--step' => 99]), Artisan::output());

        $this->assertSame($before, $columns());
        $this->assertSame(1, DB::table('cart_shipping_rates')->where('method', 'delivery')->count());
        $this->assertSame(1, DB::table('carts')->where('id', $cart)->count());
        $this->assertSame(0, DB::table('migrations')->count());

        // And the migrations run again over what was kept.
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]), Artisan::output());
        $this->assertSame($before, $columns());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function migrationsThatOnceDroppedTheDeliveryTiers(): iterable
    {
        yield 'visits_histories' => ['CreateVisitsHistoriesTable'];
        yield 'row_hash' => ['AddRowHashToCartItemsTable'];
    }

    #[Test]
    #[DataProvider('migrationsThatOnceDroppedTheDeliveryTiers')]
    public function rolling_back_one_later_migration_leaves_the_delivery_tiers(string $migration)
    {
        DB::table('cart_shipping_rates')->insert(['method' => 'delivery', 'price' => 4.95, 'minimum_cart_price' => 0]);

        // The migrator has loaded the class by now (RefreshDatabase).
        (new $migration)->down();

        $this->assertTrue(Schema::hasTable('cart_shipping_rates'));
        $this->assertSame(1, DB::table('cart_shipping_rates')->count());
    }
}
