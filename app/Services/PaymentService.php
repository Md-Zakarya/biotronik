<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a new payment record
     * 
     * @param array $paymentData
     * @param int $patientId
     * @param int|null $serviceEngineerId
     * @return Payment
     */
    public function createPayment(array $paymentData, int $patientId, ?int $serviceEngineerId = null): Payment
    {
        // Set default values if not provided
        $gstNumber = $paymentData['gst_number'] ?? 'AUTO-GENERATED';
        $panNumber = $paymentData['pan_number'] ?? 'AUTO-GENERATED';
        
        // Create the payment record
        $payment = Payment::create([
            'patient_id' => $patientId,
            'service_engineer_id' => $serviceEngineerId,
            'gst_number' => $gstNumber,
            'pan_number' => $panNumber,
            'amount' => $paymentData['amount'],
            'payment_status' => $paymentData['payment_status'] ?? 'pending',
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'payment_type' => $paymentData['payment_type'],
            'payment_details' => $paymentData['payment_details'] ?? []
        ]);
        
        Log::info('Payment created', [
            'payment_id' => $payment->id,
            'patient_id' => $patientId,
            'amount' => $payment->amount,
            'status' => $payment->payment_status
        ]);
        
        return $payment;
    }
}