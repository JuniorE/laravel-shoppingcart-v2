<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Models\Cart;

class AddCouponCodeRelationToCartsTable extends Migration
{
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        // Adding a foreign key to an existing sqlite table forces a full
        // table rebuild, which Laravel's native (post-doctrine/dbal) sqlite
        // handling cannot express for a bare foreign-key blueprint. sqlite
        // only runs the test suite, where constraints are not enforced.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table(Cart::tableName(), function (Blueprint $table) {
            $table->foreign('coupon_code')->references('name')->on('cart_coupons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration
     *
     * @return void
     */
    public function down()
    {
        // Drop the constraint this migration added; dropping the whole carts
        // table here (what it used to do) belongs to the migration that
        // created it, and made a rollback destroy every cart.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table(Cart::tableName(), function (Blueprint $table) {
            $table->dropForeign(['coupon_code']);
        });
    }
}
