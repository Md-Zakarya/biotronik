<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingImplantsTable extends Migration
{
    public function up()
    {
        Schema::create('pending_implants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->boolean('pre_feb_2022')->default(true);
            $table->string('ipg_serial_number');
            $table->date('implantation_date')->nullable();
            $table->string('ipg_model')->nullable();
            $table->string('ipg_model_number')->nullable();
            $table->string('hospital_state')->nullable();
            $table->string('hospital_name')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('channel_partner')->nullable();
            $table->string('therapy_name')->nullable();
            $table->string('device_name')->nullable();
            $table->boolean('has_ra_rv_lead')->nullable();
            $table->json('ra_rv_leads')->nullable();
            $table->boolean('has_extra_lead')->nullable();
            $table->string('csp_lead_model')->nullable();
            $table->string('csp_catheter_model')->nullable();
            $table->string('csp_lead_serial')->nullable();
            $table->string('patient_id_card')->nullable();
            $table->string('warranty_card')->nullable();
            $table->string('interrogation_report')->nullable();
            $table->string('lead_brand')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_implants');
    }
}