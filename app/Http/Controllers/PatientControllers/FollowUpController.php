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
    // public function submitPaymentDetails(Request $request)
    // {
    //     $validated = $request->validate([
    //         'gst_number' => 'required|string',
    //         'pan_number' => 'required|string',
    //         'payment_details' => 'required|array',
    //         'amount' => 'required|numeric'
    //     ]);

    //     try {
    //         $payment = Payment::create([
    //             'patient_id' => $request->user()->id,
    //             'gst_number' => $validated['gst_number'],
    //             'pan_number' => $validated['pan_number'],
    //             'amount' => $validated['amount'],
    //             'payment_status' => 'completed',
    //             'payment_date' => now(),
    //             'payment_type' => 'follow_up',
    //             'payment_details' => $validated['payment_details']
    //         ]);

    //         return response()->json([
    //             'message' => 'Payment details saved successfully',
    //             'payment_id' => $payment->id
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error saving payment details',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function submitPaymentDetails(Request $request)
    {
        $validated = $request->validate([
            'gst_number' => 'required|string',
            'pan_number' => 'required|string',
            'payment_details' => 'required|array',
            'amount' => 'required|numeric',
            'payment_id' => 'nullable|exists:payments,id' // Optional payment_id for updating existing payment
        ]);

        try {
            // Start transaction
            DB::beginTransaction();

            if ($request->filled('payment_id')) {
                // Update existing payment
                $payment = Payment::where('id', $validated['payment_id'])
                    ->where('patient_id', $request->user()->id)
                    ->where('payment_status', 'pending')
                    ->first();

                if (!$payment) {
                    return response()->json([
                        'message' => 'Invalid payment ID or payment is already completed'
                    ], 400);
                }

                // Update the payment
                $payment->update([
                    'gst_number' => $validated['gst_number'],
                    'pan_number' => $validated['pan_number'],
                    'payment_status' => 'completed',
                    'payment_date' => now(),
                    'payment_details' => array_merge(
                        (array) $payment->payment_details,
                        $validated['payment_details'],
                        ['completed_by' => 'patient', 'completed_at' => now()->toDateTimeString()]
                    )
                ]);

                $message = 'Payment completed successfully';
            } else {
                // Create new payment
                $payment = Payment::create([
                    'patient_id' => $request->user()->id,

                    'service_engineer_id' => null,
                    'gst_number' => $validated['gst_number'],
                    'pan_number' => $validated['pan_number'],
                    'amount' => $validated['amount'],
                    'payment_status' => 'completed',
                    'payment_date' => now(),
                    'payment_type' => 'follow_up',
                    'payment_details' => $validated['payment_details']
                ]);

                $message = 'Payment details saved successfully';
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'payment_id' => $payment->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error processing payment',
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
                'appointment_datetime' => 'required|date_format:Y-m-d H:i|after:now',
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
        $followUp = FollowUpRequest::with('serviceEngineer') // Eager load the service engineer relationship
            ->where('patient_id', $user->id)
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
            'appointment_datetime' => $followUp->appointment_datetime->format('Y-m-d H:i'),
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

        // Add service engineer name if status is approved and a service engineer is assigned
        if ($followUp->status === FollowUpRequest::STATUS_APPROVED && $followUp->serviceEngineer) {
            $responseData['service_engineer_name'] = $followUp->serviceEngineer->name;
        }



        return response()->json([
            'message' => 'Follow-up status retrieved successfully',
            'data' => $responseData
        ], 200);
    }

    public function getPatientPaymentHistory1(Request $request)
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

    public function getPatientPaymentHistory(Request $request)
    {
        try {
            $patientId = $request->user()->id;

            $payments = Payment::where('patient_id', $patientId)
                ->orderBy('payment_date', 'desc')
                ->get();

            // If no payments found at all
            if ($payments->isEmpty()) {
                return response()->json([
                    'message' => 'No payment history found', // Changed message
                    'data' => [
                        'payment_frequency' => 0,
                        'payments' => [],
                        'paidOrNot' => 0 // No payments means not paid for follow-up
                    ]
                ], 200);
            }

            // Check specifically for completed follow-up payments
            $hasPaidForFollowUp = $payments->where('payment_type', 'follow_up')
                ->where('payment_status', 'completed') // Ensure payment is completed
                ->isNotEmpty();

            // Count frequency of all payments by payment_type
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
                        // Set paidOrNot based on whether a completed follow-up payment exists
                        'paidOrNot' => $hasPaidForFollowUp ? 1 : 0
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
    public function checkPaymentStatus1(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'paymentStatus' => 'unauthenticated',
                'message' => 'User not authenticated'
            ], 401);
        }

        $patientId = $user->id;

        // Get all follow_up payments ordered by most recent first
        $payments = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up') // Filter for follow_up payments only
            ->orderBy('created_at', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'paymentStatus' => 'payment_required',
                'message' => 'No follow-up payment records found. Payment is required.',
                'hasPaid' => false,
                'paymentHistory' => []
            ], 200);
        }

        // Get the most recent follow_up payment
        $latestPayment = $payments->first();

        // Format the payment history (only follow_up payments)
        $paymentHistory = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->created_at,
                'payment_status' => $payment->payment_status,
                'payment_type' => $payment->payment_type,
                'payment_by' => isset($payment->payment_details['paid_by']) ? $payment->payment_details['paid_by'] : 'patient',
                'gst_number' => $payment->gst_number,
                'pan_number' => $payment->pan_number
            ];
        });

        // Determine payment scenario
        $paymentStatus = 'unknown';
        $message = '';
        $hasPaid = false;

        if ($latestPayment->payment_status === 'completed') {
            $paymentStatus = 'paid';
            $message = 'Follow-up payment has been completed successfully.';
            $hasPaid = true;
            $paymentBy = isset($latestPayment->payment_details['paid_by']) ? $latestPayment->payment_details['paid_by'] : 'patient';

            // Get service engineer name from users table if available
            $serviceEngineerName = null;
            if ($latestPayment->service_engineer_id) {
                $serviceEngineer = \App\Models\User::find($latestPayment->service_engineer_id);
                $serviceEngineerName = $serviceEngineer ? $serviceEngineer->name : null;
            }
        } else {
            $paymentStatus = 'pending';
            $message = 'Follow-up payment is pending. Patient needs to complete payment.';
            $hasPaid = false;

            // Get service engineer name from users table if available
            $serviceEngineerName = null;
            if ($latestPayment->service_engineer_id) {
                $serviceEngineer = \App\Models\User::find($latestPayment->service_engineer_id);
                $serviceEngineerName = $serviceEngineer ? $serviceEngineer->name : null;
            }
        }

        return response()->json([
            'paymentStatus' => $paymentStatus,
            'message' => $message,
            'hasPaid' => $hasPaid,
            'paymentBy' => $paymentBy ?? 'patient',
            'lastPaymentDate' => $latestPayment->created_at,
            'amount' => $latestPayment->amount,
            'paymentType' => $latestPayment->payment_type, // Will always be 'follow_up'
            'serviceEngineerName' => $serviceEngineerName ?? null,
            'pendingPaymentId' => !$hasPaid ? $latestPayment->id : null,
            'paymentHistory' => $paymentHistory
        ], 200);
    }

    public function checkPaymentStatus11(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'paymentStatus' => 'unauthenticated',
                'message' => 'User not authenticated'
            ], 401);
        }

        $patientId = $user->id;

        // Get all follow_up payments ordered by most recent first
        $payments = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'paymentStatus' => 'payment_required',
                'message' => 'No follow-up payment records found. Payment is required.',
                'hasPaid' => false,
                'paymentHistory' => []
            ], 200);
        }

        // Get the most recent follow_up payment
        $latestPayment = $payments->first();

        // Format the payment history
        $paymentHistory = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->created_at,
                'payment_status' => $payment->payment_status,
                'payment_type' => $payment->payment_type,
                'payment_by' => isset($payment->payment_details['paid_by']) ? $payment->payment_details['paid_by'] : 'patient',
                'gst_number' => $payment->gst_number,
                'pan_number' => $payment->pan_number
            ];
        });

        // Initialize variables
        $paymentStatus = 'unknown';
        $message = '';
        $hasPaid = false;
        $needsNewPayment = false;

        if ($latestPayment->payment_status === 'completed') {
            // Check if there's a completed follow-up request using this payment
            $completedFollowUp = FollowUpRequest::where('payment_id', $latestPayment->id)
                ->where('status', FollowUpRequest::STATUS_COMPLETED)
                ->first();

            if ($completedFollowUp) {
                // If there's a completed follow-up with this payment, user needs to pay again
                $paymentStatus = 'payment_required_for_new_request';
                $message = 'Your previous follow-up has been completed. Payment is required for a new follow-up request.';
                $hasPaid = false;
                $needsNewPayment = true;
            } else {
                // Check for active follow-ups with this payment
                $activeFollowUp = FollowUpRequest::where('payment_id', $latestPayment->id)
                    ->whereIn('status', [FollowUpRequest::STATUS_PENDING, FollowUpRequest::STATUS_APPROVED])
                    ->first();

                if ($activeFollowUp) {
                    $paymentStatus = 'paid';
                    $message = 'Follow-up payment has been completed successfully. You have an active follow-up request.';
                    $hasPaid = true;
                } else {
                    $paymentStatus = 'paid';
                    $message = 'Follow-up payment has been completed successfully. You can now create a follow-up request.';
                    $hasPaid = true;
                }
            }
        } else {
            $paymentStatus = 'pending';
            $message = 'Follow-up payment is pending. Patient needs to complete payment.';
            $hasPaid = false;
        }

        // Get service engineer name if available
        $serviceEngineerName = null;
        if ($latestPayment->service_engineer_id) {
            $serviceEngineer = \App\Models\User::find($latestPayment->service_engineer_id);
            $serviceEngineerName = $serviceEngineer ? $serviceEngineer->name : null;
        }

        return response()->json([
            'paymentStatus' => $paymentStatus,
            'message' => $message,
            'hasPaid' => $hasPaid,
            'needsNewPayment' => $needsNewPayment,
            'paymentBy' => $latestPayment->payment_details['paid_by'] ?? 'patient',
            'lastPaymentDate' => $latestPayment->created_at,
            'amount' => $latestPayment->amount,
            'paymentType' => $latestPayment->payment_type,
            'serviceEngineerName' => $serviceEngineerName,
            'pendingPaymentId' => !$hasPaid ? $latestPayment->id : null,
            'paymentHistory' => $paymentHistory
        ], 200);
    }


    ///latest one
    public function checkPaymentStatus111(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'paymentStatus' => 'unauthenticated',
                'message' => 'User not authenticated'
            ], 401);
        }

        $patientId = $user->id;
        $status = [
            'canRaiseANewFollowUp' => null,
            'followUpStatus' => null,
            'message' => '',
            'paymentId' => null,
            'paymentAmount' => null,
            'serviceEngineerName' => null
        ];

        // 1. Check for pending payment generated by service engineer
        $pendingPayment = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up')
            ->where('payment_status', 'pending')
            ->whereNotNull('service_engineer_id')
            ->latest()
            ->first();

        if ($pendingPayment) {
            $status['canRaiseANewFollowUp'] = 'pending_payment_found';
            $status['message'] = 'Pending payment found. Complete the payment to proceed.';
            $status['paymentId'] = $pendingPayment->id;
            $status['paymentAmount'] = $pendingPayment->amount;

            // Get service engineer name
            if ($pendingPayment->service_engineer_id) {
                $serviceEngineer = \App\Models\User::find($pendingPayment->service_engineer_id);
                $status['serviceEngineerName'] = $serviceEngineer ? $serviceEngineer->name : null;
            }

            return $this->generateResponse($status);
        }

        // 2. Check for existing follow-up request
        $existingFollowUp = FollowUpRequest::where('patient_id', $patientId)
            ->latest()
            ->first();

        if ($existingFollowUp) {
            $status['canRaiseANewFollowUp'] = 'no_pending_payment_found';

            if ($existingFollowUp->status === FollowUpRequest::STATUS_COMPLETED) {
                $status['followUpStatus'] = 'completed_needs_new_payment';
                $status['message'] = 'Previous follow-up completed. New payment required for next follow-up.';
            } else {
                $status['followUpStatus'] = $existingFollowUp->status;
                $status['message'] = 'Active follow-up request exists with status: ' . $existingFollowUp->status;
                if ($existingFollowUp->service_engineer_id) {
                    $serviceEngineer = \App\Models\User::find($existingFollowUp->service_engineer_id);
                    $status['serviceEngineerName'] = $serviceEngineer ? $serviceEngineer->name : null;
                }
            }

            return $this->generateResponse($status);
        }

        // 3. Check latest completed payment without follow-up
        $latestCompletedPayment = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up')
            ->where('payment_status', 'completed')
            ->latest()
            ->first();

        if ($latestCompletedPayment) {
            // Check if this payment has any follow-up request
            $hasFollowUp = FollowUpRequest::where('payment_id', $latestCompletedPayment->id)->exists();

            if (!$hasFollowUp) {
                $status['canRaiseANewFollowUp'] = 'payment_done_but_no_follow_raised';
                $status['message'] = 'Payment completed. You can now raise a follow-up request.';
                $status['paymentId'] = $latestCompletedPayment->id;
                return $this->generateResponse($status);
            }
        }

        // No payment or follow-up found
        $status['canRaiseANewFollowUp'] = 'no_pending_or_done_payment_found';
        $status['message'] = 'No active payment or follow-up request found. New payment required.';

        return $this->generateResponse($status);
    }
    public function checkPaymentStatus(Request $request)
    {
        $user = $request->user();
        \Log::info('Checking payment status for user', ['user_id' => $user?->id]);
    
        if (!$user) {
            \Log::warning('Payment status check failed: User not authenticated');
            return response()->json([
                'paymentStatus' => 'unauthenticated',
                'message' => 'User not authenticated'
            ], 401);
        }
    
        $patientId = $user->id;
        \Log::info('Processing payment status check', ['patient_id' => $patientId]);
    
        $status = [
            'canRaiseANewFollowUp' => null,
            'followUpStatus' => null,
            'message' => '',
            'paymentId' => null,
            'paymentAmount' => null,
            'serviceEngineerName' => null
        ];
    
        // 1. Check for pending payment generated by service engineer
        \Log::info('Checking for pending payment by service engineer');
        $pendingPayment = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up')
            ->where('payment_status', 'pending')
            ->whereNotNull('service_engineer_id')
            ->latest()
            ->first();
    
        if ($pendingPayment) {
            \Log::info('Pending payment found', [
                'payment_id' => $pendingPayment->id,
                'amount' => $pendingPayment->amount,
                'service_engineer_id' => $pendingPayment->service_engineer_id
            ]);
    
            $status['canRaiseANewFollowUp'] = 'pending_payment_found';
            $status['message'] = 'Pending payment found. Complete the payment to proceed.';
            $status['paymentId'] = $pendingPayment->id;
            $status['paymentAmount'] = $pendingPayment->amount;
    
            // Get service engineer name
            if ($pendingPayment->service_engineer_id) {
                $serviceEngineer = \App\Models\User::find($pendingPayment->service_engineer_id);
                $status['serviceEngineerName'] = $serviceEngineer ? $serviceEngineer->name : null;
                \Log::info('Service engineer associated with payment', [
                    'engineer_id' => $pendingPayment->service_engineer_id,
                    'engineer_name' => $status['serviceEngineerName']
                ]);
            }
    
            return $this->generateResponse($status);
        }
    
        // 2. Check for latest completed payment without follow-up - MOVED THIS CHECK UP
        \Log::info('Checking for completed payment without follow-up');
        $latestCompletedPayment = Payment::where('patient_id', $patientId)
            ->where('payment_type', 'follow_up')
            ->where('payment_status', 'completed')
            ->latest()
            ->first();
    
        if ($latestCompletedPayment) {
            \Log::info('Found completed payment', ['payment_id' => $latestCompletedPayment->id]);
    
            // Check if this payment has any follow-up request
            $hasFollowUp = FollowUpRequest::where('payment_id', $latestCompletedPayment->id)->exists();
            \Log::info('Checking if payment has follow-up request', [
                'payment_id' => $latestCompletedPayment->id,
                'has_follow_up' => $hasFollowUp
            ]);
    
            if (!$hasFollowUp) {
                \Log::info('Payment has no follow-up request, patient can create one now');
                $status['canRaiseANewFollowUp'] = 'payment_done_but_no_follow_raised';
                $status['message'] = 'Payment completed. You can now raise a follow-up request.';
                $status['paymentId'] = $latestCompletedPayment->id;
                return $this->generateResponse($status);
            }
        }
    
        // 3. Check for existing follow-up request - MOVED THIS CHECK DOWN
        \Log::info('Checking for existing follow-up request');
        $existingFollowUp = FollowUpRequest::where('patient_id', $patientId)
            ->latest()
            ->first();
    
        if ($existingFollowUp) {
            \Log::info('Existing follow-up request found', [
                'follow_up_id' => $existingFollowUp->follow_up_id,
                'status' => $existingFollowUp->status,
                'service_engineer_id' => $existingFollowUp->service_engineer_id
            ]);
    
            $status['canRaiseANewFollowUp'] = 'no_pending_payment_found';
    
            if ($existingFollowUp->status === FollowUpRequest::STATUS_COMPLETED) {
                \Log::info('Follow-up is completed, needs new payment');
                $status['followUpStatus'] = 'completed_needs_new_payment';
                $status['message'] = 'Previous follow-up completed. New payment required for next follow-up.';
            } else {
                $status['followUpStatus'] = $existingFollowUp->status;
                $status['message'] = 'Active follow-up request exists with status: ' . $existingFollowUp->status;
    
                if ($existingFollowUp->service_engineer_id) {
                    $serviceEngineer = \App\Models\User::find($existingFollowUp->service_engineer_id);
                    $status['serviceEngineerName'] = $serviceEngineer ? $serviceEngineer->name : null;
                    \Log::info('Service engineer assigned to follow-up', [
                        'engineer_id' => $existingFollowUp->service_engineer_id,
                        'engineer_name' => $status['serviceEngineerName']
                    ]);
                }
            }
    
            return $this->generateResponse($status);
        }
    
        // No payment or follow-up found
        \Log::info('No active payment or follow-up found, new payment required');
        $status['canRaiseANewFollowUp'] = 'no_pending_or_done_payment_found';
        $status['message'] = 'No active payment or follow-up request found. New payment required.';
    
        return $this->generateResponse($status);
    }

    private function generateResponse($status)
    {
        // Get payment history
        $paymentHistory = Payment::where('patient_id', request()->user()->id)
            ->where('payment_type', 'follow_up')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->created_at,
                    'payment_status' => $payment->payment_status,
                    'payment_type' => $payment->payment_type,
                    'payment_by' => $payment->payment_details['paid_by'] ?? 'patient',
                    'gst_number' => $payment->gst_number,
                    'pan_number' => $payment->pan_number
                ];
            });

        return response()->json([
            'status' => $status['canRaiseANewFollowUp'],
            'followUpStatus' => $status['followUpStatus'],
            'message' => $status['message'],
            'paymentId' => $status['paymentId'],
            'paymentAmount' => $status['paymentAmount'],
            'serviceEngineerName' => $status['serviceEngineerName'],
            'paymentHistory' => $paymentHistory
        ], 200);
    }


}