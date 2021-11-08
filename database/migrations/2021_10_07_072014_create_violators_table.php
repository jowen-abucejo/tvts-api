<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViolatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('violators', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('birth_date');
            $table->string('address');
            $table->string('license_number')->nullable();
            $table->string('mobile_number', 10)->nullable();
            $table->string('parent_and_license')->nullable();
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
        Schema::dropIfExists('violators');
    }
}
