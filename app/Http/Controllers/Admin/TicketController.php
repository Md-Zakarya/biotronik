<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceReplacement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function getAllReplacementRequests()
    {
        try {
            $replacementRequests = DeviceReplacement::with(['patient', 'implant'])->get()->map(function ($request) {
                return [
                    'patient_name' => $request->patient->name,
                    'id' => $request->id,
                    'address' => $request->patient->address,
                    'ticket_type' => 'Warranty replacement'
                ];
            });

            return response()->json([
                'message' => 'Replacement requests retrieved successfully',
                'data' => $replacementRequests
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving replacement requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingReplacementRequests()
    {
        try {
            $pendingRequests = DeviceReplacement::with(['patient', 'implant'])
                ->where('status', 'pending')
                ->get()
                ->map(function ($request) {
                    return [
                        'patient_name' => $request->patient->name,
                        'id' => $request->id,
                        'address' => $request->patient->address,
                        'ticket_type' => 'Warranty replacement'
                    ];
                });

            return response()->json([
                'message' => 'Pending replacement requests retrieved successfully',
                'data' => $pendingRequests
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving pending replacement requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getReplacementRequestDetails($id)
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

            // $data = [
            //     'name' => $replacementRequest->patient->name,
            //     'date_of_birth' => $replacementRequest->patient->date_of_birth,
            //     'gender' => $replacementRequest->patient->gender,
            //     'email' => $replacementRequest->patient->email,
            //     'phone_number' => $replacementRequest->patient->phone_number,
            //     'state_of_implant_registration' => $replacementRequest->implant->state,
            //     'hospital_name' => $replacementRequest->implant->hospital_name,
            //     'doctor_name' => $replacementRequest->implant->doctor_name,
            //     'channel_partner_name' => $replacementRequest->implant->channel_partner,
            //     'reason_for_replacement' => $replacementRequest->replacement_reason,
            //     'planned_replacement_schedule' => $replacementRequest->planned_replacement_date,
            //     'interrogation_report' => $replacementRequest->interrogation_report_path,
            //     'physician_report' => $replacementRequest->prescription_path,
            //     'ipg_model_number' => $replacementRequest->implant->ipg_model_number,
            //     'ipg_model_name' => $replacementRequest->implant->ipg_model,
            // ];

            $data = [
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
                'message' => 'Replacement request details retrieved successfully',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving replacement request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function decideReplacementRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'service_charge' => 'nullable|numeric',
            'rejection_reason' => 'nullable|string'
        ]);

        $replacementRequest = DeviceReplacement::find($id);
        if (!$replacementRequest) {
            return response()->json([
                'message' => 'Replacement request not found'
            ], 404);
        }

        if ($validated['status'] === 'approved') {
            $replacementRequest->status = 'approved';
            $replacementRequest->service_charge = $validated['service_charge'] ?? 0;
        } else {
            $replacementRequest->status = 'rejected';
            $replacementRequest->rejection_reason = $validated['rejection_reason'] ?? 'No reason provided';
        }

        $replacementRequest->save();

        return response()->json([
            'message' => 'Replacement request updated successfully',
            'data' => $replacementRequest
        ], 200);
    }


}