<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_open_days', function (Blueprint $table) {
            $table->id();
            $table->boolean('Monday');
            $table->boolean('Tuesday');
            $table->boolean('Wednesday');
            $table->boolean('Thursday');
            $table->boolean('Friday');
            $table->boolean('Saturday');
            $table->boolean('Sunday');
            $table->timestamps();
            $table->foreign('id')->references('id')->on('restaurants');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_open_days');
    }
};
