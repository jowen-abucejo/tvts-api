<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateViolationViolationTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('violation_violation_type', function (Blueprint $table) {
            $table->foreignId('violation_id');
            $table->foreignId('violation_type_id');
        });

        Artisan::call( 'db:seed', [
            '--class' => 'ViolationSeeder',
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
        Schema::dropIfExists('violation_violation_type');
    }
}
