<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowHashToCartItemsTable extends Migration
{
    /**
     * Run the migration
     *
     * @return void
     */
    public function up()
    {
        // A shop can have the column without this migration recorded (an older
        // webshop's database, e.g. vleeshalle 2026-11-02): it keeps what it has.
        if (Schema::hasColumn('cart_items', 'row_hash')) {
            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('row_hash')->nullable();
        });
    }

    /**
     * Reverse the migration
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_shipping_rates');
    }
}
