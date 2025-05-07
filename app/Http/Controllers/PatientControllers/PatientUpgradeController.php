<?php

namespace App\Http\Controllers\PatientControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceUpgrade;
use Illuminate\Support\Facades\Log;

class PatientUpgradeController extends Controller
{
    public function requestUpgrade(Request $request)
    {
        $user = $request->user(); // Assuming authenticated user is the patient
        $requestId = uniqid('req_');

        Log::info("[$requestId] Starting device upgrade request process", [
            'user_id' => $user->id,
            'request_method' => $request->method(),
            'request_ip' => $request->ip()
        ]);

        try {
            $validated = $request->validate([
                // Step 1: Patient fields (Previous Implant Details)
                'old_implantation_date' => 'required|date',
                'old_implant_brand' => 'required|string',
                'old_ipg_model' => 'required|string', // IPG Model Name
                'old_lead_brand' => 'required|string',
                'old_ra_rv_lead_model' => 'required|string',
                'old_csp_catheter_brand' => 'nullable|string',
                'old_csp_lead_model' => 'nullable|string',

                // Step 2: New implant information (Location/Context)
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'doctor_name' => 'required|string',
                'channel_partner' => 'required|string',
            ]);

            // Create upgrade request
            $upgradeRequest = DeviceUpgrade::create([
                'patient_id' => $user->id,
                'old_implantation_date' => $validated['old_implantation_date'],
                'old_implant_brand' => $validated['old_implant_brand'],
                'old_ipg_model' => $validated['old_ipg_model'],
                'old_lead_brand' => $validated['old_lead_brand'],
                'old_ra_rv_lead_model' => $validated['old_ra_rv_lead_model'],
                'old_csp_catheter_brand' => $validated['old_csp_catheter_brand'] ?? null,
                'old_csp_lead_model' => $validated['old_csp_lead_model'] ?? null,
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'channel_partner' => $validated['channel_partner'],
                'status' => 'pending'
            ]);

            Log::info("[$requestId] Device upgrade request created successfully", [
                'upgrade_id' => $upgradeRequest->id
            ]);

            return response()->json([
                'message' => 'Device upgrade request submitted successfully',
                'request_id' => $upgradeRequest->id
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("[$requestId] Validation error creating device upgrade request", [
                'errors' => $e->errors(),
                'user_id' => $user->id
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("[$requestId] Error creating device upgrade request", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error creating device upgrade request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUpgradeRequests(Request $request)
    {
        $user = $request->user();
    
        $latestRequest = DeviceUpgrade::where('patient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
    
        if (!$latestRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No upgrade request found.',
                'data' => null
            ], 404);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Latest upgrade request retrieved successfully.',
            'data' => [
                'id' => $latestRequest->id,
                'status' => $latestRequest->status,
                'old_implantation_date' => $latestRequest->old_implantation_date,
                'old_implant_brand' => $latestRequest->old_implant_brand,
                'old_ipg_model' => $latestRequest->old_ipg_model,
                'old_lead_brand' => $latestRequest->old_lead_brand,
                'old_ra_rv_lead_model' => $latestRequest->old_ra_rv_lead_model,
                'old_csp_catheter_brand' => $latestRequest->old_csp_catheter_brand,
                'old_csp_lead_model' => $latestRequest->old_csp_lead_model,
                'state' => $latestRequest->state,
                'hospital_name' => $latestRequest->hospital_name,
                'doctor_name' => $latestRequest->doctor_name,
                'channel_partner' => $latestRequest->channel_partner,
                'created_at' => $latestRequest->created_at,
                'updated_at' => $latestRequest->updated_at,
            ]
        ], 200);
    }
}