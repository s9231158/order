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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('info')->nullable();
            $table->string('openday');
            $table->dateTime('opentime');
            $table->dateTime('closetime');
            $table->boolean('enable')->default(true);
            $table->string('title');
            $table->string('img');
            $table->string('address');
            $table->string('api');
            $table->decimal('totalpoint');
            $table->integer('countpoint');
            $table->softDeletes();
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
        Schema::dropIfExists('restaurants');
    }
};
