<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateCartItemsTable extends Migration {
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_items', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->foreignId('cart_id')->constrained()->onDelete("cascade");
            $table->foreignId('parent_id')->nullable()->constrained('cart_items')->onDelete("cascade");
//            $table->integer('cart_id')->unsigned();
//            $table->integer('parent_id')->unsigned()->nullable();
            $table->decimal('quantity')->default(0);
            $table->string('plu');
            $table->integer('type')->default(1);
            $table->decimal('weight', 12, 4)->default(1);
            $table->decimal('total_weight', 12, 4)->default(1);

            $table->string('coupon_code')->nullable();
            $table->decimal('price', 12, 4)->default(1);
            $table->decimal('total', 12, 4)->default(1);
            $table->decimal('discount', 12, 4)->default(0);
            $table->decimal('tax_percent', 12, 4)->default(0)->nullable();
            $table->decimal('tax_amount', 12, 4)->default(0)->nullable();

            $table->json('additional')->nullable();

            $table->timestamps();
        });

//        Schema::table('cart_items', function(Blueprint $table) {
//            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
//            $table->foreign('parent_id')->references('id')->on('cart_items')->onDelete('cascade');
//            $table->foreign('coupon_code')->references('name')->on('cart_coupons')->onDelete('cascade');
//        });
    }

    /**
     * Reverse the migration
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
}
