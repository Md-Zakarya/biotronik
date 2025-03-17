serials_table.php
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
        Schema::create('lead_serials', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('lead_model_number');
            $table->foreign('lead_model_number')->references('model_number')->on('lead_models')->onDelete('cascade');
            $table->unsignedBigInteger('distributor_id')->nullable();
            $table->foreign('distributor_id')->references('id')->on('users');
            // $table->boolean('is_assigned')->default(false);
            $table->timestamps();
            
            // Index for faster searches
            $table->index('serial_number');
            $table->index('lead_model_number');
            // $table->index('is_assigned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_serials');
    }
};