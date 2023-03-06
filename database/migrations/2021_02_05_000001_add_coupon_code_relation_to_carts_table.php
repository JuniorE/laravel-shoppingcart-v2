<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddCouponCodeRelationToCartsTable extends Migration {
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carts', function(Blueprint $table) {
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
        Schema::dropIfExists('carts');
    }
}
