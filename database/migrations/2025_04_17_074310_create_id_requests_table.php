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
        Schema::create('id_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique(); // Unique request ID (e.g., IDR-12345)
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('patient_name');
            $table->string('phone_number');
            $table->text('delivery_address');
            $table->string('state');
            $table->string('city');
            $table->string('pin_code');
            
            // Payment information - just reference the payment record
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            
            // Shipping information
            $table->string('shipping_partner')->nullable(); // Name of shipping partner
            $table->string('tracking_id')->nullable(); // Tracking ID from shipping partner
            $table->enum('status', [
                'printing',
                'delivery_partner_picked', 
                'in_transit', 
                'delivered'
            ])->default('printing');
            
            // Status timestamps for tracking
            $table->timestamp('printing_at')->nullable();
            $table->timestamp('delivery_partner_picked_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('id_requests');
    }
};