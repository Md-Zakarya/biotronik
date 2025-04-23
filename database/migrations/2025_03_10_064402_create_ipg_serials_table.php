<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ipg_serials', function (Blueprint $table) {
            $table->id();
            $table->string('ipg_serial_number')->unique();
            $table->string('model_number');
            $table->foreign('model_number')->references('model_number')->on('ipg_models');
            // $table->foreignId('distributor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('distributor_id')->nullable()->constrained('users')->onDelete('set null');

            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->date('date_added')->nullable();
            $table->boolean('is_implanted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipg_serials');
    }
};