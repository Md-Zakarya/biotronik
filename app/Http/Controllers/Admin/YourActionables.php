<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupService;
use App\Services\S3StorageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class YourActionables extends Controller
{
    protected $s3StorageService;

    public function __construct(S3StorageService $s3StorageService)
    {
        $this->s3StorageService = $s3StorageService;
    }

    

    

    
    public function getBackupServiceDetails($id)
    {
        try {
            $backupService = BackupService::with(['patient', 'serviceEngineer'])
                ->where('id', $id)
                ->first();

            if (!$backupService) {
                return response()->json([
                    'message' => 'Backup service request not found'
                ], 404);
            }

            $details = [
                'id' => $backupService->id,
                'backup_id' => $backupService->backup_id,
                'patient' => [
                    'id' => $backupService->patient->id,
                    'name' => $backupService->patient->name,
                    'phone_number' => $backupService->patient->phone_number,
                    'email' => $backupService->patient->email,
                    'patient_photo' => $backupService->patient->patient_photo ? $this->s3StorageService->getFileUrl($backupService->patient->patient_photo) : null,
                    'date_of_birth' => $backupService->patient->date_of_birth,
                    'gender' => $backupService->patient->gender,
                ],
                'service_engineer' => $backupService->serviceEngineer ? [
                    'id' => $backupService->serviceEngineer->id,
                    'name' => $backupService->serviceEngineer->name,
                ] : null,
                'state' => $backupService->state,
                'hospital_name' => $backupService->hospital_name,
                'channel_partner' => $backupService->channel_partner,
                'appointment_datetime' => $backupService->appointment_datetime,
                'service_type' => $backupService->service_type,
                'service_duration' => $backupService->service_duration,
                'status' => $backupService->status,
                'accompanying_person_name' => $backupService->accompanying_person_name,
                'accompanying_person_phone' => $backupService->accompanying_person_phone,
                'created_at' => $backupService->created_at,
                'updated_at' => $backupService->updated_at,
            ];

            return response()->json([
                'message' => 'Backup service request details retrieved successfully',
                'data' => $details
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving backup service request details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function assignServiceEngineer(Request $request, $id)
    {
        try {
            $validated = $request->validate([
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

            $backupService = BackupService::findOrFail($id);

            if ($backupService->status !== 'pending') {
                return response()->json([
                    'message' => 'Cannot assign service engineer. Backup service request is not in pending status.'
                ], 400);
            }

            // Update the backup service with the assigned service engineer
            $backupService->update([
                'service_engineer_id' => $validated['service_engineer_id']
            ]);

            return response()->json([
                'message' => 'Service engineer assigned successfully',
                'data' => [
                    'backup_service_id' => $backupService->id,
                    'service_engineer_id' => $backupService->service_engineer_id,
                    'status' => $backupService->status
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error assigning service engineer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmBackupService($id)
    {
        try {
            $backupService = BackupService::with('serviceEngineer')->findOrFail($id);

            // Check if service engineer is assigned
            if (!$backupService->service_engineer_id) {
                return response()->json([
                    'message' => 'Cannot confirm backup service. Service engineer must be assigned first.'
                ], 400);
            }

            // Check if status is assigned
            if ($backupService->status !== 'pending') {
                return response()->json([
                    'message' => 'Cannot confirm backup service. Service must be in assigned status.'
                ], 400);
            }

            // Update the status to confirmed
            $backupService->update([
                'status' => 'confirmed'
            ]);

            return response()->json([
                'message' => 'Backup service confirmed successfully',
                'data' => [
                    'backup_service_id' => $backupService->id,
                    'status' => $backupService->status,
                    'service_engineer' => [
                        'id' => $backupService->serviceEngineer->id,
                        'name' => $backupService->serviceEngineer->name
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error confirming backup service',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}