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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('uid')->unsigned();
            $table->foreign('uid')->references('id')->on('users');
            $table->dateTime('ordertime');
            $table->dateTime('taketime');
            $table->integer('total');
            $table->string('phone');
            $table->string('address');
            $table->integer('status');
            $table->bigInteger('rid')->unsigned();
            $table->foreign('rid')->references('id')->on('restaurants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
