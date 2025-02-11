<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceReplacementsTable extends Migration
{
    public function up()
    {
        Schema::create('device_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('implant_id')->constrained('implants');
            
            // Common fields
            $table->string('state');
            $table->string('hospital_name');
            $table->string('doctor_name');
            $table->string('channel_partner');
            
            $table->string('new_ipg_serial_number')->nullable();
            // Warranty fields
            $table->text('replacement_reason')->nullable();
            $table->date('planned_replacement_date')->nullable();
            $table->string('interrogation_report_path')->nullable();
            $table->string('prescription_path')->nullable();
            $table->foreignId('service_engineer_id')->nullable()->unique()->constrained('users');

            
            // Service charge
            $table->decimal('service_charge', 10, 2)->nullable();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('service_completed')->default(false);

            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_replacements');
    }
}