<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateViolationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('violation_types', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('vehicle_type');
            $table->string('penalties');
            $table->timestamps();
            $table->softDeletes();
        });

        Artisan::call( 'db:seed', [
            '--class' => 'ViolationTypeSeeder',
            '--force' => true ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('violation_types');
    }
}
