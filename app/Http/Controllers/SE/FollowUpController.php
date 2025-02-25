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
            $validated = $request->validate([
                'completion_message' => 'string|max:1000',
            ]);

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

            // Update the status and completion message
            $followUpRequest->update([
                'status' => FollowUpRequest::STATUS_COMPLETED,
                'completion_message' => $validated['completion_message']
            ]);

            return response()->json([
                'message' => 'Follow-up request marked as completed successfully',
                'data' => [
                    'id' => $followUpRequest->id,
                    'follow_up_id' => $followUpRequest->follow_up_id,
                    'status' => $followUpRequest->status,
                    'completion_message' => $followUpRequest->completion_message,
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
            $patient = Patient::where('phone_number', $phoneNumber)
                ->select('id', 'name', 'email', 'phone_number')
                ->first();

            if (!$patient) {
                return response()->json([
                    'message' => 'No patient found with this phone number'
                ], 404);
            }

            return response()->json([
                'message' => 'Patient details retrieved successfully',
                'data' => $patient
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving patient details',
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
            'appointment_date' => 'required|date|after:today',
            'appointment_time' => 'required',
            'reason' => 'required|string'
        ]);

        // Create payment record
        $payment = Payment::create([
            'patient_id' => $patient->id,
            'gst_number' => $request->gst_number ?? 'AUTO-GENERATED',
            'pan_number' => $request->pan_number ?? 'AUTO-GENERATED',
            'amount' => $request->amount ?? 0,
            'payment_status' => 'completed',
            'payment_date' => now(),
            'payment_type' => 'follow_up',
            'payment_details' => ['auto_generated' => true]
        ]);

        // Create follow-up request
        $followUpRequest = FollowUpRequest::create([
            'patient_id' => $patient->id,
            'payment_id' => $payment->id,
            'status' => FollowUpRequest::STATUS_COMPLETED,
            'service_engineer_id' => $serviceEngineerId,
            'completion_message' => 'Auto-completed by sales representative',
            ...$validated
        ]);

        // Commit transaction
        \DB::commit();

        return response()->json([
            'message' => 'Follow-up request created and completed successfully',
            'data' => [
                'follow_up_id' => $followUpRequest->follow_up_id,
                'status' => $followUpRequest->status,
                'service_engineer' => $followUpRequest->serviceEngineer->name,
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
}
