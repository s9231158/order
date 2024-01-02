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
        Schema::create('user_recodes', function (Blueprint $table) {
            $table->id();
            $table->string('device');
            $table->unsignedBigInteger('uid');
            $table->string('ip');
            $table->dateTime('login');
            $table->timestamps();
            $table->foreign('uid')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_recodes');
    }
};
