<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Models\Cart;

/**
 * A cart whose prices exclude VAT, and the VAT bucket of each line.
 *
 * Every cart that exists when this runs keeps prices that include VAT: the
 * column defaults to true. Guarded, so an application that added either
 * column itself is left alone.
 */
class AddNetPricingToCarts extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn(Cart::tableName(), 'prices_include_tax')) {
            Schema::table(Cart::tableName(), function (Blueprint $table) {
                $table->boolean('prices_include_tax')->default(true);
            });
        }

        if (! Schema::hasColumn('cart_items', 'tax_code')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedInteger('tax_code')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('cart_items', 'tax_code')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropColumn('tax_code');
            });
        }

        if (Schema::hasColumn(Cart::tableName(), 'prices_include_tax')) {
            Schema::table(Cart::tableName(), function (Blueprint $table) {
                $table->dropColumn('prices_include_tax');
            });
        }
    }
}
