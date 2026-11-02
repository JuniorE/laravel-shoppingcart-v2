<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Tests\TestCase;
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
}
