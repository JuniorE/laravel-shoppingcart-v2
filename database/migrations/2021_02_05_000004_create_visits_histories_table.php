<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitsHistoriesTable extends Migration
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
        if (Schema::hasTable('visits_histories')) {
            return;
        }

        Schema::create('visits_histories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('cart_id');
            $table->json('visits');
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
        Schema::dropIfExists('cart_shipping_rates');
    }
}
