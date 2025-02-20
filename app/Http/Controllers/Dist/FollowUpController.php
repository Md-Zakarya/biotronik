<?php

namespace App\Http\Controllers\Dist;

use App\Http\Controllers\Controller;
use App\Models\FollowUpRequest;
use Illuminate\Http\Request;
use App\Events\FollowUpStatusChanged;
use App\Events\FollowUpCompleted;
use Illuminate\Support\Facades\Schema;

class FollowUpController extends Controller
{
    /**
     * Get follow-up request status for distributor
     */
    public function getFollowUpStatus(Request $request, $id)
    {
        try {
            $followUp = FollowUpRequest::with([
                'patient:id,name,phone_number,email,date_of_birth,gender',
                'serviceEngineer:id,name,phonenumber'
            ])->findOrFail($id);

            return response()->json([
                'status' => $followUp->status,
                'follow_up_details' => [
                    'hospital_name' => $followUp->hospital_name,
                    'doctor_name' => $followUp->doctor_name,
                    'appointment_datetime' => $followUp->appointment_datetime,

                    'reason' => $followUp->reason,
                    'state' => $followUp->state,
                    'channel_partner' => $followUp->channel_partner,
                    'accompanying_person_name' => $followUp->accompanying_person_name,
                    'accompanying_person_phone' => $followUp->accompanying_person_phone,
                ],
                'patient' => $followUp->patient,
                'service_engineer' => $followUp->serviceEngineer,
             
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending follow-up requests for distributor
     */
    public function getFollowUpRequests(Request $request)
{
    try {
        if (!Schema::hasTable('follow_up_requests')) {
            return response()->json([
                'message' => 'System configuration error',
                'error' => 'Required table not found'
            ], 500);
        }

        $requests = FollowUpRequest::select([
            'id', 
            'hospital_name',
            'patient_id',
            'service_engineer_id',
            'created_at',
            'status'
        ])
        ->with([
            'patient' => function($query) {
                $query->select('id', 'name', 'phone_number', 'email');
            },
            'serviceEngineer' => function($query) {
                $query->select('id', 'name', 'phonenumber');
            }
        ])
        // ->where('status', 'pending')
        ->orderBy('created_at', direction: 'desc')
        ->paginate(10);

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => 'No pending follow-up requests found',
                'requests' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_records' => 0,
                    'per_page' => 10
                ]
            ], 200);
        }

        $formattedRequests = $requests->map(function ($request) {
            return [
                'serial_no' => $request->id,
                'patient_name' => $request->patient ? $request->patient->name : null,
                'hospital_name' => $request->hospital_name,
                'service_engineer' => $request->serviceEngineer ? $request->serviceEngineer->name : 'Not assigned',
                'registration_type' => 'Follow-up Service', 
                'status' => $request->status,
            ];
        });

        return response()->json([
            'message' => 'Follow-up requests retrieved successfully',
            'requests' => $formattedRequests,
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'total_pages' => $requests->lastPage(),
                'total_records' => $requests->total(),
                'per_page' => $requests->perPage()
            ]
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error retrieving follow-up requests: ' . $e->getMessage());
        return response()->json([
            'message' => 'Error retrieving follow-up requests',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Assign service engineer to follow-up request
     */
    public function assignServiceEngineer(Request $request, $id)
    {
        $validated = $request->validate([
            'service_engineer_id' => 'required|exists:users,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        try {
            $followUp = FollowUpRequest::findOrFail($id);

            $followUp->update([
                'service_engineer_id' => $validated['status'] === 'approved' ? $validated['service_engineer_id'] : null,
                'status' => $validated['status'],
                'rejection_reason' => $validated['rejection_reason'] ?? null
            ]);

            return response()->json([
                'message' => 'Follow-up request ' . $validated['status'] . ' successfully',
                'status' => $validated['status'],
                'service_engineer_id' => $followUp->service_engineer_id,
                'rejection_reason' => $followUp->rejection_reason
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assigned requests for service engineer
     */
    public function getAssignedRequests(Request $request)
    {
        try {
            $requests = FollowUpRequest::with([
                'patient:id,name,phone,email',
                'patient.implant:id,patient_id,ipg_model,ipg_serial_number,therapy_type'
            ])
                ->where('service_engineer_id', $request->user()->id)
                ->where('status', 'approved')
                ->orderBy('appointment_date', 'asc')
                ->paginate(10);

            return response()->json([
                'requests' => $requests->items(),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'total_pages' => $requests->lastPage(),
                    'total_records' => $requests->total(),
                    'per_page' => $requests->perPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving assigned requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark follow-up request as complete
     */
    public function markAsComplete(Request $request, $id)
    {
        $validated = $request->validate([
            'completion_message' => 'required|string'
        ]);

        try {
            $followUp = FollowUpRequest::where('id', $id)
                ->where('service_engineer_id', $request->user()->id)
                ->firstOrFail();

            $followUp->update([
                'status' => 'completed',
                'completion_message' => $validated['completion_message']
            ]);

            return response()->json([
                'message' => 'Follow-up request marked as complete',
                'status' => 'completed',
                'completion_message' => $validated['completion_message'],
                'completed_at' => $followUp->updated_at
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error completing follow-up request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}