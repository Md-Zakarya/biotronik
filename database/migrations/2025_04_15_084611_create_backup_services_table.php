<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('backup_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->string('state');
            $table->string('backup_id')->unique(); 
            $table->string('hospital_name');
            $table->text('hospital_address')->nullable();
            $table->string('channel_partner');
            $table->dateTime('appointment_datetime');
            $table->string('service_type');
            $table->string('service_duration');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled']);
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->foreignId('service_engineer_id')->nullable()->constrained('users');
            $table->string('accompanying_person_name')->nullable();
            $table->string('accompanying_person_phone')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_services');
    }
};
