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
        Schema::create('ecpay_backs', function (Blueprint $table) {
            $table->string('merchant_trade_no')->primary();
            $table->integer('merchant_id');
            $table->dateTime('trade_date');
            $table->dateTime('payment_date');
            $table->integer('rtn_code');
            $table->string('rtn_msg');
            $table->integer('amount');
            $table->string('check_mac_value');
            $table->timestamps();
            $table->foreign('merchant_trade_no')->references('merchant_trade_no')->on('ecpays');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ecpay_backs');
    }
};
