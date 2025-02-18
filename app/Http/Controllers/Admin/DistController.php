<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceReplacement;
use App\Models\User;
use App\Models\FollowUpRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DistController extends Controller
{
    public function listAllPendingRequests(Request $request)
    {
        try {
            $requests = DeviceReplacement::with(['patient', 'implant', 'serviceEngineer'])
                ->where('status', DeviceReplacement::STATUS_APPROVED)
                ->whereNull('new_ipg_serial_number')
                ->get();

            if ($requests->isEmpty()) {
                return response()->json([
                    'message' => 'No pending requests found',
                    'data' => []
                ], 200);
            }

            return [
                'id' => $requests->first()->id,
                'patient_name' => $requests->first()->patient->name,
                'hospital_name' => $requests->first()->hospital_name,
                'ticket_type' => $requests->first->replacement_reason ? 'Warranty Replacement' : 'Paid Replacement',
                'status' => DeviceReplacement::STATUS_PENDING,
                'service_engineer' => $requests->first()->serviceEngineer ? $requests->first()->serviceEngineer->name : null,
            ];
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving approved replacement requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listRequests(Request $request)
    {
        try {
            $requests = DeviceReplacement::with(['patient', 'implant', 'serviceEngineer'])
                ->where('status', 'approved') // Add this filter
                ->get()
                ->map(function ($replacement) {
                    // Determine display status for approved requests
                    $status = 'approved';
                    if (empty($replacement->new_ipg_serial_number)) {
                        $status = 'pending';
                    } else {
                        $status = $replacement->service_completed ? 'registered' : 'linked';
                    }

                    return [
                        'id' => $replacement->id,
                        'patient_name' => $replacement->patient->name,
                        'hospital_name' => $replacement->hospital_name,
                        'ticket_type' => $replacement->replacement_reason ? 'Warranty Replacement' : 'Paid Replacement',
                        'status' => ucfirst($status),
                        'service_engineer' => $replacement->serviceEngineer ? $replacement->serviceEngineer->name : null,
                    ];
                });

            return response()->json([
                'message' => 'Approved device replacement requests retrieved successfully',
                'data' => $requests
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving approved replacement requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function listSalesRepresentatives()
    {
        try {
            $salesReps = User::role('sales-representative')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,

                    ];
                });

            return response()->json([
                'message' => 'Sales representatives retrieved successfully',
                'data' => $salesReps
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving sales representatives',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function assignServiceEngineer(Request $request)
    {
        try {
            $validated = $request->validate([
                'replacement_request_id' => 'required|exists:device_replacements,id',
                'service_engineer_id' => [
                    'required',
                    'exists:users,id',
                    Rule::exists('model_has_roles', 'model_id')
                        ->where(function ($query) {
                            $query->where('role_id', function ($subQuery) {
                                $subQuery->select('id')
                                    ->from('roles')
                                    ->where('name', 'sales-representative');
                            });
                        })
                ]
            ]);

            // Check if service engineer is already assigned to another request
            // $existingAssignment = DeviceReplacement::where('service_engineer_id', $validated['service_engineer_id'])
            //     ->where('id', '!=', $validated['replacement_request_id'])
            //     ->first();

            // if ($existingAssignment) {
            //     return response()->json([
            //         'message' => 'Service engineer is already assigned to another replacement request',
            //         'current_assignment' => $existingAssignment->id
            //     ], 400);
            // }

            $replacementRequest = DeviceReplacement::find($validated['replacement_request_id']);
            $replacementRequest->service_engineer_id = $validated['service_engineer_id'];
            $replacementRequest->save();

            return response()->json([
                'message' => 'Service engineer assigned successfully',
                'data' => $replacementRequest
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error assigning service engineer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getReplacementDetails($id)
    {
        try {
            $replacement = DeviceReplacement::with(['patient', 'implant'])
                ->where('id', $id)
                ->first();

            if (!$replacement) {
                return response()->json([
                    'message' => 'Replacement request not found'
                ], 404);
            }

            $details = [
                'name' => $replacement->patient->name,
                'date_of_birth' => $replacement->patient->date_of_birth,
                'gender' => $replacement->patient->gender,
                'email' => $replacement->patient->email,
                'phone_number' => $replacement->patient->phone_number,
                'state' => $replacement->state,                           // Changed from implant->state
                'hospital_name' => $replacement->hospital_name,           // Changed from implant->hospital_name
                'doctor_name' => $replacement->doctor_name,              // Changed from implant->doctor_name
                'channel_partner' => $replacement->channel_partner,       // Changed from implant->channel_partner
                'reason_for_replacement' => $replacement->replacement_reason,
                'planned_replacement_schedule' => Carbon::parse($replacement->planned_replacement_date)->format('Y-m-d H:i:s'),
                'interrogation_report' => $replacement->interrogation_report_path,
                'physician_report' => $replacement->prescription_path,
                'ipg_model_number' => $replacement->implant->ipg_model_number,
                'ipg_model_name' => $replacement->implant->ipg_model
            ];

            return response()->json([
                'message' => 'Replacement details retrieved successfully',
                'data' => $details
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving replacement details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function assignNewIpgSerialNumber(Request $request)
    {
        try {
            $validated = $request->validate([
                'replacement_request_id' => 'required|exists:device_replacements,id',
                'new_ipg_serial_number' => [
                    'required',
                    'string',
                    Rule::unique('implants', 'ipg_serial_number'),
                ],
                'new_ipg_model_number' => [
                    'required',
                    'string',
                    // Add any additional validation rules for the model number if necessary
                ]
            ]);

            // Retrieve the replacement with its implant
            $replacement = DeviceReplacement::with('implant')->find($validated['replacement_request_id']);

            if (!$replacement || !$replacement->implant) {
                return response()->json([
                    'message' => 'Replacement request or associated implant not found'
                ], 404);
            }

            \DB::beginTransaction();

            try {
                $oldImplant = $replacement->implant;
                $replacement->new_ipg_serial_number = $validated['new_ipg_serial_number'];
                $replacement->save(); // Make sure to save the changes
                $oldSerial = $oldImplant->ipg_serial_number;
                $patientId = $oldImplant->patient_id;

                // First, deactivate ALL implants for this patient, regardless of current status
                \App\Models\Implant::where('patient_id', $patientId)
                    ->update(['active' => false]);

                // Double verify the old implant is inactive
                $oldImplant->refresh();
                $oldImplant->update(['active' => false]);

                // Create new implant record
                $newImplant = $oldImplant->replicate();
                $newImplant->ipg_serial_number = $validated['new_ipg_serial_number'];
                $newImplant->ipg_model_number = $validated['new_ipg_model_number']; // Assign the new IPG model number
                $newImplant->active = true;
                $newImplant->save();

                // Verify only one active implant exists for the patient
                $activeImplantCount = \App\Models\Implant::where('patient_id', $patientId)
                    ->where('active', true)
                    ->count();

                if ($activeImplantCount !== 1) {
                    throw new \Exception('Inconsistent implant activation state detected');
                }

                \DB::commit();

                return response()->json([
                    'message' => 'IPG serial number updated successfully',
                    'old_ipg_serial_number' => $oldSerial,
                    'new_ipg_serial_number' => $newImplant->ipg_serial_number,
                    'new_ipg_model_number' => $newImplant->ipg_model_number, // Return the new IPG model number
                    'implant_id' => $newImplant->id,
                    'active_implants_count' => $activeImplantCount
                ], 200);

            } catch (\Exception $innerException) {
                \DB::rollback();
                throw $innerException;
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating IPG serial number',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllActionables()
    {
        try {
            // Get pending replacement requests with timestamps
            $replacementRequests = DeviceReplacement::with(['patient', 'implant', 'serviceEngineer'])
                ->where('status', DeviceReplacement::STATUS_APPROVED)
                ->whereNull('new_ipg_serial_number')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'request_type' => 'replacement',
                        'patient_name' => $request->patient->name,
                        'hospital_name' => $request->hospital_name,
                        'ticket_type' => $request->replacement_reason ? 'Warranty Replacement' : 'Paid Replacement',
                        'status' => 'Pending',
                        'service_engineer' => $request->serviceEngineer ? $request->serviceEngineer->name : null,
                        'created_at' => $request->created_at->toDateTimeString() // Ensure consistent date format
                    ];
                });

            // Get pending follow-up requests with timestamps
            $followUpRequests = FollowUpRequest::with(['patient'])
                ->where('status', FollowUpRequest::STATUS_PENDING)
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'request_type' => 'follow-up',
                        'patient_name' => $request->patient->name,
                        'hospital_name' => $request->hospital_name,
                        'ticket_type' => 'Follow-up Service',
                        'status' => 'Pending',
                        'service_engineer' => null,
                        'created_at' => $request->created_at->toDateTimeString() // Ensure consistent date format
                    ];
                });

            // Convert both collections to arrays and merge them
            $allRequests = array_merge($replacementRequests->toArray(), $followUpRequests->toArray());

            // Sort the merged array by created_at
            usort($allRequests, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'message' => 'Actionable requests retrieved successfully',
                'data' => [
                    'total_actionables' => count($allRequests),
                    'replacement_requests' => $replacementRequests->count(),
                    'follow_up_requests' => $followUpRequests->count(),
                    'requests' => $allRequests
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving actionable requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}