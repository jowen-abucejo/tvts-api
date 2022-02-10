<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateTicketExtraPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_extra_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId("ticket_id");
            $table->foreignId("extra_property_id");
            $table->string("property_value")->nullable();
            $table->timestamps();
        });

        if ( \App\Models\User::count() < 1 )
        {
            Artisan::call( 'db:seed', [
                '--class' => 'DatabaseSeeder',
                '--force' => true ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_extra_properties');
    }
}
