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
        Schema::create('local_menus', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rid')->unsigned();
            $table->string('info')->nullable();
            $table->string('name');
            $table->integer('price');
            $table->string('img')->nullable();
            $table->bigInteger('tid')->unsigned();
            $table->boolean('enable')->default(true);
            $table->timestamps();
            $table->foreign('rid')->references('id')->on('restaurants');
            $table->foreign('tid')->references('id')->on('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_menus');
    }
};
