<?php

namespace App\Http\Controllers\SE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Implant;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImplantController extends Controller
{
    // public function upgradeImplant(Request $request)
    // {
    //     $validated = $request->validate([
    //         'state' => 'required|string',
    //         'hospital_name' => 'required|string',
    //         'doctor_name' => 'required|string',
    //         'therapy_name' => 'required|string',
    //         'device_name' => 'required|string',
    //         'implantation_date' => 'required|date',
    //         'ipg_serial_number' => 'required|string|unique:implants,ipg_serial_number',
    //         'ipg_model' => 'required|string',
    //         'ipg_model_number' => 'required|string',
    //         'lead_brand' => 'required|string',
    //         // 'ra_rv_lead_model' => 'required|string',
    //         // 'ra_rv_lead_serial' => 'required|string',
    //         // 'rv_lead_model' => 'required|string',
    //         // 'rv_lead_serial' => 'required|string',
    //         'is_csp_implant' => 'required|boolean',
    //         'csp_catheter_model' => 'required_if:is_csp_implant,true|nullable|string',
    //         'csp_lead_brand' => 'required_if:is_csp_implant,true|nullable|string',
    //         'csp_lead_model' => 'required_if:is_csp_implant,true|nullable|string',
    //         'csp_lead_serial' => 'required_if:is_csp_implant,true|nullable|string',
    //         // Add validation for ra_rv_leads array
    //         'ra_rv_leads' => 'nullable|array',
    //         'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
    //         'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',
    //     ]);

    //     try {
    //         // Generate secret key
    //         $secretKey = Str::random(16);

    //         // Create new implant
    //         $implant = Implant::create([
    //             ...$validated,
    //             'secret_key' => $secretKey,
    //             'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
    //             'user_id' => $request->user()->id,
    //             'active' => true,
    //             'pre_feb_2022' => false,
    //             'hospital_state' => $validated['state'],
    //             'has_ra_rv_lead' => true,
    //              'lead_brand' => 'Biotronik'

    //         ]);

    //         return response()->json([
    //             'message' => 'Implant registered successfully',
    //             'data' => [
    //                 'ipg_serial_number' => $implant->ipg_serial_number,
    //                 'secret_key' => $implant->secret_key
    //             ]
    //         ], 201);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error registering implant',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function upgradeImplant(Request $request)
    {
        $validated = $request->validate([
            // New implant details - these remain required
            'state' => 'required|string',
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_serial_number' => 'required|string|unique:implants,ipg_serial_number',
            'ipg_model' => 'required|string',
            'ipg_model_number' => 'required|string',
            'is_csp_implant' => 'required|boolean',
            'csp_catheter_model' => 'required_if:is_csp_implant,true|nullable|string',
            'csp_lead_model' => 'required_if:is_csp_implant,true|nullable|string',
            'csp_lead_serial' => 'required_if:is_csp_implant,true|nullable|string',
            
            // Required ra_rv_leads for the new implant
            'ra_rv_leads' => 'required|array|min:1',
            'ra_rv_leads.*.model' => 'required|string',
            'ra_rv_leads.*.serial' => 'required|string',
            'ra_rv_leads.*.brand' => 'required|string',
        
            // Previous implant details - all made optional
            'previous_implantation_date' => 'nullable|date',
            'previous_ipg_serial_number' => 'nullable|string',
            'previous_ipg_model' => 'nullable|string',
            'previous_ipg_model_number' => 'nullable|string',
            'previous_therapy_name' => 'nullable|string',
            'previous_device_name' => 'nullable|string',
            'previous_implant_brand' => 'nullable|string',
            'previous_is_csp_implant' => 'nullable|boolean',
            'previous_csp_catheter_model' => 'nullable|string',
            'previous_csp_lead_model' => 'nullable|string',
            'previous_csp_lead_serial' => 'nullable|string',
            
            // Optional ra_rv_leads for the previous implant
            'previous_ra_rv_leads' => 'nullable|array',
            'previous_ra_rv_leads.*.model' => 'required_with:previous_ra_rv_leads|string',
            'previous_ra_rv_leads.*.serial' => 'required_with:previous_ra_rv_leads|string',
            'previous_ra_rv_leads.*.brand' => 'required_with:previous_ra_rv_leads|string',
        ]);
    
        try {
            // Generate secret key for the new implant record
            $newSecretKey = Str::random(16);
    
            // Prepare the new implant data
            $newImplantData = [
                'hospital_state'      => $validated['state'],
                'hospital_name'       => $validated['hospital_name'],
                'doctor_name'         => $validated['doctor_name'],
                'therapy_name'        => $validated['therapy_name'],
                'device_name'         => $validated['device_name'],
                'implantation_date'   => $validated['implantation_date'],
                'ipg_serial_number'   => $validated['ipg_serial_number'],
                'ipg_model'           => $validated['ipg_model'],
                'ipg_model_number'    => $validated['ipg_model_number'],
                'is_csp_implant'      => $validated['is_csp_implant'],
                'csp_catheter_model'  => $validated['csp_catheter_model'] ?? null,
                'csp_lead_model'      => $validated['csp_lead_model'] ?? null,
                'csp_lead_serial'     => $validated['csp_lead_serial'] ?? null,
                'ra_rv_leads'         => $validated['ra_rv_leads'],
                'secret_key'          => $newSecretKey,
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
                'user_id'             => $request->user()->id,
                'active'              => true,
                'pre_feb_2022'        => false,
                'has_ra_rv_lead'      => true,
                'implant_brand'       => "Biotronik"
            ];
    
            // Create new implant record
            $implant = Implant::create($newImplantData);
    
            // Only create previous implant record if previous_ipg_serial_number is provided
            if (!empty($validated['previous_ipg_serial_number'])) {
                // Prepare the previous implant data
                $previousImplantData = [
                    'hospital_state'      => $validated['state'],
                    'hospital_name'       => $validated['hospital_name'],
                    'doctor_name'         => $validated['doctor_name'],
                    'therapy_name'        => $validated['previous_therapy_name'] ?? null,
                    'device_name'         => $validated['previous_device_name'] ?? null,
                    'implantation_date'   => $validated['previous_implantation_date'] ?? null,
                    'ipg_serial_number'   => $validated['previous_ipg_serial_number'],
                    'ipg_model'           => $validated['previous_ipg_model'] ?? null,
                    'ipg_model_number'    => $validated['previous_ipg_model_number'] ?? null,
                    'implant_brand'       => $validated['previous_implant_brand'] ?? null,
                    'is_csp_implant'      => $validated['previous_is_csp_implant'] ?? false,
                    'csp_catheter_model'  => $validated['previous_csp_catheter_model'] ?? null,
                    'csp_lead_model'      => $validated['previous_csp_lead_model'] ?? null,
                    'csp_lead_serial'     => $validated['previous_csp_lead_serial'] ?? null,
                    'ra_rv_leads'         => $validated['previous_ra_rv_leads'] ?? [],
                    'secret_key'          => $newSecretKey, // Use the same secret key for both implants
                    'warranty_expired_at' => isset($validated['previous_implantation_date']) ? 
                        Carbon::parse($validated['previous_implantation_date'])->addMonths(3) : null,
                    'user_id'             => $request->user()->id,
                    'active'              => false,
                    'pre_feb_2022'        => false,
                    'has_ra_rv_lead'      => !empty($validated['previous_ra_rv_leads']),
                ];
    
                Implant::create($previousImplantData);
                
                \Log::info('Previous implant record created during upgrade', [
                    'previous_ipg_serial_number' => $validated['previous_ipg_serial_number'],
                    'new_ipg_serial_number' => $validated['ipg_serial_number']
                ]);
            }
    
            return response()->json([
                'message' => 'Implant upgraded successfully',
                'data' => [
                    'new_ipg_serial_number' => $implant->ipg_serial_number,
                    'new_secret_key' => $implant->secret_key,
                    'previous_implant_recorded' => !empty($validated['previous_ipg_serial_number'])
                ]
            ], 201);
    
        } catch (\Exception $e) {
            \Log::error('Error upgrading implant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error upgrading implant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}