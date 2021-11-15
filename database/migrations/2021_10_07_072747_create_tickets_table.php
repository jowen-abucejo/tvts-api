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
            $table->string('plate_number', 20);
            
            $table->string('vehicle_owner');
            $table->string('owner_address');
            $table->dateTime('datetime_of_apprehension');
            $table->string('place_of_apprehension');
            $table->boolean('vehicle_is_impounded')->nullable()->default(0);
            $table->boolean('is_under_protest')->nullable()->default(0);
            $table->boolean('license_is_confiscated')->nullable()->default(0);
            $table->string('document_signature')->nullable();   
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
