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
        Schema::create('oishii_menus', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rid')->unsigned();
            $table->string('info');
            $table->string('name');
            $table->integer('price');
            $table->string('img');
            $table->bigInteger('tid');
            $table->boolean('enable');
            $table->timestamps();
            $table->foreign('rid')->references('id')->on('restaurants');
            // $table->foreign('tid')->references('id')->on('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oishii_menus');
    }
};
