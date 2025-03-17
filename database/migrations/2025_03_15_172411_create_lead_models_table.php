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
        Schema::create('lead_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_number')->unique();
            $table->string('model_name');
            $table->string('device_type');
            $table->timestamps();
            
            // Index for faster searches
            $table->index('model_number');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_models');
    }
};