<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaruant__total__money', function (Blueprint $table) {
            $table->id();
            $table->integer('money');
            $table->dateTime('starttime');
            $table->dateTime('endtime');
            $table->timestamps();
            $table->bigInteger('rid')->unsigned();
            $table->foreign('rid')->references('id')->on('restaurants');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaruant__total__money');
    }
};
