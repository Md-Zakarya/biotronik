<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceReplacement;
use App\Models\User;
use App\Models\FollowUpRequest;
use App\Models\PendingImplant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DistController extends Controller
{   public function getDashboardCounts()
    {
        try {
            // Get pending replacement requests count
            $pendingReplacementsCount = DeviceReplacement::where('status', 'approved')->count();
            
            // Get pending implants count
            $pendingImplantsCount = PendingImplant::where('status', 'pending')->count();
            
            // Get pending follow-up requests
            $pendingFollowUpsCount = FollowUpRequest::where('status', 'pending')->count();
            
            // Total actionables
            $totalActionables = $pendingReplacementsCount + $pendingImplantsCount + $pendingFollowUpsCount;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_actionables' => $totalActionables,
                    'pending_replacements' => $pendingReplacementsCount,
                    'pending_implants' => $pendingImplantsCount,
                    'pending_follow_ups' => $pendingFollowUpsCount
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching dashboard counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function getPendingImplantDetails($id)
    {
        try {
            $pendingImplant = PendingImplant::with('patient')
                ->where('id', $id)
                ->first();

            if (!$pendingImplant) {
                return response()->json([
                    'message' => 'Pending implant not found'
                ], 404);
            }

            $details = [
                'id' => $pendingImplant->id,
                'patient' => [
                    'id' => $pendingImplant->patient->id,
                    'name' => $pendingImplant->patient->name,
                    'date_of_birth' => $pendingImplant->patient->date_of_birth,
                    'gender' => $pendingImplant->patient->gender,
                    'email' => $pendingImplant->patient->email,
                    'phone_number' => $pendingImplant->patient->phone_number,
                    'address' => $pendingImplant->patient->address,
                    'state' => $pendingImplant->patient->state, 
                    'city' => $pendingImplant->patient->city,
                    'pincode' => $pendingImplant->patient->pin_code,
                ],
                'implant' => [
                    'pre_feb_2022' => $pendingImplant->pre_feb_2022,
                    'ipg_serial_number' => $pendingImplant->ipg_serial_number,
                    'implantation_date' => $pendingImplant->implantation_date,
                    'ipg_model' => $pendingImplant->ipg_model,
                    'ipg_model_number' => $pendingImplant->ipg_model_number,
                    'hospital_state' => $pendingImplant->hospital_state,
                    'hospital_name' => $pendingImplant->hospital_name,
                    'doctor_name' => $pendingImplant->doctor_name,
                    'channel_partner' => $pendingImplant->channel_partner,
                    'therapy_name' => $pendingImplant->therapy_name,
                    'device_name' => $pendingImplant->device_name,
                    'ra_rv_leads' => $pendingImplant->ra_rv_leads,
                    'has_ra_rv_lead' => $pendingImplant->has_ra_rv_lead,
                    'has_extra_lead' => $pendingImplant->has_extra_lead,
                    'csp_lead_model' => $pendingImplant->csp_lead_model,
                    'csp_catheter_model' => $pendingImplant->csp_catheter_model,
                    'csp_lead_serial' => $pendingImplant->csp_lead_serial,
                    'lead_brand' => $pendingImplant->lead_brand,
                ],
                'documents' => [
                    'patient_id_card' => $pendingImplant->patient_id_card,
                    'warranty_card' => $pendingImplant->warranty_card,
                    'interrogation_report' => $pendingImplant->interrogation_report,
                ],
                'status' => $pendingImplant->status,
            ];

            return response()->json([
                'message' => 'Pending implant details retrieved successfully',
                'data' => $details
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving pending implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approvePendingImplant(Request $request, $id)
    {
        try {
            \DB::beginTransaction();

            $pendingImplant = PendingImplant::with('patient')
                ->where('id', $id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingImplant) {
                return response()->json([
                    'message' => 'Pending implant not found or already processed'
                ], 404);
            }

            // Create a new implant record from the pending data
            $implantData = [
                'patient_id' => $pendingImplant->patient_id,
                'pre_feb_2022' => $pendingImplant->pre_feb_2022,
                'ipg_serial_number' => $pendingImplant->ipg_serial_number,
                'implantation_date' => $pendingImplant->implantation_date,
                'ipg_model' => $pendingImplant->ipg_model,
                'ipg_model_number' => $pendingImplant->ipg_model_number,
                'hospital_state' => $pendingImplant->hospital_state,
                'hospital_name' => $pendingImplant->hospital_name,
                'doctor_name' => $pendingImplant->doctor_name,
                'channel_partner' => $pendingImplant->channel_partner,
                'therapy_name' => $pendingImplant->therapy_name,
                'device_name' => $pendingImplant->device_name,
                'ra_rv_leads' => $pendingImplant->ra_rv_leads,
                'has_ra_rv_lead' => $pendingImplant->has_ra_rv_lead,
                'has_extra_lead' => $pendingImplant->has_extra_lead,
                'csp_lead_model' => $pendingImplant->csp_lead_model,
                'csp_catheter_model' => $pendingImplant->csp_catheter_model,
                'csp_lead_serial' => $pendingImplant->csp_lead_serial,
                'patient_id_card' => $pendingImplant->patient_id_card,
                'warranty_card' => $pendingImplant->warranty_card,
                'interrogation_report' => $pendingImplant->interrogation_report,
                'lead_brand' => $pendingImplant->lead_brand,
                'secret_key' => \Illuminate\Support\Str::random(16),
                'warranty_expired_at' => Carbon::parse($pendingImplant->implantation_date)->addYear(),
                'active' => true
            ];

            // Deactivate any existing implants for this patient
            \App\Models\Implant::where('patient_id', $pendingImplant->patient_id)
                ->update(['active' => false]);

            // Create the new implant
            $implant = \App\Models\Implant::create($implantData);

            // Update pending implant status
            $pendingImplant->status = 'approved';
            $pendingImplant->save();

            \DB::commit();

            return response()->json([
                'message' => 'Implant approved and created successfully',
                'data' => [
                    'implant_id' => $implant->id,
                    'ipg_serial_number' => $implant->ipg_serial_number,
                    'secret_key' => $implant->secret_key
                ]
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Error approving pending implant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectPendingImplant(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500'
            ]);

            $pendingImplant = PendingImplant::where('id', $id)
                ->where('status', 'pending')
                ->first();

            if (!$pendingImplant) {
                return response()->json([
                    'message' => 'Pending implant not found or already processed'
                ], 404);
            }

            // Update status and add rejection reason
            $pendingImplant->status = 'rejected';
            $pendingImplant->rejection_reason = $validated['rejection_reason'];
            $pendingImplant->save();

            return response()->json([
                'message' => 'Pending implant rejected successfully',
                'data' => [
                    'id' => $pendingImplant->id,
                    'status' => 'rejected',
                    'rejection_reason' => $pendingImplant->rejection_reason
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error rejecting pending implant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function listAllPendingItems(Request $request)
    {
        try {
            // Get pending replacement requests
            $replacementRequests = DeviceReplacement::with(['patient', 'implant', 'serviceEngineer'])
                ->where('status', DeviceReplacement::STATUS_PENDING)
                ->get()
                ->map(function ($replacement) {
                    return [
                        'id' => $replacement->id,
                        'type' => 'replacement',
                        'patient_id' => $replacement->patient->id, // Added patient ID
                        'patient_name' => $replacement->patient->name,
                        'hospital_name' => $replacement->hospital_name,
                        'hospital_address' => $replacement->state, // Added hospital state/address
                        'ticket_type' => $replacement->replacement_reason ? 'Warranty Replacement' : 'Paid Replacement',
                        'status' => 'Pending',
                        'service_engineer' => $replacement->serviceEngineer ? $replacement->serviceEngineer->name : null,
                    ];
                });

            // Get pending implants
            $pendingImplants = PendingImplant::with('patient')
                ->where('status', 'pending')
                ->get()
                ->map(function ($implant) {
                    return [
                        'id' => $implant->id,
                        'type' => 'implant',
                        'patient_id' => $implant->patient->id, // Added patient ID
                        'patient_name' => $implant->patient->name,
                        'hospital_name' => $implant->hospital_name,
                        'hospital_address' => $implant->hospital_state, // Added hospital state/address
                        'ticket_type' => 'Old Implant Registration',
                        'status' => 'Pending',
                        'service_engineer' => null,
                    ];
                });

            // Combine both collections
            $allPendingItems = $replacementRequests->concat($pendingImplants);

            // Sort by created_at (newest first)
            $sortedItems = $allPendingItems->sortByDesc('created_at')->values();

            if ($sortedItems->isEmpty()) {
                return response()->json([
                    'message' => 'No pending items found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'message' => 'Pending items retrieved successfully',
                'data' => $sortedItems,
                'counts' => [
                    'total' => $sortedItems->count(),
                    'replacements' => $replacementRequests->count(),
                    'implants' => $pendingImplants->count()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving pending items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
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