<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->nullable()->unique();
            $table->foreignId('violator_id')->references('id')->on('violators');
            $table->integer('offense_number');
            $table->string('vehicle_type', 10);
            $table->dateTime('datetime_of_apprehension');
            $table->foreignId('issued_by')->references('id')->on('users');
            $table->foreignId('payment_id')->nullable()->references('id')->on('payments');           
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
        Schema::dropIfExists('tickets');
    }
}
