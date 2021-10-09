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
            $table->foreignId('violator_id');
            $table->string('plate_number');
            
            $table->boolean('license_is_confiscated')->nullable()->default(0);
            $table->string('vehicle_owner');
            $table->string('owner_address');
            $table->boolean('vehicle_is_impounded')->nullable()->default(0);
            $table->string('place_of_apprehension');
            $table->boolean('is_admitted')->nullable()->default(1);
            $table->string('document_signature')->nullable();   
            $table->foreignId('issued_by');
            $table->foreignId('payment_id')->nullable()->default(0);           
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
