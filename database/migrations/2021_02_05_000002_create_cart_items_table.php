<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use juniorE\ShoppingCart\Models\Cart;

class CreateCartItemsTable extends Migration
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
        if (Schema::hasTable('cart_items')) {
            return;
        }

        Schema::create('cart_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            // Named explicitly: constrained() would infer the referenced
            // table from the column name and miss a renamed carts table.
            $table->foreignId('cart_id')->constrained(Cart::tableName())->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('cart_items')->onDelete('cascade');
            $table->double('quantity')->default(0);
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
        // Nothing. A shop can have this table from before the migration was
        // recorded (an older webshop's database), so a rollback of its batch
        // dropped the table with every row in it. A rollback takes nothing
        // away; drop the table by hand when it really has to go.
    }
}
