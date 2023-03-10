<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
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
        Schema::dropIfExists('carts');
    }
}
