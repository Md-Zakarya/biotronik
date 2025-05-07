<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('follow_up_requests', function (Blueprint $table) {
            $table->id();
            $table->string('follow_up_id')->unique();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('payment_id')->constrained();
            $table->string('state');
            $table->string('hospital_name');
            $table->string('doctor_name');
            $table->string('channel_partner');
            $table->string('accompanying_person_name');
            $table->string('accompanying_person_phone');
          
            $table->datetime('appointment_datetime')->nullable();
            $table->text('reason')->nullable();
            $table->string('status');

            $table->foreignId('service_engineer_id')->nullable()->constrained('users');
          
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('follow_up_requests');
    }
};