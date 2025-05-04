<?php

namespace App\Http\Controllers\SE;

use App\Http\Controllers\Controller;
use App\Models\DeviceUpgrade;
use App\Models\Implant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpgradeImplantController extends Controller
{

    public function getUpgradeDetails($id, Request $request)
    {
        $engineer = $request->user();

        $upgrade = DeviceUpgrade::with(['patient'])
            ->where('id', $id)
            ->where('service_engineer_id', $engineer->id)
            ->firstOrFail();

        return response()->json([
            'upgrade' => $upgrade
        ]);
    }

    public function completeUpgrade(Request $request, $id)
    {
        $engineer = $request->user();

        $validated = $request->validate([
            'new_implantation_date' => 'required|date|before_or_equal:today',
            'new_ipg_serial_number' => [
                'required',
                'string',
                Rule::unique('implants', 'ipg_serial_number')
            ],
            'new_ipg_model' => 'required|string',
            'new_ipg_model_number' => 'required|string',
            'new_therapy_name' => 'required|string',
            'new_device_name' => 'required|string',
            'new_ra_rv_leads' => 'required|array|min:1',
            'new_ra_rv_leads.*.model' => 'required|string',
            'new_ra_rv_leads.*.serial' => 'required|string',
            'new_csp_catheter_model' => 'nullable|string',
            'new_csp_lead_model' => 'nullable|string',
            'new_csp_lead_serial' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $upgrade = DeviceUpgrade::where('id', $id)
                ->where('service_engineer_id', $engineer->id)
                ->where('status', 'assigned')
                ->firstOrFail();

            $patientId = $upgrade->patient_id;

            // 1. Create a record of the old implant in the implant table (inactive)
            Implant::create([
                'patient_id' => $patientId,
                'implantation_date' => $upgrade->old_implantation_date,
                'pre_feb_2022' => true,
                'hospital_state' => $upgrade->state,
                'hospital_name' => $upgrade->hospital_name,
                'doctor_name' => $upgrade->doctor_name,
                'channel_partner' => $upgrade->channel_partner,
                'therapy_name' => null,
                'device_name' => null,
                'ipg_model' => $upgrade->old_ipg_model,
                'ipg_model_number' => null,
                'ipg_serial_number' => "old_implant_serial_number",
                'has_ra_rv_lead' => true,
                'ra_rv_leads' => [
                    [
                        'model' => $upgrade->old_ra_rv_lead_model,
                        'serial' => null
                    ]
                ],
                'csp_catheter_model' => $upgrade->old_csp_catheter_brand,
                'csp_lead_serial' => $upgrade->old_csp_lead_model,
                'secret_key' => Str::random(16),
                'warranty_expired_at' => null,
                'user_id' => $engineer->id,
                'active' => false,
                'implant_brand' => $upgrade->old_implant_brand
            ]);

            // 2. Deactivate all existing implants for this patient
            Implant::where('patient_id', $patientId)
                ->update(['active' => false]);

            // 3. Create new implant record (active)
            $newSecretKey = Str::random(16);
            $newImplant = Implant::create([
                'patient_id' => $patientId,
                'implantation_date' => $validated['new_implantation_date'],
                'pre_feb_2022' => false,
                'hospital_state' => $upgrade->state,
                'hospital_name' => $upgrade->hospital_name,
                'doctor_name' => $upgrade->doctor_name,
                'channel_partner' => $upgrade->channel_partner,
                'therapy_name' => $validated['new_therapy_name'],
                'device_name' => $validated['new_device_name'],
                'ipg_model' => $validated['new_ipg_model'],
                'ipg_model_number' => $validated['new_ipg_model_number'],
                'ipg_serial_number' => $validated['new_ipg_serial_number'],
                'has_ra_rv_lead' => true,
                'ra_rv_leads' => $validated['new_ra_rv_leads'],
                'csp_catheter_model' => $validated['new_csp_catheter_model'] ?? null,
                'csp_lead_serial' => $validated['new_csp_lead_serial'] ?? null,
                'csp_lead_model' => $validated['new_csp_lead_model'] ?? null,
                'secret_key' => $newSecretKey,
                'warranty_expired_at' => Carbon::parse($validated['new_implantation_date'])->addYear(),
                'user_id' => $engineer->id,
                'active' => true,
                'implant_brand' => "Biotronik"
            ]);

            $upgrade->update([
                'new_implantation_date' => $validated['new_implantation_date'],
                'new_ipg_serial_number' => $validated['new_ipg_serial_number'],
                'new_ipg_model' => $validated['new_ipg_model'],
                'new_ipg_model_number' => $validated['new_ipg_model_number'],
                'new_therapy_name' => $validated['new_therapy_name'],
                'new_device_name' => $validated['new_device_name'],
                'new_ra_rv_leads' => $validated['new_ra_rv_leads'],
                'new_csp_catheter_model' => $validated['new_csp_catheter_model'] ?? null,
                'new_csp_lead_model' => $validated['new_csp_lead_model'] ?? null,
                'new_csp_lead_serial' => $validated['new_csp_lead_serial'] ?? null,
                'status' => 'completed'
            ]);

            // Update IPG and Lead inventory status
            $this->updateIpgImplantationStatus(
                $validated['new_ipg_serial_number'],
                $validated['new_ipg_model_number'],
                $patientId
            );

            foreach ($validated['new_ra_rv_leads'] as $lead) {
                $this->updateLeadImplantationStatus(
                    $lead['serial'],
                    $lead['model'] ?? null,
                    $patientId
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Implant upgrade completed successfully',
                'upgrade' => $upgrade,
                'new_implant' => $newImplant
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error completing implant upgrade', [
                'errors' => $e->errors(),
                'upgrade_id' => $id,
                'engineer_id' => $engineer->id
            ]);
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Upgrade request not found or not assigned', [
                'upgrade_id' => $id,
                'engineer_id' => $engineer->id
            ]);
            DB::rollBack();
            return response()->json([
                'message' => 'Upgrade request not found or not assigned to you.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error completing implant upgrade', [
                'upgrade_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error completing implant upgrade',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update IPG serial record to mark it as implanted for a patient
     * 
     * @param string $serialNumber IPG serial number
     * @param string|null $modelNumber IPG model number (optional)
     * @param int|null $patientId Patient ID (optional)
     * @return bool Whether the operation was successful
     * @throws \Exception If a critical error occurs during the update
     */
    private function updateIpgImplantationStatus($serialNumber, $modelNumber = null, $patientId = null)
    {
        try {
            // Find the IPG by serial number
            $ipgSerial = \App\Models\IpgSerial::where('ipg_serial_number', $serialNumber)->first();

            if ($ipgSerial) {
                // Update existing IPG record
                $ipgSerial->is_implanted = true;
                if ($patientId) {
                    $ipgSerial->patient_id = $patientId;
                }

                if (!$ipgSerial->save()) {
                    \Log::error("Failed to update IPG implantation status", [
                        'serial_number' => $serialNumber,
                        'ipg_serial_id' => $ipgSerial->id
                    ]);
                    throw new \Exception("Failed to update IPG implantation status for serial: $serialNumber");
                }

                \Log::info("IPG serial marked as implanted", [
                    'ipg_serial_id' => $ipgSerial->id,
                    'serial_number' => $serialNumber,
                    'patient_id' => $patientId
                ]);

                return true;
            } else {
                // If IPG isn't in the system yet, attempt to create it
                if (!$modelNumber) {
                    \Log::error("IPG serial not found and no model number provided to create it", [
                        'serial_number' => $serialNumber
                    ]);
                    throw new \Exception("IPG serial $serialNumber not found and no model number provided to create a new record");
                }

                // Check if model exists
                $ipgModel = \App\Models\IpgModel::where('model_number', $modelNumber)->first();

                if (!$ipgModel) {
                    \Log::error("IPG model not found, cannot create IPG serial record", [
                        'model_number' => $modelNumber
                    ]);
                    throw new \Exception("IPG model $modelNumber not found, cannot create IPG serial record");
                }

                // Create new IPG serial record
                $ipgSerial = \App\Models\IpgSerial::create([
                    'serial_number' => $serialNumber,
                    'ipg_model_id' => $ipgModel->id,
                    'ipg_model_number' => $modelNumber,
                    'is_implanted' => true,
                    'patient_id' => $patientId
                ]);

                if (!$ipgSerial || !$ipgSerial->id) {
                    \Log::error("Failed to create new IPG serial record", [
                        'serial_number' => $serialNumber,
                        'model_number' => $modelNumber
                    ]);
                    throw new \Exception("Failed to create new IPG serial record for $serialNumber");
                }

                \Log::info("Created new IPG serial record and marked as implanted", [
                    'ipg_serial_id' => $ipgSerial->id,
                    'serial_number' => $serialNumber,
                    'patient_id' => $patientId
                ]);

                return true;
            }
        } catch (\Exception $e) {
            \Log::error("Error updating IPG implantation status", [
                'serial_number' => $serialNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Now we re-throw the exception to be handled by the caller
            throw new \Exception("Failed to update IPG implantation status: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update lead serial record to mark it as implanted for a patient
     * 
     * @param string $serialNumber Lead serial number
     * @param string|null $modelNumber Lead model number (optional)
     * @param int|null $patientId Patient ID (optional)
     * @return void
     */
    private function updateLeadImplantationStatus($serialNumber, $modelNumber = null, $patientId = null)
    {
        try {
            // Find the lead by serial number
            $leadSerial = \App\Models\LeadSerial::where('serial_number', $serialNumber)->first();

            if ($leadSerial) {
                // Update existing lead record
                $leadSerial->is_implanted = true;
                if ($patientId) {
                    $leadSerial->patient_id = $patientId;
                }
                $leadSerial->save();

                \Log::info("Lead serial marked as implanted", [
                    'lead_serial_id' => $leadSerial->id,
                    'serial_number' => $serialNumber,
                    'patient_id' => $patientId
                ]);
            } else {
                // If lead isn't in the system yet, log a warning
                \Log::warning("Lead serial not found in the system, cannot mark as implanted", [
                    'serial_number' => $serialNumber,
                    'model_number' => $modelNumber,
                    'patient_id' => $patientId
                ]);

                // If model number is provided, we can try to create a new lead serial record
                if ($modelNumber) {
                    // Find the lead model
                    $leadModel = \App\Models\LeadModel::where('model_number', $modelNumber)
                        ->orWhere('model_name', $modelNumber)
                        ->first();

                    if ($leadModel) {
                        // Create new lead serial record
                        $newLeadSerial = \App\Models\LeadSerial::create([
                            'serial_number' => $serialNumber,
                            'lead_model_id' => $leadModel->id,
                            'is_implanted' => true,
                            'patient_id' => $patientId
                        ]);

                        \Log::info("Created new lead serial record and marked as implanted", [
                            'lead_serial_id' => $newLeadSerial->id,
                            'serial_number' => $serialNumber,
                            'patient_id' => $patientId
                        ]);
                    } else {
                        \Log::warning("Lead model not found, cannot create lead serial record", [
                            'model_number' => $modelNumber
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error updating lead implantation status", [
                'serial_number' => $serialNumber,
                'error' => $e->getMessage()
            ]);
            // We don't throw the exception here to allow the main process to continue
        }
    }
}