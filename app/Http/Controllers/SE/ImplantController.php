<?php

namespace App\Http\Controllers\SE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Implant;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImplantController extends Controller
{
    public function upgradeImplant(Request $request)
    {
        $validated = $request->validate([
            'state' => 'required|string',
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_serial_number' => 'required|string|unique:implants,ipg_serial_number',
            'ipg_model' => 'required|string',
            'ipg_model_number' => 'required|string',
            'lead_brand' => 'required|string',
            'ra_rv_lead_model' => 'required|string',
            'ra_rv_lead_serial' => 'required|string',
            'rv_lead_model' => 'required|string',
            'rv_lead_serial' => 'required|string',
            'is_csp_implant' => 'required|boolean',
            'csp_catheter_model' => 'required_if:is_csp_implant,true|nullable|string',
            'csp_lead_brand' => 'required_if:is_csp_implant,true|nullable|string',
            'csp_lead_model' => 'required_if:is_csp_implant,true|nullable|string',
            'csp_lead_serial' => 'required_if:is_csp_implant,true|nullable|string',
        ]);

        try {
            // Generate secret key
            $secretKey = Str::random(8);
            
            // Create new implant
            $implant = Implant::create([
                ...$validated,
                'secret_key' => $secretKey,
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
                'user_id' => $request->user()->id,
                'active' => true,
                'pre_feb_2022' => false
            ]);

            return response()->json([
                'message' => 'Implant registered successfully',
                'data' => [
                    'ipg_serial_number' => $implant->ipg_serial_number,
                    'secret_key' => $implant->secret_key
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error registering implant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}