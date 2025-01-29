<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImplantsTable extends Migration
{
    public function up()
    {
        Schema::create('implants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->date('implantation_date')->nullable();
            $table->boolean('pre_feb_2022');
            
            // Old Implant Details
            $table->string('hospital_state')->nullable();
            $table->string('hospital_name')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('channel_partner')->nullable();
            
            // Device Details
            $table->string('therapy_name')->nullable();
            $table->string('ipg_model')->nullable();
            $table->string('ipg_model_number')->nullable();
            $table->string('ra_rv_lead_model')->nullable();
            $table->boolean('has_ra_rv_lead')->nullable();
            $table->boolean('has_extra_lead')->nullable();
            $table->string('csp_lead_model')->nullable();
            $table->string('csp_catheter_model')->nullable();
            $table->string('device_name')->nullable();
            $table->string('ipg_serial_number');
            $table->string('ra_rv_lead_serial')->nullable();
            $table->string('csp_lead_serial')->nullable();
            
            // Documents
            $table->string('patient_id_card')->nullable();
            $table->string('warranty_card')->nullable();
            $table->string('interrogation_report')->nullable();
            $table->date('warranty_expired_at')->nullable();
            
            // New Implant Details
            $table->string('secret_key')->nullable();
            // $table->string('IPG_Serial_Number')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('implants');
    }
}