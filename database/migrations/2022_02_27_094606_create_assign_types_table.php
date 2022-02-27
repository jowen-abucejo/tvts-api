<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateAssignTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('violation_id');
            $table->foreignId('violation_type_id');
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('assign_types');
    }
}
