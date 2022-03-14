<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateExtraPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extra_properties', function (Blueprint $table) {
            $table->id();
            $table->string("property")->nullable();
            $table->string("property_owner");
            $table->string("text_label");
            $table->string("data_type");
            $table->string('options')->nullable();
            $table->boolean("is_multiple_select")->nullable()->default(false);
            $table->boolean("is_required");
            $table->integer("order_in_form")->nullable()->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        if ( \App\Models\ExtraProperty::count() < 1 )
        {
            Artisan::call( 'db:seed', [
                '--class' => 'ExtraPropertySeeder',
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
        Schema::dropIfExists('extra_properties');
    }
}
