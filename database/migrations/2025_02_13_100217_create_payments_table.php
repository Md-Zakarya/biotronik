<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->unsignedBigInteger('service_engineer_id')->nullable();
            $table->string('gst_number');
            $table->string('pan_number');
            $table->decimal('amount', 10, 2);
            $table->string('payment_status');
            $table->datetime('payment_date');
            $table->string('payment_type');
            $table->json('payment_details');
            $table->timestamps();
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};