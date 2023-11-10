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
            $table->bigInteger('oid');
            $table->bigInteger('uid');
            $table->string('eid');
            $table->string('status');
            $table->bigInteger('pid');
            $table->foreign('uid')->references('id')->on('orders');
            $table->foreign('pid')->references('id')->on('payments');
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
