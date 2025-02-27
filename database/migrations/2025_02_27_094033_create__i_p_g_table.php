<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIPGTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ipg_devices', function (Blueprint $table) {
            $table->id();
            $table->string('ipg_serial_number')->unique();
            $table->string('ipg_model_name');
            $table->string('ipg_model_number');
            $table->boolean('is_linked')->default(false);
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('implant_id')->nullable()->constrained()->onDelete('set null');
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
        Schema::dropIfExists('ipg_devices');
    }
}