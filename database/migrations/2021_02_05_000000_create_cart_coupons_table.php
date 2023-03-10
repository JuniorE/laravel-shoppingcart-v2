<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartCouponsTable extends Migration
{
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_coupons', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('status')->default(false);
            $table->integer('coupon_type')->default(0);

            $table->timestamp('starts_from')->nullable();
            $table->timestamp('ends_till')->nullable();

            $table->integer('usage_per_customer')->unsigned()->default(0);
            $table->integer('uses_per_coupon')->unsigned()->default(0);
            $table->integer('times_used')->unsigned()->default(0);

            $table->boolean('conditional')->default(true);
            $table->json('conditions')->nullable();
            $table->boolean('ends_other_coupons')->default(true)->nullable();

            $table->decimal('discount_amount', 12, 4)->default(0)->nullable();
            $table->decimal('discount_percent', 12, 4)->default(0)->nullable();
            $table->integer('discount_quantity')->default(0)->nullable();
            $table->integer('discount_step')->default(0)->nullable();

            $table->boolean('apply_to_shipping')->default(false);
            $table->boolean('free_shipping')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migration
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_coupons');
    }
}
