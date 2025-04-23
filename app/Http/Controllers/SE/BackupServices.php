<?php

namespace App\Http\Controllers\SE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupServices extends Controller
{
    /**
     * Get details of a backup service request
     * 
     * @param Request $request
     * @param int $id The backup service ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBackupServiceDetails(Request $request, $id)
    {
        try {
            $serviceEngineerId = $request->user()->id;

            // Get the backup service request with related data
            $backupService = \App\Models\BackupService::with(['patient', 'patient.implant'])
                ->where('id', $id)
                ->where('service_engineer_id', $serviceEngineerId)
                ->first();

            if (!$backupService) {
                return response()->json([
                    'message' => 'Backup service request not found or not assigned to you'
                ], 404);
            }

            // Check if patient has an implant
            $hasImplant = $backupService->patient && $backupService->patient->implant;

            // Build response data
            $responseData = [
                // Appointment Details
                'appointment_details' => [
                    'appointment_datetime' => $backupService->appointment_datetime,
                    'service_type' => $backupService->service_type,
                    'service_duration' => $backupService->service_duration,
                    'status' => $backupService->status
                ],

                // Hospital & Channel Partner Details
                'hospital_details' => [
                    'hospital_name' => $backupService->hospital_name,
                    'state' => $backupService->state,
                    'channel_partner' => $backupService->channel_partner
                ],

                // Patient Details
                'patient_details' => [
                    'patient_name' => $backupService->patient->name,
                    'patient_phone' => $backupService->patient->phone_number,
                    'patient_email' => $backupService->patient->email
                ],

                // Accompanying Person Details
                'accompanying_person_details' => [
                    'name' => $backupService->accompanying_person_name,
                    'phone' => $backupService->accompanying_person_phone
                ]
            ];

            // Add device details if implant exists, otherwise indicate no implant registered
            if ($hasImplant) {
                $responseData['device_details'] = [
                    'therapy_name' => $backupService->patient->implant->therapy_name,
                    'device_name' => $backupService->patient->implant->device_name,
                    'ipg_model' => $backupService->patient->implant->ipg_model,
                    'ipg_model_number' => $backupService->patient->implant->ipg_model_number,
                    'ipg_serial_number' => $backupService->patient->implant->ipg_serial_number
                ];
            } else {
                $responseData['device_details'] = [
                    'error' => 'No implant registered for this patient'
                ];
            }

            return response()->json([
                'message' => 'Backup service request details retrieved successfully',
                'has_implant' => $hasImplant,
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving backup service request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a backup service request as completed
     * 
     * @param Request $request
     * @param int $id The backup service ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeBackupService(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'completion_notes' => 'nullable|string',
                'service_duration' => 'nullable|integer'
            ]);

            $serviceEngineerId = $request->user()->id;

            // Get the backup service request and verify it belongs to this service engineer
            $backupService = \App\Models\BackupService::where('id', $id)
                ->where('service_engineer_id', $serviceEngineerId)
                ->whereIn('status', ['assigned', 'confirmed'])
                ->first();

            if (!$backupService) {
                return response()->json([
                    'message' => 'Backup service request not found, not assigned to you, or not in a valid status'
                ], 404);
            }

            // Update the backup service
            $updateData = [
                'status' => 'completed',
                'completed_at' => now()
            ];

            // Add optional fields if provided
            if (isset($validated['completion_notes'])) {
                $updateData['completion_notes'] = $validated['completion_notes'];
            }

            if (isset($validated['service_duration'])) {
                $updateData['service_duration'] = $validated['service_duration'];
            }

            $backupService->update($updateData);

            return response()->json([
                'message' => 'Backup service request marked as completed successfully',
                'data' => [
                    'id' => $backupService->id,
                    'backup_id' => $backupService->backup_id,
                    'status' => $backupService->status,
                    'completed_at' => $backupService->completed_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error completing backup service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
