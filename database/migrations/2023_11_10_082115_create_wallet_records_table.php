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
        Schema::create('wallet__records', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('out')->nullable();
            $table->integer('in')->nullable();
            $table->bigInteger('oid')->nullable();
            $table->bigInteger('uid')->unsigned();
            $table->integer('status');
            $table->bigInteger('pid')->unsigned()->nullable();
            $table->string('eid')->index();
            $table->foreign('uid')->references('id')->on('users');
            $table->foreign('pid')->references('id')->on('payments');
            $table->foreign('eid')->references('merchant_trade_no')->on('ecpays');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet__records');
    }
};
