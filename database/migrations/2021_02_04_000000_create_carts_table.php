<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Models\Cart;

class CreateCartsTable extends Migration
{
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        // A shop can have the table without this migration recorded (an older
        // webshop's database, e.g. vleeshalle 2026-11-02): it keeps what it has.
        if (Schema::hasTable(Cart::tableName())) {
            return;
        }

        Schema::create(Cart::tableName(), function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('identifier')->unique();
            $table->string('shipping_method')->nullable();

            $table->string('coupon_code')->nullable();
            $table->integer('items_count')->nullable();

            $table->decimal('grand_total', 12, 4)->default(0)->nullable();
            $table->decimal('sub_total', 12, 4)->default(0)->nullable();
            $table->decimal('tax_total', 12, 4)->default(0)->nullable();
            $table->decimal('discount', 12, 4)->default(0)->nullable();
            $table->string('checkout_method')->nullable();

            $table->integer('conversion_time')->nullable();
            $table->json('additional')->nullable();

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
        Schema::dropIfExists(Cart::tableName());
    }
}
