<?php

namespace App\Http\Controllers\SE;

use App\Http\Controllers\Controller;
use App\Models\FollowUpRequest;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function getFollowUpRequests(Request $request)
    {
        try {
            $serviceEngineerId = $request->user()->id;

            $followUpRequests = FollowUpRequest::with(['patient'])
                ->where('status', FollowUpRequest::STATUS_APPROVED)
                ->where('service_engineer_id', $serviceEngineerId)
                ->get()
                ->map(function ($request) {
                    return [
                        'Follow_up_request_id' => $request->id,
                        'patient_id' => $request->patient->id,
                        'patient_name' => $request->patient->name,
                        'state' => $request->state,
                        'ticket_type' => 'Follow-up Service',
                        'appointment_datetime' => $request->appointment_datetime,
                        // Change status display to 'pending' when status is 'approved'
                        'status' => $request->status === FollowUpRequest::STATUS_APPROVED ? 'pending' : $request->status,
                    ];
                });

            return response()->json([
                'message' => 'Approved follow-up requests retrieved successfully',
                'data' => $followUpRequests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving follow-up requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFollowUpStatus(Request $request, $id)
    {
        try {
            $followUpRequest = FollowUpRequest::with(['patient', 'patient.implant'])
                ->where('id', $id)
                ->first();

            if (!$followUpRequest) {
                return response()->json([
                    'message' => 'Follow-up request not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Follow-up request details retrieved successfully',
                'data' => [
                    // Appointment Details
                    'appointment_details' => [
                        // 'appointment_date' => $followUpRequest->appointment_date,
                        // 'appointment_time' => $followUpRequest->appointment_time,

                        'appointment_datetime' => $followUpRequest->appointment_datetime,
                        'reason' => $followUpRequest->reason,
                        'status' => $followUpRequest->status
                    ],

                    // Hospital & Physician Details
                    'hospital_details' => [
                        'hospital_name' => $followUpRequest->hospital_name,
                        'doctor_name' => $followUpRequest->doctor_name,
                        'state' => $followUpRequest->state
                    ],

                    // Device Details
                    'device_details' => [
                        'therapy_type' => $followUpRequest->patient->implant->therapy_name,
                        'ipg_device_type' => $followUpRequest->patient->implant->device_name,
                        'ipg_model_name' => $followUpRequest->patient->implant->ipg_model,
                        'ipg_model_number' => $followUpRequest->patient->implant->ipg_model_number,
                        'ipg_serial_number' => $followUpRequest->patient->implant->ipg_serial_number
                    ],

                    // Patient Details
                    'patient_details' => [
                        'patient_name' => $followUpRequest->patient->name,
                        'patient_phone' => $followUpRequest->patient->phone_number,
                        'patient_email' => $followUpRequest->patient->email
                    ],

                    // Accompanying Person Details
                    'accompanying_person_details' => [
                        'name' => $followUpRequest->accompanying_person_name,
                        'phone' => $followUpRequest->accompanying_person_phone
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving follow-up request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function markAsComplete(Request $request, $id)
    {
        try {
            // Get the follow-up request and verify it belongs to this service engineer
            $followUpRequest = FollowUpRequest::where('id', $id)
                ->where('service_engineer_id', $request->user()->id)
                ->where('status', FollowUpRequest::STATUS_APPROVED)
                ->first();

            if (!$followUpRequest) {
                return response()->json([
                    'message' => 'Follow-up request not found or not assigned to you'
                ], 404);
            }

            // Update the status only
            $followUpRequest->update([
                'status' => FollowUpRequest::STATUS_COMPLETED
            ]);

            return response()->json([
                'message' => 'Follow-up request marked as completed successfully',
                'data' => [
                    'id' => $followUpRequest->id,
                    'follow_up_id' => $followUpRequest->follow_up_id,
                    'status' => $followUpRequest->status,
                    'completed_at' => $followUpRequest->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error completing follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getPatientDetailsByPhone($phoneNumber)
    {
        try {
            // Load the patient with their implants relationship
            $patient = Patient::with('implant')
                ->where('phone_number', $phoneNumber)
                ->select('id', 'name', 'email', 'phone_number')
                ->first();

            if (!$patient) {
                return response()->json([
                    'message' => 'No patient found with this phone number'
                ], 404);
            }

            // Check if patient has an implant registered
            $hasImplant = $patient->implant !== null;

            // Format response data
            $responseData = [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'phone_number' => $patient->phone_number,
                'has_implant' => $hasImplant,
                'implant' => null
            ];

            // Add implant details if they exist
            if ($hasImplant) {
                $responseData['implant'] = [
                    'id' => $patient->implant->id,
                    'ipg_serial_number' => $patient->implant->ipg_serial_number,
                    'ipg_model' => $patient->implant->ipg_model,
                    'device_name' => $patient->implant->device_name,
                    'therapy_name' => $patient->implant->therapy_name,
                    'implantation_date' => $patient->implant->implantation_date,
                    'hospital_name' => $patient->implant->hospital_name,
                    'doctor_name' => $patient->implant->doctor_name
                ];
            }

            return response()->json([
                'message' => 'Patient details retrieved successfully',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving patient details',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Create a payment request for a patient
     * This allows service engineers to notify patients that they need to make a payment
     */

    public function createPatientPaymentRequest(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'amount' => 'required|numeric|min:0',
                'gst_number' => 'nullable|string',
                'pan_number' => 'nullable|string'
            ]);

            // Get patient and service engineer ID
            $patient = Patient::findOrFail($validated['patient_id']);
            $serviceEngineerId = $request->user()->id;

            // Create payment request record
            $payment = Payment::create([
                'patient_id' => $patient->id,
                'service_engineer_id' => $serviceEngineerId, // Store SE ID directly in the table
                'gst_number' => $validated['gst_number'] ?? 'AUTO-GENERATED',
                'pan_number' => $validated['pan_number'] ?? 'AUTO-GENERATED',
                'amount' => $validated['amount'],
                'payment_status' => 'pending', // Pending payment from patient
                'payment_date' => now(),
                'payment_type' => 'follow_up',
                'payment_details' => [
                    'requested_by' => 'service_engineer',
                    'requested_at' => now()->toDateTimeString()
                ]
            ]);

            return response()->json([
                'message' => 'Payment request created successfully for patient',
                'data' => [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'patient_name' => $patient->name,
                    'patient_phone' => $patient->phone_number,
                    'service_engineer_id' => $serviceEngineerId,
                    'status' => 'pending'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating payment request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Service engineer makes a payment on behalf of a patient
     */
    public function makePayment(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'amount' => 'required|numeric|min:0',
                'gst_number' => 'nullable|string',
                'pan_number' => 'nullable|string',
                'payment_details' => 'nullable|array' // Add validation for optional payment details
            ]);

            // Get patient and service engineer ID
            $patient = Patient::findOrFail($validated['patient_id']);
            $serviceEngineerId = $request->user()->id;

            // Prepare payment details
            $paymentDetails = [
                'paid_by' => 'service_engineer',
                'service_engineer_id' => $serviceEngineerId,
                'completed_at' => now()->toDateTimeString()
            ];

            // Merge additional payment details if provided
            if (isset($validated['payment_details'])) {
                $paymentDetails = array_merge($paymentDetails, $validated['payment_details']);
            }

            // Create completed payment record
            $payment = Payment::create([
                'patient_id' => $patient->id,
                'service_engineer_id' => $serviceEngineerId, // Make sure to include this field explicitly
                'gst_number' => $validated['gst_number'] ?? 'AUTO-GENERATED',
                'pan_number' => $validated['pan_number'] ?? 'AUTO-GENERATED',
                'amount' => $validated['amount'],
                'payment_status' => 'completed',
                'payment_date' => now(),
                'payment_type' => 'follow_up',
                'payment_details' => $paymentDetails
            ]);

            return response()->json([
                'message' => 'Payment completed successfully by service engineer',
                'data' => [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'patient_name' => $patient->name,
                    'status' => 'completed',
                    'receipt_number' => 'SE-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createFollowUpRequest(Request $request)
    {
        // Start database transaction
        \DB::beginTransaction();

        try {
            // Validate patient exists
            $patient = Patient::findOrFail($request->patient_id);
            $serviceEngineerId = $request->user()->id;

            // Enhanced duplicate check with locking
            $activeRequest = FollowUpRequest::where('patient_id', $patient->id)
                ->whereIn('status', [
                    FollowUpRequest::STATUS_PENDING,
                    FollowUpRequest::STATUS_APPROVED,
                    FollowUpRequest::STATUS_COMPLETED
                ])
                ->lockForUpdate() // Lock the rows
                ->whereBetween('created_at', [now()->subHours(24), now()]) // Check last 24 hours
                ->first();

            if ($activeRequest) {
                \DB::rollBack();
                return response()->json([
                    'message' => 'A follow-up request already exists for this patient',
                    'follow_up_id' => $activeRequest->follow_up_id,
                    'created_at' => $activeRequest->created_at,
                    'status' => $activeRequest->status
                ], 400);
            }

            // Validate request data
            $validated = $request->validate([
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'doctor_name' => 'required|string',
                'channel_partner' => 'required|string',
                'accompanying_person_name' => 'required|string',
                'accompanying_person_phone' => 'required|string',
                'appointment_datetime' => 'required|date|after:today',
                'reason' => 'required|string',
                'is_paid' => 'required|boolean', // Add validation for paid/free tier flag
                'payment_id' => 'nullable|exists:payments,id' // For pre-existing payments
            ]);

            // Handle payment - either use existing payment or create a new one
            if ($validated['is_paid'] && isset($validated['payment_id'])) {
                // Verify payment exists and belongs to the patient
                $payment = Payment::where('id', $validated['payment_id'])
                    ->where('patient_id', $patient->id)
                    ->where('payment_status', 'completed') // Only allow completed payments
                    ->first();

                if (!$payment) {
                    \DB::rollBack();
                    return response()->json([
                        'message' => 'Invalid payment ID, payment not found, or payment not completed for this patient',
                    ], 400);
                }
            } else if ($validated['is_paid']) {
                // If it's a paid service but no payment ID provided, reject the request
                \DB::rollBack();
                return response()->json([
                    'message' => 'For paid service, a completed payment is required. Please complete payment first.',
                ], 400);
            } else {
                // Create a new payment for free tier service
                $payment = Payment::create([
                    'patient_id' => $patient->id,
                    'service_engineer_id' => $serviceEngineerId,
                    'gst_number' => $request->gst_number ?? 'AUTO-GENERATED',
                    'pan_number' => $request->pan_number ?? 'AUTO-GENERATED',
                    'amount' => 0, // Free tier is always zero
                    'payment_status' => 'completed', // Free tier is always completed
                    'payment_date' => now(),
                    'payment_type' => 'follow_up',
                    'payment_details' => [
                        'auto_generated' => true,
                        'is_free_tier' => true
                    ]
                ]);
            }

            // Create follow-up request (all requests are now completed status)
            $followUpRequest = FollowUpRequest::create([
                'patient_id' => $patient->id,
                'payment_id' => $payment->id,
                'status' => FollowUpRequest::STATUS_COMPLETED,
                'service_engineer_id' => $serviceEngineerId,
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'channel_partner' => $validated['channel_partner'],
                'accompanying_person_name' => $validated['accompanying_person_name'],
                'accompanying_person_phone' => $validated['accompanying_person_phone'],
                'appointment_datetime' => $validated['appointment_datetime'],
                'reason' => $validated['reason']
            ]);

            // Commit transaction
            \DB::commit();

            return response()->json([
                'message' => 'Follow-up request created successfully',
                'data' => [
                    'follow_up_id' => $followUpRequest->follow_up_id,
                    'status' => $followUpRequest->status,
                    'service_engineer' => $followUpRequest->serviceEngineer->name,
                    'payment_status' => $payment->payment_status,
                    'payment_amount' => $payment->amount,
                    'created_at' => $followUpRequest->created_at,
                    'completed_at' => $followUpRequest->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            \DB::rollBack();

            return response()->json([
                'message' => 'Error creating follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function getPaymentStatus($paymentId)
    {
        try {
            // Retrieve the payment record along with the associated patient
            $payment = Payment::with('patient')->findOrFail($paymentId);

            // Check if the payment belongs to the authenticated service engineer
            if (auth()->user()->id != $payment->service_engineer_id) {
                return response()->json([
                    'message' => 'You are not authorized to view this payment',
                    'data' => null
                ], 403);
            }

            // Format payment date
            $paymentDate = $payment->payment_date ? $payment->payment_date->format('Y-m-d H:i:s') : null;

            // Get relevant details from payment_details JSON
            $paymentDetails = $payment->payment_details ?? [];
            $requestedAt = $paymentDetails['requested_at'] ?? null;
            $completedAt = $paymentDetails['completed_at'] ?? null;

            return response()->json([
                'message' => 'Payment status retrieved successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'patient_id' => $payment->patient_id,
                    'patient_name' => $payment->patient->name,
                    'patient_phone' => $payment->patient->phone_number,
                    'amount' => $payment->amount,
                    'payment_status' => $payment->payment_status,
                    'payment_date' => $paymentDate,
                    'payment_type' => $payment->payment_type,
                    'requested_at' => $requestedAt,
                    'completed_at' => $completedAt,
                    'receipt_number' => $payment->payment_status === 'completed' ?
                        'SE-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) : null
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Payment not found',
                'error' => $e->getMessage(),
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving payment status',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }




    //         public function createFollowUpRequest(Request $request)
// {
//     // Start database transaction
//     \DB::beginTransaction();

    //         try {
//         // Validate patient exists
//         $patient = Patient::findOrFail($request->patient_id);
//         $serviceEngineerId = $request->user()->id;

    //             // Enhanced duplicate check with locking
//         $activeRequest = FollowUpRequest::where('patient_id', $patient->id)
//             ->whereIn('status', [
//                 FollowUpRequest::STATUS_PENDING, 
//                 FollowUpRequest::STATUS_APPROVED,
//                 FollowUpRequest::STATUS_COMPLETED
//             ])
//             ->lockForUpdate() // Lock the rows
//             ->whereBetween('created_at', [now()->subHours(24), now()]) // Check last 24 hours
//             ->first();

    //             if ($activeRequest) {
//             \DB::rollBack();
//             return response()->json([
//                 'message' => 'A follow-up request already exists for this patient',
//                 'follow_up_id' => $activeRequest->follow_up_id,
//                 'created_at' => $activeRequest->created_at,
//                 'status' => $activeRequest->status
//             ], 400);
//         }

    //             // Validate request data
//         $validated = $request->validate([
//             'state' => 'required|string',
//             'hospital_name' => 'required|string',
//             'doctor_name' => 'required|string',
//             'channel_partner' => 'required|string',
//             'accompanying_person_name' => 'required|string',
//             'accompanying_person_phone' => 'required|string',
//             'appointment_date' => 'required|date|after:today',
//             'appointment_time' => 'required',
//             'reason' => 'required|string'
//         ]);

    //             // Create payment record
//         $payment = Payment::create([
//             'patient_id' => $patient->id,
//             'gst_number' => $request->gst_number ?? 'AUTO-GENERATED',
//             'pan_number' => $request->pan_number ?? 'AUTO-GENERATED',
//             'amount' => $request->amount ?? 0,
//             'payment_status' => 'completed',
//             'payment_date' => now(),
//             'payment_type' => 'follow_up',
//             'payment_details' => ['auto_generated' => true]
//         ]);

    //             // Create follow-up request
//         $followUpRequest = FollowUpRequest::create([
//             'patient_id' => $patient->id,
//             'payment_id' => $payment->id,
//             'status' => FollowUpRequest::STATUS_COMPLETED,
//             'service_engineer_id' => $serviceEngineerId,
//             'completion_message' => 'Auto-completed by sales representative',
//             ...$validated
//         ]);

    //             // Commit transaction
//         \DB::commit();

    //             return response()->json([
//             'message' => 'Follow-up request created and completed successfully',
//             'data' => [
//                 'follow_up_id' => $followUpRequest->follow_up_id,
//                 'status' => $followUpRequest->status,
//                 'service_engineer' => $followUpRequest->serviceEngineer->name,
//                 'completed_at' => $followUpRequest->created_at
//             ]
//         ], 201);

    //         } catch (\Exception $e) {
//         // Rollback transaction on error
//         \DB::rollBack();

    //             return response()->json([
//             'message' => 'Error creating follow-up request',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }
}
