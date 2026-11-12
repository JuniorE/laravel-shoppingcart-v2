<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartShippingRatesTable extends Migration
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
        if (Schema::hasTable('cart_shipping_rates')) {
            return;
        }

        Schema::create('cart_shipping_rates', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('method');
            $table->string('method_description')->nullable();
            $table->decimal('price', 12, 4);
            $table->decimal('minimum_cart_price', 12, 4)->default(0);
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
        // Nothing. A shop can have this table from before the migration was
        // recorded (an older webshop's database), so a rollback of its batch
        // dropped the table with every row in it. A rollback takes nothing
        // away; drop the table by hand when it really has to go.
    }
}
