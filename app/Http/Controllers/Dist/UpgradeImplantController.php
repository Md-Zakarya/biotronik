<?php

namespace App\Http\Controllers\Dist;

use App\Http\Controllers\Controller;
use App\Models\DeviceUpgrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\S3StorageService;


class UpgradeImplantController extends Controller
{
    public function getPendingUpgrades()
    {
        $upgrades = DeviceUpgrade::with(['patient'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'pending_upgrades' => $upgrades
        ]);
    }

    public function assignServiceEngineer(Request $request, $id)
    {
        $validated = $request->validate([
            'service_engineer_id' => [
                'required',
                'exists:users,id',
                Rule::exists('model_has_roles', 'model_id')
                    ->where(function ($query) {
                        $query->where('role_id', function ($subQuery) {
                            $subQuery->select('id')
                                ->from('roles')
                                ->where('name', 'sales-representative'); // Ensure this role name is correct
                        });
                    })
            ]
        ]);

        try {
            DB::beginTransaction();

            $upgrade = DeviceUpgrade::findOrFail($id);

            if ($upgrade->status !== 'pending') {
                return response()->json([
                    'message' => 'This upgrade request is already assigned or processed'
                ], 400);
            }

            $upgrade->service_engineer_id = $validated['service_engineer_id'];
            $upgrade->status = 'assigned';
            $upgrade->save();

            DB::commit();

            // Optionally: Notify assigned Service Engineer here

            return response()->json([
                'message' => 'Service engineer assigned successfully',
                'upgrade' => $upgrade->load('patient', 'serviceEngineer') // Load relationships for response
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error assigning service engineer to upgrade request', [
                'upgrade_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error assigning service engineer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getPendingUpgradeDetails($id)
    {
        $upgrade = DeviceUpgrade::with(['patient'])
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$upgrade) {
            return response()->json([
                'message' => 'Pending upgrade-implant request not found'
            ], 404);
        }

        $patient = $upgrade->patient;
        $s3Service = app(S3StorageService::class);

        return response()->json([
            'id' => $upgrade->id,
                'name' => $patient->name ?? null,
                'date_of_birth' => $patient->date_of_birth ?? null,
                'gender' => $patient->gender ?? null,
                'email' => $patient->email ?? null,
                'phone_number' => $patient->phone_number ?? null,
                'patient_photo' => $patient->patient_photo
                    ? $s3Service->getFileUrl($patient->patient_photo)
                    : null,          
                'state' => $upgrade->state,
                'hospital_name' => $upgrade->hospital_name,
                'doctor_name' => $upgrade->doctor_name,
                'channel_partner' => $upgrade->channel_partner,
                'old_implantation_date' => $upgrade->old_implantation_date,
                'old_implant_brand' => $upgrade->old_implant_brand,
                'old_ipg_model' => $upgrade->old_ipg_model,
                'old_lead_brand' => $upgrade->old_lead_brand,
                'old_ra_rv_lead_model' => $upgrade->old_ra_rv_lead_model,
                'old_csp_catheter_brand' => $upgrade->old_csp_catheter_brand,
                'old_csp_lead_model' => $upgrade->old_csp_lead_model,
                'is_csp_implant' => !empty($upgrade->old_csp_catheter_brand),
                // New implant fields (may be null for pending)
                'new_implantation_date' => $upgrade->new_implantation_date,
                'new_ipg_serial_number' => $upgrade->new_ipg_serial_number,
                'new_ipg_model' => $upgrade->new_ipg_model,
                'new_ipg_model_number' => $upgrade->new_ipg_model_number,
                'new_therapy_name' => $upgrade->new_therapy_name,
                'new_device_name' => $upgrade->new_device_name,
                'new_ra_rv_leads' => $upgrade->new_ra_rv_leads,
                'new_csp_catheter_model' => $upgrade->new_csp_catheter_model,
                'new_csp_lead_model' => $upgrade->new_csp_lead_model,
                'new_csp_lead_serial' => $upgrade->new_csp_lead_serial,
            'status' => $upgrade->status,
            'created_at' => $upgrade->created_at,
            'updated_at' => $upgrade->updated_at,
        ]);
    }
}