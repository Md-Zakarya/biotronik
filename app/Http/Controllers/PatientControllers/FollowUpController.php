<?php

namespace App\Http\Controllers\PatientControllers;

use App\Http\Controllers\Controller;
use App\Models\FollowUpRequest;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowUpController extends Controller
{
    public function deleteFollowUpRequest(Request $request)
    {
        try {
            // Get authenticated user and their ID
            $user = $request->user();
            $patientId = $user->id;

            // Find and delete the latest follow-up request for the authenticated patient
            $followUpRequest = FollowUpRequest::where('patient_id', $patientId)
                ->latest()  // Orders by created_at in descending order
                ->first();

            if (!$followUpRequest) {
                return response()->json([
                    'message' => 'No active follow-up request found'
                ], 404);
            }

            $followUpRequest->delete();

            return response()->json([
                'message' => 'Follow-up request deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function submitPaymentDetails(Request $request)
    {
        $validated = $request->validate([
            'gst_number' => 'required|string',
            'pan_number' => 'required|string',
            'payment_details' => 'required|array',
            'amount' => 'required|numeric'
        ]);

        try {
            $payment = Payment::create([
                'patient_id' => $request->user()->id,
                'gst_number' => $validated['gst_number'],
                'pan_number' => $validated['pan_number'],
                'amount' => $validated['amount'],
                'payment_status' => 'completed',
                'payment_date' => now(),
                'payment_type' => 'follow_up',
                'payment_details' => $validated['payment_details']
            ]);

            return response()->json([
                'message' => 'Payment details saved successfully',
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error saving payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createFollowUpRequest(Request $request)
    {
        try {
            $patient = $request->user();

            // Check for existing active request (pending or approved)
            $activeRequest = FollowUpRequest::where('patient_id', $patient->id)
                ->whereIn('status', [FollowUpRequest::STATUS_PENDING, FollowUpRequest::STATUS_APPROVED])
                ->first();

            if ($activeRequest) {
                return response()->json([
                    'message' => 'An active follow-up request already exists',
                    'follow_up_id' => $activeRequest->follow_up_id
                ], 400);
            }

            // First verify patient has valid payment and get payment ID
            $payment = Payment::where('patient_id', $patient->id)
                ->where('payment_type', 'follow_up')
                ->where('payment_status', 'completed')
                ->latest()
                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Payment required before creating follow-up request'
                ], 403);
            }

            $validated = $request->validate([
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'doctor_name' => 'required|string',
                'channel_partner' => 'required|string',
                'accompanying_person_name' => 'required|string',
                'accompanying_person_phone' => 'required|string',
                'appointment_datetime' => 'required|date_format:Y-m-d H:i:s|after:now',
                'reason' => 'required|string'
            ]);

            // Get latest rejected request if exists
            $lastRejectedRequest = FollowUpRequest::where('patient_id', $patient->id)
                ->where('status', FollowUpRequest::STATUS_REJECTED)
                ->latest()
                ->first();

            $requestData = [
                'patient_id' => $patient->id,
                'payment_id' => $payment->id,
                'status' => FollowUpRequest::STATUS_PENDING,
                ...$validated
            ];

            if ($lastRejectedRequest) {
                // Update the rejected request instead of creating new one
                $lastRejectedRequest->update($requestData);
                $followUpRequest = $lastRejectedRequest->fresh();
                $message = 'Follow-up request resubmitted successfully';
            } else {
                // Create new request if no rejected request exists
                $followUpRequest = FollowUpRequest::create($requestData);
                $message = 'Follow-up request created successfully';
            }

            return response()->json([
                'message' => $message,
                'follow_up_id' => $followUpRequest->follow_up_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getFollowUpStatus(Request $request)
{
    $user = $request->user();

    // Retrieve the latest follow-up request for the authenticated user
    $followUp = FollowUpRequest::where('patient_id', $user->id)
        ->latest()
        ->first();

    if (!$followUp) {
        return response()->json([
            'message' => 'Follow-up request not found'
        ], 404);
    }

    // Retrieve the associated payment using the payment_id from the follow-up request
    $payment = Payment::find($followUp->payment_id);

    if (!$payment) {
        return response()->json([
            'message' => 'Payment information not found'
        ], 404);
    }

    $responseData = [
        'status' => $followUp->status,
        'follow_up_id' => $followUp->follow_up_id,
        'purchase' => $payment->payment_date,
        'validity' => \Carbon\Carbon::parse($payment->payment_date)->addDays(15), // 15 days after purchase date
        'appointment_datetime' => $followUp->appointment_datetime->format('Y-m-d H:i:s'),
        'reason' => $followUp->reason,
        'channel_partner' => $followUp->channel_partner,
        'accompanying_person_name' => $followUp->accompanying_person_name,
        'accompanying_person_phone' => $followUp->accompanying_person_phone,
        'hospital_name' => $followUp->hospital_name,
        'doctor_name' => $followUp->doctor_name,
        'patient_name' => $user->name,
        'patient_phone' => $user->phone_number,
        'patient_email' => $user->email,
    ];

    // Add rejection_reason if status is rejected
    if ($followUp->status === 'rejected' && !empty($followUp->rejection_reason)) {
        $responseData['rejection_reason'] = $followUp->rejection_reason;
    }

    return response()->json([
        'message' => 'Follow-up status retrieved successfully',
        'data' => $responseData
    ], 200);
}

    public function getPatientPaymentHistory(Request $request)
    {
        try {
            $patientId = $request->user()->id;

            $payments = Payment::where('patient_id', $patientId)
                ->orderBy('payment_date', 'desc')
                ->get();

            // If no payments found
            if ($payments->isEmpty()) {
                return response()->json([
                    'message' => 'No follow up request available',
                    'data' => [
                        'payment_frequency' => 0,
                        'payments' => [],
                        'paidOrNot' => 0
                    ]
                ], 200);
            }

            // Count frequency of payments by payment_type
            $paymentFrequency = $payments->groupBy('payment_type')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_amount' => $group->sum('amount')
                    ];
                });

            return response()->json([
                'message' => 'Payment history retrieved successfully',
                'data' => [
                    'payment_frequency' => [
                        'total_payments' => $payments->count(),
                        'breakdown' => $paymentFrequency,
                        'paidOrNot' => 1
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkPaymentStatus(Request $request)
    {
        $patientId = $request->user()->id;
        
        $payment = Payment::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$payment) {
            return response()->json([
                'hasPaid' => false,
                'message' => 'No payment records found'
            ], 200);
        }

        return response()->json([
            'hasPaid' => true,
            'lastPaymentDate' => $payment->created_at,
            'amount' => $payment->amount
        ], 200);
    }

}