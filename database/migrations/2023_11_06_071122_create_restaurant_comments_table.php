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
        Schema::create('restaurant_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('uid')->unsigned();
            $table->bigInteger('rid')->unsigned();
            $table->timestamps();
            $table->string('comment');
            $table->decimal('point');
            $table->foreign('uid')->references('id')->on('users');
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
        Schema::dropIfExists('restaurant_comments');
    }
};
