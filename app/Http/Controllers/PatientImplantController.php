<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Implant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\DeviceReplacement;
use Illuminate\Validation\Rule;

class PatientImplantController extends Controller
{
    public function getUserDetails(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ], 200);
    }
    public function checkIfPatientHasImplant(Request $request)
    {
        $user = $request->user();
        $hasImplant = $user->implant()->exists();

        return response()->json([
            'has_implant' => $hasImplant
        ], 200);
    }

    public function store(Request $request)
    {
        // First check if user already has an implant
        $user = $request->user();
        // if ($user->implant()->exists()) {
        //     return response()->json([
        //         'message' => 'Patient already has an implant registered',
        //         'error' => 'Multiple implants not allowed'
        //     ], 400);
        // }

        $validated = $request->validate([
            // Basic patient fields
            'name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'address' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'pin_code' => 'required|string',
            // 'patient_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Relative information
            'relative_name' => 'required|string|max:255',
            'relative_relation' => 'required|string',
            'relative_gender' => 'required|in:Male,Female,Other',
            'relative_address' => 'required|string',
            'relative_state' => 'required|string',
            'relative_city' => 'required|string',
            'relative_pin_code' => 'required|string',
            'relative_email' => 'nullable|email',
            'relative_phone' => 'required|string',

            // Common fields
            'pre_feb_2022' => 'required|boolean',
            'ipg_serial_number' => 'required|string',
            // 'secret_key' => 'nullable|exists:implants,secret_key',

            'secret_key' => [
                'nullable',
                Rule::exists('implants', 'secret_key')->where(function ($query) use ($request) {
                    $query->where('ipg_serial_number', $request->ipg_serial_number);
                }),
            ],

            // Conditional validation based on pre_feb_2022
            'ipg_model_number' => 'required_if:pre_feb_2022,1|string|nullable',
            'implantation_date' => 'required_if:pre_feb_2022,1|date|nullable',
            'ipg_model' => 'required_if:pre_feb_2022,1|string|nullable',
            'hospital_state' => 'required_if:pre_feb_2022,1|string|nullable',
            'hospital_name' => 'required_if:pre_feb_2022,1|string|nullable',
            'doctor_name' => 'required_if:pre_feb_2022,1|string|nullable',
            'channel_partner' => 'required_if:pre_feb_2022,1|string|nullable',
            'therapy_name' => 'required_if:pre_feb_2022,1|string|nullable',
            'device_name' => 'required_if:pre_feb_2022,1|string|nullable',
            'patient_id_card' => 'required_if:pre_feb_2022,1|file|nullable',
            'warranty_card' => 'required_if:pre_feb_2022,1|file|nullable',
            'interrogation_report' => 'required_if:pre_feb_2022,1|file|nullable',
        ]);

        try {
            // Update patient information
            $user->update([
                'patient_photo' => $request->file('patient_photo'),
                // ->store('patient_photos', 'public'),
                'name' => $request->name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'state' => $request->state,
                'city' => $request->city,
                'pin_code' => $request->pin_code,
                'relative_name' => $request->relative_name,
                'relative_relation' => $request->relative_relation,
                'relative_gender' => $request->relative_gender,
                'relative_address' => $request->relative_address,
                'relative_state' => $request->relative_state,
                'relative_city' => $request->relative_city,
                'relative_pin_code' => $request->relative_pin_code,
                'relative_email' => $request->relative_email,
                'relative_phone' => $request->relative_phone,
            ]);

            if ($request->pre_feb_2022) {
                // Handle pre-Feb 2022 implant creation
                $implantData = [
                    'pre_feb_2022' => true,
                    'ipg_serial_number' => $request->ipg_serial_number,
                    'implantation_date' => $request->implantation_date,
                    'ipg_model' => $request->ipg_model,
                    'ipg_model_number' => $request->ipg_model_number,
                    'hospital_state' => $request->hospital_state,
                    'hospital_name' => $request->hospital_name,
                    'doctor_name' => $request->doctor_name,
                    'channel_partner' => $request->channel_partner,
                    'therapy_name' => $request->therapy_name,
                    'device_name' => $request->device_name,
                    'ra_rv_lead_model' => $request->ra_rv_lead_model,
                    'has_ra_rv_lead' => $request->has_ra_rv_lead,
                    'has_extra_lead' => $request->has_extra_lead,
                    'csp_lead_model' => $request->csp_lead_model,
                    'csp_catheter_model' => $request->csp_catheter_model,
                    'ra_rv_lead_serial' => $request->ra_rv_lead_serial,
                    'csp_lead_serial' => $request->csp_lead_serial,
                    'warranty_expired_at' => Carbon::parse($request->implantation_date)->addMonths(3)
                ];

                // // Store documents if provided
                // if ($request->hasFile('patient_id_card')) {
                //     $implantData['patient_id_card'] = $request->file('patient_id_card')->store('patient_documents', 'public');
                // }
                // if ($request->hasFile('warranty_card')) {
                //     $implantData['warranty_card'] = $request->file('warranty_card')->store('patient_documents', 'public');
                // }
                // if ($request->hasFile('interrogation_report')) {
                //     $implantData['interrogation_report'] = $request->file('interrogation_report')->store('patient_documents', 'public');
                // }

                $implant = $user->implant()->create($implantData);
                $message = 'Patient and implant information saved successfully';
            } else {
                // Handle post-Feb 2022 implant linking
                $existingImplant = Implant::where('ipg_serial_number', $request->ipg_serial_number)->first();



                if (!$existingImplant) {
                    // Create new implant record
                    $implant = Implant::create([
                        'ipg_serial_number' => $request->ipg_serial_number,
                        'secret_key' => $request->secret_key,
                        'patient_id' => $user->id,
                        'pre_feb_2022' => false,
                        'implantation_date' => now(),
                        'warranty_expired_at' => now()->addMonths(3)
                    ]);

                    $message = 'New implant registered successfully';
                    return response()->json([
                        'message' => $message,
                        'implant' => $implant
                    ], 201);
                }

                if ($existingImplant->patient_id !== null) {
                    return response()->json([
                        'message' => 'Implant is already associated with another patient'
                    ], 400);
                }

                $existingImplant->patient_id = $user->id;
                $existingImplant->save();
                $implant = $existingImplant;
                $message = 'Existing implant linked to patient successfully';
            }

            return response()->json([
                'message' => $message,
                'patient' => $user,
                'implant' => $implant
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error saving information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //     $validated = $request->validate([
    //         // Basic patient fields
    //         'name' => 'required|string|max:255',
    //         'date_of_birth' => 'required|date',
    //         'gender' => 'required|in:Male,Female,Other',
    //         'address' => 'required|string',
    //         'state' => 'required|string',
    //         'city' => 'required|string',
    //         'pin_code' => 'required|string',
    //         'patient_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

    //         // Relative information
    //         'relative_name' => 'required|string|max:255',
    //         'relative_relation' => 'required|string',
    //         'relative_gender' => 'required|in:Male,Female,Other',
    //         'relative_address' => 'required|string',
    //         'relative_state' => 'required|string',
    //         'relative_city' => 'required|string',
    //         'relative_pin_code' => 'required|string',
    //         'relative_email' => 'nullable|email',
    //         'relative_phone' => 'required|string',

    //         // Common fields
    //         'pre_feb_2022' => 'required|boolean',
    //         'ipg_serial_number' => 'required|string',
    //         'ipg_model_number' => 'required_if:pre_feb_2022,1|string|nullable',

    //         // Fields required only for pre-Feb 2022
    //         'implantation_date' => 'required_if:pre_feb_2022,1|date',
    //         'ipg_model' => 'required_if:pre_feb_2022,1|string',
    //         'hospital_state' => 'required_if:pre_feb_2022,1|string',
    //         'hospital_name' => 'required_if:pre_feb_2022,1|string',
    //         'doctor_name' => 'required_if:pre_feb_2022,1|string',
    //         'channel_partner' => 'required_if:pre_feb_2022,1|string',
    //         'therapy_name' => 'required_if:pre_feb_2022,1|string',
    //         'device_name' => 'required_if:pre_feb_2022,1|string',

    //         // Optional fields
    //         'ra_rv_lead_model' => 'nullable|string',
    //         'has_ra_rv_lead' => 'nullable|boolean',
    //         'has_extra_lead' => 'nullable|boolean',
    //         'csp_lead_model' => 'nullable|string',
    //         'ra_rv_lead_serial' => 'nullable|string',
    //         'csp_lead_serial' => 'nullable|string',
    //         'csp_catheter_model' => 'nullable|string|max:255',

    //         // Pre-Feb 2022 documents
    //         'patient_id_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //         'warranty_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //         'interrogation_report' => 'required_if:pre_feb_2022,1|file|nullable',

    //         // Post-Feb 2022 required field
    //         'secret_key' => 'required_if:pre_feb_2022,0|string',
    //     ], [
    //         'secret_key.required_if' => 'Secret key is required for implants after February 2022',
    //         'ipg_serial_number.required' => 'IPG serial number is required for all implants'
    //     ]);


    //     // $existingImplant = $this->validateImplantCredentials(
    //     //     $request->ipg_serial_number,
    //     //     $request->secret_key
    //     // );

    //     // if (!$existingImplant) {
    //     //     return response()->json([
    //     //         'message' => 'Invalid IPG serial number or secret key',
    //     //         'error' => 'No matching implant found with provided credentials'
    //     //     ], 404);
    //     // }


    //     try {
    //         // Create patient
    //         $patient = Patient::create([
    //             'patient_photo' => $request->file('patient_photo')->store('patient_photos', 'public'),
    //             'name' => $request->name,
    //             'date_of_birth' => $request->date_of_birth,
    //             'gender' => $request->gender,
    //             'address' => $request->address,
    //             'state' => $request->state,
    //             'city' => $request->city,
    //             'pin_code' => $request->pin_code,
    //             'relative_name' => $request->relative_name,
    //             'relative_relation' => $request->relative_relation,
    //             'relative_gender' => $request->relative_gender,
    //             'relative_address' => $request->relative_address,
    //             'relative_state' => $request->relative_state,
    //             'relative_city' => $request->relative_city,
    //             'relative_pin_code' => $request->relative_pin_code,
    //             'relative_email' => $request->relative_email,
    //             'relative_phone' => $request->relative_phone,
    //         ]);

    //         // Prepare implant data
    //         $implantData = [
    //             'pre_feb_2022' => $request->pre_feb_2022,
    //             'ipg_serial_number' => $request->ipg_serial_number,
    //             'secret_key' => $request->secret_key,
    //         ];

    //         // Conditionally add fields based on pre_feb_2022 flag
    //         if ($request->pre_feb_2022) {
    //             $implantData['implantation_date'] = $request->implantation_date;
    //             $implantData['ipg_model'] = $request->ipg_model;
    //             $implantData['ipg_model_number'] = $request->ipg_model_number;
    //             $implantData['hospital_state'] = $request->hospital_state;
    //             $implantData['hospital_name'] = $request->hospital_name;
    //             $implantData['doctor_name'] = $request->doctor_name;
    //             $implantData['channel_partner'] = $request->channel_partner;
    //             $implantData['therapy_name'] = $request->therapy_name;
    //             $implantData['device_name'] = $request->device_name;

    //             $implantData['ra_rv_lead_model'] = $request->ra_rv_lead_model;
    //             $implantData['has_ra_rv_lead'] = $request->has_ra_rv_lead;
    //             $implantData['has_extra_lead'] = $request->has_extra_lead;
    //             $implantData['csp_lead_model'] = $request->csp_lead_model;
    //             $implantData['csp_catheter_model'] = $request->csp_catheter_model;
    //             $implantData['ra_rv_lead_serial'] = $request->ra_rv_lead_serial;
    //             $implantData['csp_lead_serial'] = $request->csp_lead_serial;
    //             $implantData['patient_id_card'] = $request->file('patient_id_card')->store('patient_documents', 'public');
    //             $implantData['warranty_card'] = $request->file('warranty_card')->store('patient_documents', 'public');
    //             $implantData['interrogation_report'] = $request->file('interrogation_report')->store('patient_documents', 'public');

    //         } else {
    //             $implantData['implantation_date'] = null;
    //             $implantData['ipg_model'] = null;
    //             $implantData['hospital_state'] = null;
    //             $implantData['hospital_name'] = null;
    //             $implantData['doctor_name'] = null;
    //             $implantData['channel_partner'] = null;
    //             $implantData['therapy_name'] = null;
    //             $implantData['device_name'] = null;
    //             $implantData['ra_rv_lead_model'] = null;
    //             $implantData['has_ra_rv_lead'] = null;
    //             $implantData['has_extra_lead'] = null;
    //             $implantData['csp_lead_model'] = null;
    //             $implantData['ra_rv_lead_serial'] = null;
    //             $implantData['csp_lead_serial'] = null;
    //             $implantData['csp_catheter_model'] = null;
    //             $implantData['ipg_model_number'] = null;
    //         }

    //         // Create implant
    //         $implant = $patient->implant()->create($implantData);

    //         return response()->json([
    //             'message' => 'Patient and implant information saved successfully',
    //             'patient' => $patient,
    //             'implant' => $implant
    //         ], 201);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error saving information',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function submitByEngineer(Request $request)
    {
        $validated = $request->validate([
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'channel_partner' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_serial_number' => 'required|string',
            'ipg_model' => 'required|string',
            'ipg_model_number' => 'required|string',
            'ra_rv_lead_model' => 'nullable|string',
            'ra_rv_lead_serial' => 'nullable|string',
            'has_ra_rv_lead' => 'nullable|boolean',
            'csp_catheter_model' => 'nullable|string',
            'has_extra_lead' => 'nullable|boolean',
            'csp_lead_model' => 'nullable|string',
            'csp_lead_serial' => 'nullable|string'
        ]);

        try {
            $user = $request->user();
            $implant = Implant::where('ipg_serial_number', $validated['ipg_serial_number'])->first();
            $isNewImplant = !$implant;

            $implantData = [
                'pre_feb_2022' => false,
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'channel_partner' => $validated['channel_partner'],
                'therapy_name' => $validated['therapy_name'],
                'device_name' => $validated['device_name'],
                'implantation_date' => $validated['implantation_date'],
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
                'ipg_model' => $validated['ipg_model'],
                'ipg_model_number' => $validated['ipg_model_number'],
                'ra_rv_lead_model' => $validated['ra_rv_lead_model'],
                'ra_rv_lead_serial' => $validated['ra_rv_lead_serial'],
                'has_ra_rv_lead' => $validated['has_ra_rv_lead'],
                'csp_catheter_model' => $validated['csp_catheter_model'],
                'has_extra_lead' => $validated['has_extra_lead'],
                'csp_lead_model' => $validated['csp_lead_model'],
                'csp_lead_serial' => $validated['csp_lead_serial'],
                'user_id' => $user->id
            ];

            if ($isNewImplant) {
                // Create new implant with new secret key
                $secretKey = Str::random(16);
                $implantData['secret_key'] = $secretKey;
                $implantData['ipg_serial_number'] = $validated['ipg_serial_number'];

                $implant = Implant::create($implantData);
                $message = 'Implant details registered successfully';
            } else {
                // Update existing implant but keep original secret key and IPG serial number
                $implant->update($implantData);
                $secretKey = $implant->secret_key;
                $message = 'Implant details updated successfully';
            }

            return response()->json([
                'message' => $message,
                'secret_key' => $secretKey,
                'ipg_serial_number' => $validated['ipg_serial_number']
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error managing implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getEngineerImplants(Request $request)
    {
        try {
            // Get implants where user_id matches the authenticated engineer's ID
            $implants = Implant::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get([
                    'secret_key',
                    'ipg_serial_number',
                    'hospital_name',
                    'doctor_name',


                ]);

            return response()->json([
                'implants' => $implants
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getWarrantyStatus(Request $request)
    {
        $user = $request->user();
        $implant = $user->implant;

        if (!$implant) {
            return response()->json(['message' => 'Implant not found.'], 404);
        }

        $warrantyExpiration = $implant->warranty_expired_at;
        $isExpired = now()->greaterThan($warrantyExpiration);

        return response()->json([
            'warranty_expiration_date' => $warrantyExpiration,
            'is_expired' => $isExpired,
        ]);
    }


    public function requestReplacement(Request $request)
    {
        try {
            $patient = $request->user();
            $implant = $patient->implant;

            if (!$implant) {
                return response()->json(['message' => 'No implant found'], 404);
            }

            // Check existing request - only block if pending or approved
            $activeRequest = DeviceReplacement::where('patient_id', $patient->id)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($activeRequest) {
                return response()->json([
                    'message' => 'Active replacement request exists',
                    'request' => $activeRequest
                ], 400);
            }

            // Get latest rejected request if exists
            $lastRejectedRequest = DeviceReplacement::where('patient_id', $patient->id)
                ->where('status', 'rejected')
                ->latest()
                ->first();

            // Check warranty
            $isWarranty = !now()->greaterThan($implant->warranty_expired_at);

            // Base validation
            $validationRules = [
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'doctor_name' => 'required|string',
                'channel_partner' => 'required|string',
            ];

            // Warranty validation
            if ($isWarranty) {
                $validationRules += [
                    'replacement_reason' => 'required|string',
                    // 'planned_replacement_date' => 'required|date|after:today',
                    'planned_replacement_date' => 'required|date_format:Y-m-d H:i:s|after:now',
                    // 'interrogation_report' => 'required|file|max:2048',
                    // 'prescription' => 'required|file|max:2048'
                ];
            }

            $validated = $request->validate($validationRules);

            $replacementData = [
                'patient_id' => $patient->id,
                'implant_id' => $implant->id,
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'channel_partner' => $validated['channel_partner']
            ];

            if ($isWarranty) {
                $replacementData += [
                    'replacement_reason' => $validated['replacement_reason'],
                    'planned_replacement_date' => $validated['planned_replacement_date'],
                    // 'interrogation_report_path' => $request->file('interrogation_report')->store('reports'),
                    // 'prescription_path' => $request->file('prescription')->store('prescriptions'),
                    'status' => DeviceReplacement::STATUS_PENDING
                ];
            } else {
                $replacementData += [
                    'status' => DeviceReplacement::STATUS_APPROVED
                ];
            }

            // If there was a rejected request, update it instead of creating new
            if ($lastRejectedRequest) {
                $lastRejectedRequest->update($replacementData);
                $replacement = $lastRejectedRequest->fresh();
                $message = 'Replacement request resubmitted';
            } else {
                $replacement = DeviceReplacement::create($replacementData);
                $message = 'Replacement request submitted';
            }

            return response()->json([
                'message' => $message,
                'data' => $replacement
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getReplacementStatus(Request $request)
    {
        try {
            $patient = $request->user();
            $replacementRequest = DeviceReplacement::where('patient_id', $patient->id)
                ->latest()
                ->first();

            if (!$replacementRequest) {
                return response()->json([
                    'message' => 'No replacement request found',
                    'status' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Replacement request status retrieved successfully',
                'data' => [
                    'status' => $replacementRequest->status,
                    'submitted_at' => $replacementRequest->created_at,
                    'rejection_reason' => $replacementRequest->rejection_reason,
                    'service_charge' => $replacementRequest->service_charge,
                    'planned_replacement_date' => $replacementRequest->planned_replacement_date
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving replacement request status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitReplacement(Request $request)
    {
        $validated = $request->validate([
            'new_ipg_serial_number' => 'required|string|exists:implants,ipg_serial_number',
            'state' => 'required|string',
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_model' => 'required|string',
            // 'ipg_model_number' => 'required|string'
        ]);

        try {
            \DB::beginTransaction();

            // Find the existing implant record
            $implant = Implant::where('ipg_serial_number', $validated['new_ipg_serial_number'])->first();

            // Update the implant record with new information
            $implant->update([
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'therapy_name' => $validated['therapy_name'],
                'device_name' => $validated['device_name'],
                'implantation_date' => $validated['implantation_date'],
                'ipg_model' => $validated['ipg_model'],
                // 'ipg_model_number' => $validated['ipg_model_number'],
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
                'user_id' => $request->user()->id
            ]);

            // Find and update the related device replacement record
            $replacementRequest = DeviceReplacement::where('new_ipg_serial_number', $validated['new_ipg_serial_number'])
                ->where('status', 'approved')
                ->where('service_completed', false)
                ->latest()
                ->first();


            if ($replacementRequest) {
                // Log before updating
                \Log::info('Device replacement service completion initiated', [
                    'replacement_id' => $replacementRequest->id,
                    'patient_id' => $replacementRequest->patient_id,

                    'new_ipg_serial' => $replacementRequest->new_ipg_serial_number,
                    'engineer_id' => $request->user()->id
                ]);

                $replacementRequest->update([
                    'service_completed' => true
                ]);

                // Log after successful update
                \Log::info('Device replacement service completed successfully', [
                    'replacement_id' => $replacementRequest->id,

                    'engineer_id' => $request->user()->id
                ]);
            }
            \DB::commit();

            return response()->json([
                'message' => 'Implant information updated successfully',
                'data' => $implant,
                'replacement_updated' => $replacementRequest ? true : false
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'message' => 'Error updating implant information',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getPatientDetailsByIpg($ipgSerialNumber)
    {
        try {
            $implant = Implant::with([
                'patient' => function ($query) {
                    $query->select(
                        'id',
                        'name',
                        'date_of_birth',
                        'gender',
                        'email',
                        'phone_number',
                        'address',
                        'state',
                        'city',
                        'pin_code'
                    );
                }
            ])->where('ipg_serial_number', $ipgSerialNumber)
                ->select(
                    'id',
                    'patient_id',
                    'user_id',
                    'ipg_serial_number',
                    'secret_key',
                    'pre_feb_2022',
                    'ipg_model',
                    'ipg_model_number',
                    'implantation_date',
                    'hospital_name',
                    'hospital_state',
                    'doctor_name',
                    'channel_partner',
                    'therapy_name',
                    'device_name',
                    'ra_rv_lead_model',
                    'has_ra_rv_lead',
                    'has_extra_lead',
                    'csp_lead_model',
                    'csp_catheter_model',
                    'ra_rv_lead_serial',
                    'csp_lead_serial',
                    'warranty_expired_at',
                    'active',
                    'patient_id_card',
                    'warranty_card',
                    'interrogation_report',
                    'created_at',
                    'updated_at'
                )
                ->first();

            if (!$implant) {
                return response()->json([
                    'message' => 'No implant found with provided IPG serial number'
                ], 404);
            }

            if (!$implant->patient) {
                return response()->json([
                    'message' => 'No patient associated with this IPG serial number'
                ], 404);
            }

            return response()->json([
                'message' => 'Patient details retrieved successfully',
                'data' => [
                    'patient' => $implant->patient,
                    'implant' => [
                        'ipg_serial_number' => $implant->ipg_serial_number,
                        'secret_key' => $implant->secret_key,
                        'pre_feb_2022' => $implant->pre_feb_2022,
                        'ipg_model' => $implant->ipg_model,
                        'ipg_model_number' => $implant->ipg_model_number,
                        'implantation_date' => $implant->implantation_date,
                        'hospital_name' => $implant->hospital_name,
                        'hospital_state' => $implant->hospital_state,
                        'doctor_name' => $implant->doctor_name,
                        'channel_partner' => $implant->channel_partner,
                        'therapy_name' => $implant->therapy_name,
                        'device_name' => $implant->device_name,
                        'ra_rv_lead_model' => $implant->ra_rv_lead_model,
                        'has_ra_rv_lead' => $implant->has_ra_rv_lead,
                        'has_extra_lead' => $implant->has_extra_lead,
                        'csp_lead_model' => $implant->csp_lead_model,
                        'csp_catheter_model' => $implant->csp_catheter_model,
                        'ra_rv_lead_serial' => $implant->ra_rv_lead_serial,
                        'csp_lead_serial' => $implant->csp_lead_serial,
                        'warranty_status' => [
                            'expired_at' => $implant->warranty_expired_at,
                            'is_active' => now()->lt($implant->warranty_expired_at)
                        ],
                        'active' => $implant->active,
                        'patient_id_card' => $implant->patient_id_card,
                        'warranty_card' => $implant->warranty_card,
                        'interrogation_report' => $implant->interrogation_report,
                        'created_at' => $implant->created_at,
                        'updated_at' => $implant->updated_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving patient details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function linkPatientToImplant(Request $request)
    {
        $validated = $request->validate([
            'ipg_serial_number' => 'required|string|exists:implants,ipg_serial_number',
            'secret_key' => 'required|string'
        ]);

        try {
            // Get authenticated patient's ID
            $patientId = $request->user()->id;

            // Find the implant with matching IPG serial and secret key
            $implant = Implant::where('ipg_serial_number', $validated['ipg_serial_number'])
                ->where('secret_key', $validated['secret_key'])
                ->first();

            if (!$implant) {
                return response()->json([
                    'message' => 'Invalid IPG serial number or secret key'
                ], 404);
            }

            // Check if implant is already linked to a patient
            if ($implant->patient_id) {
                return response()->json([
                    'message' => 'This implant is already linked to a patient'
                ], 400);
            }

            // Begin transaction
            \DB::beginTransaction();
            try {
                // Deactivate ALL existing implants for this patient
                Implant::where('patient_id', $patientId)
                    ->update(['active' => false]);

                // Find the most recent active implant to copy fields from
                $oldImplant = Implant::where('patient_id', $patientId)
                    ->latest()
                    ->first();

                if ($oldImplant) {
                    // Fields to copy from old implant
                    $fieldsToCopy = [
                        'channel_partner',
                        'has_ra_rv_lead',
                        'has_extra_lead',
                        'patient_id_card',
                        'warranty_card',
                        'interrogation_report'
                    ];

                    // Copy fields from old implant to new implant
                    foreach ($fieldsToCopy as $field) {
                        if (isset($oldImplant->$field)) {
                            $implant->$field = $oldImplant->$field;
                        }
                    }
                }

                // Link new implant to patient and set it as active
                $implant->update([
                    'patient_id' => $patientId,
                    'active' => true
                ]);

                \DB::commit();

                return response()->json([
                    'message' => 'Implant linked to patient successfully',
                    'data' => [
                        'ipg_serial_number' => $implant->ipg_serial_number,
                        'previous_implants_deactivated' => $oldImplant ? true : false
                    ]
                ], 200);

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error linking implant to patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}

