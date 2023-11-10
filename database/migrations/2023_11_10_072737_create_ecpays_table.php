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
        Schema::create('ecpays', function (Blueprint $table) {
            $table->string('merchant_trade_no')->primary();
            $table->integer('merchant_id');
            $table->dateTime('merchant_trade_date');
            $table->integer('amount');
            $table->string('trade_desc');
            $table->string('return_url');
            $table->string('choose_payment');
            $table->string('check_mac_value');
            $table->integer('encrypt_type');
            $table->string('lang');
            $table->timestamps();
            $table->foreign('merchant_trade_no')->references('eid')->on('wallet__records');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ecpays');
    }
};
