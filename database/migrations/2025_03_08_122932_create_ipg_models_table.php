models_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ipg_models', function (Blueprint $table) {
            $table->string('model_number')->primary();
            $table->string('model_name');
            $table->string('device_type');
            $table->boolean('cardiomessenger_enable')->default(false);
            $table->integer('warranty')->default(0); // Warranty in days
            $table->boolean('mr_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipg_models');
    }
};