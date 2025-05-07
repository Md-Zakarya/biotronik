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
        Schema::create('device_upgrades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // Patient requesting the upgrade

            // Step 1: Patient entered fields (Previous Implant Details)
            $table->date('old_implantation_date');
            $table->string('old_implant_brand');
            $table->string('old_ipg_model'); // Corresponds to IPG Model Name in txt
            $table->string('old_lead_brand');
            $table->string('old_ra_rv_lead_model');
            $table->string('old_csp_catheter_brand')->nullable();
            $table->string('old_csp_lead_model')->nullable();

            // Step 2: New implant information (Location/Context)
            $table->string('state');
            $table->string('hospital_name');
            $table->string('doctor_name');
            $table->string('channel_partner');

            // Step 3: Distributor assignment
            $table->foreignId('service_engineer_id')->nullable()->constrained('users')->onDelete('set null'); // Link to the assigned service engineer

            // Step 4: Service Engineer fields (New Implant Details)
            $table->date('new_implantation_date')->nullable();
            $table->string('new_ipg_serial_number')->nullable();
            $table->string('new_ipg_model')->nullable(); // Corresponds to IPG Model Name in txt
            $table->string('new_ipg_model_number')->nullable();
            $table->string('new_therapy_name')->nullable();
            $table->string('new_device_name')->nullable(); // Corresponds to Device Type in txt
            $table->json('new_ra_rv_leads')->nullable(); // Corresponds to RA Lead Info (JSON) in txt
            $table->string('new_csp_catheter_model')->nullable(); // Corresponds to CSP Catheter Model Number in txt
            $table->string('new_csp_lead_model')->nullable(); // Corresponds to CSP Catheter Model Name in txt
            $table->string('new_csp_lead_serial')->nullable();

            // Status tracking
            $table->enum('status', ['pending', 'assigned', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_upgrades');
    }
};