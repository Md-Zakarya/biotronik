<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceReplacement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DistController extends Controller
{
    // public function listRequests(Request $request)
    // {
    //     try {
    //         $requests = DeviceReplacement::with(['patient', 'implant'])
    //             ->get()
    //             ->map(function ($request) {
    //                 return [
    //                     'id' => $request->id,
    //                     'patient_name' => $request->patient->name,
    //                     'hospital_name' => $request->hospital_name,
    //                     'ticket_type' => $request->replacement_reason ? 'Warranty Replacement' : 'Paid Replacement',
    //                     'status' => ucfirst($request->status), // Capitalizes first letter: pending -> Pending
    //                     'service_engineer' => $request->serviceEngineer ? $request->serviceEngineer->name : null
    //                 ];
    //             });

    //         return response()->json([
    //             'message' => 'Device replacement requests retrieved successfully',
    //             'data' => $requests
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error retrieving device replacement requests',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

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
            $existingAssignment = DeviceReplacement::where('service_engineer_id', $validated['service_engineer_id'])
                ->where('id', '!=', $validated['replacement_request_id'])
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'message' => 'Service engineer is already assigned to another replacement request',
                    'current_assignment' => $existingAssignment->id
                ], 400);
            }

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
                'planned_replacement_schedule' => $replacement->planned_replacement_date,
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


}