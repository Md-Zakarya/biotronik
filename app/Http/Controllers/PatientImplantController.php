<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Implant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\DeviceReplacement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use App\Models\PendingImplant;
use Illuminate\Support\Facades\Storage;
use App\Services\S3StorageService;
use Illuminate\Support\Facades\DB;



class PatientImplantController extends Controller
{
    protected $s3Service;

    public function __construct(S3StorageService $s3Service)
    {
        $this->s3Service = $s3Service;
    }
    public function getUserDetails(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ], 200);
    }
    // public function checkIfPatientHasImplant(Request $request)
    // {
    //     $user = $request->user();
    //     $hasImplant = $user->implant()->exists();

    //     return response()->json([
    //         'has_implant' => $hasImplant
    //     ], 200);
    // }

    // public function store(Request $request)
    // {
    //     // First check if user already has an implant
    //     $user = $request->user();
    //     // if ($user->implant()->exists()) {
    //     //     return response()->json([
    //     //         'message' => 'Patient already has an implant registered',
    //     //         'error' => 'Multiple implants not allowed'
    //     //     ], 400);
    //     // }

    //     $validated = $request->validate([
    //         // Basic patient fields
    //         'name' => 'required|string|max:255',
    //         'date_of_birth' => 'required|date',
    //         'gender' => 'required|in:Male,Female,Other',
    //         'address' => 'required|string',
    //         'state' => 'required|string',
    //         'city' => 'required|string',
    //         'pin_code' => 'required|string',
    //         // 'patient_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

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
    //         // 'secret_key' => 'nullable|exists:implants,secret_key',

    //         'secret_key' => [
    //             'nullable',
    //             Rule::exists('implants', 'secret_key')->where(function ($query) use ($request) {
    //                 $query->where('ipg_serial_number', $request->ipg_serial_number);
    //             }),
    //         ],

    //         // Conditional validation based on pre_feb_2022
    //         'ipg_model_number' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'implantation_date' => 'required_if:pre_feb_2022,1|date|nullable',
    //         'ipg_model' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'hospital_state' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'hospital_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'doctor_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'channel_partner' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'therapy_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'device_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //         'patient_id_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //         'warranty_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //         'interrogation_report' => 'required_if:pre_feb_2022,1|file|nullable',
    //         // 'csp_lead_model' => 'nullable|string',
    //         // 'csp_lead_serial' => 'nullable|string',
    //     ]);

    //     try {
    //         // Update patient information
    //         $user->update([
    //             'patient_photo' => $request->file('patient_photo'),
    //             // ->store('patient_photos', 'public'),
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

    //         if ($request->pre_feb_2022) {
    //             // Handle pre-Feb 2022 implant creation
    //             $implantData = [
    //                 'pre_feb_2022' => true,
    //                 'ipg_serial_number' => $request->ipg_serial_number,
    //                 'implantation_date' => $request->implantation_date,
    //                 'ipg_model' => $request->ipg_model,
    //                 'ipg_model_number' => $request->ipg_model_number,
    //                 'hospital_state' => $request->hospital_state,
    //                 'hospital_name' => $request->hospital_name,
    //                 'doctor_name' => $request->doctor_name,
    //                 'channel_partner' => $request->channel_partner,
    //                 'therapy_name' => $request->therapy_name,
    //                 'device_name' => $request->device_name,
    //                 'ra_rv_lead_model' => $request->ra_rv_lead_model,
    //                 'has_ra_rv_lead' => $request->has_ra_rv_lead,
    //                 'has_extra_lead' => $request->has_extra_lead,
    //                 'csp_lead_model' => $request->csp_lead_model,
    //                 'csp_catheter_model' => $request->csp_catheter_model,
    //                 'ra_rv_lead_serial' => $request->ra_rv_lead_serial,
    //                 'csp_lead_serial' => $request->csp_lead_serial,
    //                 'warranty_expired_at' => Carbon::parse($request->implantation_date)->addMonths(3)
    //             ];

    //             // // Store documents if provided
    //             // if ($request->hasFile('patient_id_card')) {
    //             //     $implantData['patient_id_card'] = $request->file('patient_id_card')->store('patient_documents', 'public');
    //             // }
    //             // if ($request->hasFile('warranty_card')) {
    //             //     $implantData['warranty_card'] = $request->file('warranty_card')->store('patient_documents', 'public');
    //             // }
    //             // if ($request->hasFile('interrogation_report')) {
    //             //     $implantData['interrogation_report'] = $request->file('interrogation_report')->store('patient_documents', 'public');
    //             // }

    //             $implant = $user->implant()->create($implantData);
    //             $message = 'Patient and implant information saved successfully';
    //         } else {
    //             // Handle post-Feb 2022 implant linking
    //             $existingImplant = Implant::where('ipg_serial_number', $request->ipg_serial_number)->first();



    //             if (!$existingImplant) {
    //                 // Create new implant record
    //                 $implant = Implant::create([
    //                     'ipg_serial_number' => $request->ipg_serial_number,
    //                     'secret_key' => $request->secret_key,
    //                     'patient_id' => $user->id,
    //                     'pre_feb_2022' => false,
    //                     'implantation_date' => now(),
    //                     'warranty_expired_at' => now()->addMonths(3)
    //                 ]);

    //                 $message = 'New implant registered successfully';
    //                 return response()->json([
    //                     'message' => $message,
    //                     'implant' => $implant
    //                 ], 201);
    //             }

    //             if ($existingImplant->patient_id !== null) {
    //                 return response()->json([
    //                     'message' => 'Implant is already associated with another patient'
    //                 ], 400);
    //             }

    //             $existingImplant->patient_id = $user->id;
    //             $existingImplant->save();
    //             $implant = $existingImplant;
    //             $message = 'Existing implant linked to patient successfully';
    //         }

    //         return response()->json([
    //             'message' => $message,
    //             'patient' => $user,
    //             'implant' => $implant
    //         ], 201);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Error saving information',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

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




    /**
     * Update patient and relative information
     */
    public function updatePatientInfo(Request $request)
    {
        $requestId = uniqid('req_');
        Log::info("[$requestId] Starting patient information update process", [
            'user_id' => $request->user() ? $request->user()->id : 'no user',
            'request_method' => $request->method(),
            'request_ip' => $request->ip()
        ]);

        try {
            $user = $request->user();
            Log::info("[$requestId] User retrieved", ['user_id' => $user->id, 'email' => $user->email]);

            Log::info("[$requestId] Starting validation for patient info");
            $validated = $request->validate([
                // Basic patient fields (all optional)
                'name' => 'sometimes|string|max:255',
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|in:Male,Female,Other',
                'address' => 'sometimes|string',
                'state' => 'sometimes|string',
                'city' => 'sometimes|string',
                'pin_code' => 'sometimes|string',
                'patient_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',

                // Relative information (all optional)
                'relative_name' => 'sometimes|string|max:255',
                'relative_relation' => 'sometimes|string',
                'relative_gender' => 'sometimes|in:Male,Female,Other',
                'relative_address' => 'sometimes|string',
                'relative_state' => 'sometimes|string',
                'relative_city' => 'sometimes|string',
                'relative_pin_code' => 'sometimes|string',
                'relative_email' => 'sometimes|nullable|email',
                'relative_phone' => 'sometimes|string',
            ]);

            Log::info("[$requestId] Patient information validation successful");

            // Prepare update data with only the fields that were provided
            $updateData = [];

            // Handle patient photo if provided
            if ($request->hasFile('patient_photo')) {
                $file = $request->file('patient_photo');

                // Delete existing photo if it exists
                if ($user->patient_photo) {
                    $this->s3Service->deleteFile($user->patient_photo);
                }

                // Upload new photo
                $uploadResult = $this->s3Service->uploadFile(
                    $file,
                    'patient_photos',
                    $user->id
                );

                if (!$uploadResult['success']) {
                    throw new \Exception('Failed to upload patient photo: ' . $uploadResult['error']);
                }

                $updateData['patient_photo'] = $uploadResult['db_path'];

                Log::info("[$requestId] Patient photo uploaded to S3", [
                    'path' => $uploadResult['s3_path'],
                ]);
            }

            // Add all validated fields to the update data
            foreach ($validated as $key => $value) {
                if ($key !== 'patient_photo') { // Skip patient_photo as we've handled it separately
                    $updateData[$key] = $value;
                }
            }

            // Update patient information with only the provided fields
            $user->update($updateData);

            // Add the photo URL to the response if it exists
            $responseData = $user->toArray();
            if ($user->patient_photo) {
                $responseData['patient_photo_url'] = $this->s3Service->getFileUrl($user->patient_photo);
            }

            Log::info("[$requestId] Patient information updated successfully", [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);

            return response()->json([
                'message' => 'Patient information updated successfully',
                'patient' => $responseData
            ], 200);

        } catch (ValidationException $e) {
            Log::error("[$requestId] Validation error", [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("[$requestId] Error updating patient information", [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Error updating patient information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register or link an implant to the patient
     */

    /**
     * Register or link an implant to the patient
     */

    //commenting in 01-05-2025 (integrating the inventory here)
    //  public function registerImplant(Request $request)
    // {
    //     $requestId = uniqid('req_');
    //     Log::info("[$requestId] Starting implant registration process", [
    //         'user_id' => $request->user() ? $request->user()->id : 'no user',
    //         'request_method' => $request->method(),
    //         'request_ip' => $request->ip()
    //     ]);

    //     try {
    //         $user = $request->user();
    //         Log::info("[$requestId] User retrieved", ['user_id' => $user->id, 'email' => $user->email]);

    //         Log::info("[$requestId] Starting validation for implant");
    //         $validated = $request->validate([
    //             // Common fields
    //             'pre_feb_2022' => 'required|boolean',
    //             'ipg_serial_number' => 'required|string',
    //             'secret_key' => [
    //                     'nullable',
    //                     Rule::exists('implants', 'secret_key')->where(function ($query) use ($request) {
    //                         $query->where('ipg_serial_number', $request->ipg_serial_number);
    //                     }),
    //                 ],

    //             // Conditional validation based on pre_feb_2022
    //             'ipg_model_number' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'implantation_date' => 'required_if:pre_feb_2022,1|date|nullable',
    //             'ipg_model' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'hospital_state' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'hospital_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'doctor_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'channel_partner' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'therapy_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'device_name' => 'required_if:pre_feb_2022,1|string|nullable',
    //             'patient_id_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //             'warranty_card' => 'required_if:pre_feb_2022,1|file|nullable',
    //             'interrogation_report' => 'required_if:pre_feb_2022,1|file|nullable',

    //             // New validation for ra_rv_leads
    //             'ra_rv_leads' => 'nullable|array',
    //             'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
    //             'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',

    //             'has_ra_rv_lead' => 'nullable|boolean',
    //             'has_extra_lead' => 'nullable|boolean',
    //             'csp_lead_model' => 'nullable|string',
    //             'csp_catheter_model' => 'nullable|string',
    //             'csp_lead_serial' => 'nullable|string',
    //         ]);

    //         Log::info("[$requestId] Implant validation successful");

    //         // Log key request data for debugging
    //         $logData = [
    //             'ipg_serial_number' => $validated['ipg_serial_number'],
    //             'pre_feb_2022' => $validated['pre_feb_2022'] ? 'Yes' : 'No',
    //             'has_ra_rv_lead' => $request->has('has_ra_rv_lead') ? $validated['has_ra_rv_lead'] ? 'Yes' : 'No' : 'Not provided',
    //             'has_extra_lead' => $request->has('has_extra_lead') ? $validated['has_extra_lead'] ? 'Yes' : 'No' : 'Not provided'
    //         ];

    //         if ($request->has('ra_rv_leads')) {
    //             $logData['ra_rv_leads_count'] = is_array($validated['ra_rv_leads']) ? count($validated['ra_rv_leads']) : 'Not an array';
    //             $logData['ra_rv_leads'] = $validated['ra_rv_leads'];
    //         } else {
    //             $logData['ra_rv_leads'] = 'Not provided';
    //         }

    //         Log::info("[$requestId] Request data", $logData);

    //         if ($validated['pre_feb_2022']) {
    //             Log::info("[$requestId] Processing pre-Feb 2022 implant for admin approval", ['ipg_serial' => $validated['ipg_serial_number']]);

    //             // Check if there's already a pending request for this serial number for this patient
    //             $existingPending = PendingImplant::where('patient_id', $user->id)
    //                 ->where('ipg_serial_number', $validated['ipg_serial_number'])
    //                 ->where('status', 'pending')
    //                 ->first();

    //             if ($existingPending) {
    //                 Log::info("[$requestId] Found existing pending request for this implant", [
    //                     'pending_id' => $existingPending->id,
    //                     'created_at' => $existingPending->created_at
    //                 ]);

    //                 return response()->json([
    //                     'message' => 'You already have a pending request for this implant',
    //                     'pending_implant_id' => $existingPending->id,
    //                     'submitted_at' => $existingPending->created_at
    //                 ], 409); // Conflict
    //             }

    //             // Prepare data for pending implant
    //             $pendingImplantData = [
    //                 'patient_id' => $user->id,
    //                 'pre_feb_2022' => true,
    //                 'ipg_serial_number' => $validated['ipg_serial_number'],
    //                 'implantation_date' => $validated['implantation_date'],
    //                 'ipg_model' => $validated['ipg_model'],
    //                 'ipg_model_number' => $validated['ipg_model_number'],
    //                 'hospital_state' => $validated['hospital_state'],
    //                 'hospital_name' => $validated['hospital_name'],
    //                 'doctor_name' => $validated['doctor_name'],
    //                 'channel_partner' => $validated['channel_partner'],
    //                 'therapy_name' => $validated['therapy_name'],
    //                 'device_name' => $validated['device_name'],
    //                 'has_ra_rv_lead' => $validated['has_ra_rv_lead'] ?? null,
    //                 'has_extra_lead' => $validated['has_extra_lead'] ?? null,
    //                 'csp_lead_model' => $validated['csp_lead_model'] ?? null,
    //                 'csp_catheter_model' => $validated['csp_catheter_model'] ?? null,
    //                 'csp_lead_serial' => $validated['csp_lead_serial'] ?? null,
    //                 'lead_brand' => 'Biotronik',
    //                 'status' => 'pending'
    //             ];

    //             Log::info("[$requestId] Files in request", [
    //                 'has_patient_id_card' => $request->hasFile('patient_id_card'),
    //                 'has_warranty_card' => $request->hasFile('warranty_card'),
    //                 'has_interrogation_report' => $request->hasFile('interrogation_report'),
    //                 'all_files' => $request->allFiles()
    //             ]);
    //             // Store file uploads if provided using S3
    //             if ($request->hasFile('patient_id_card')) {
    //                 $file = $request->file('patient_id_card');
    //                 $s3Filename = 'backend-images/biotronik/patient_id_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $dbFilename = 'biotronik/patient_id_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
    //                 $pendingImplantData['patient_id_card'] = $dbFilename;

    //                 Log::info("[$requestId] Patient ID card uploaded to S3", [
    //                     'path' => $s3Filename,
    //                     'result' => $path
    //                 ]);
    //             }

    //             if ($request->hasFile('warranty_card')) {
    //                 $file = $request->file('warranty_card');
    //                 $s3Filename = 'backend-images/biotronik/warranty_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $dbFilename = 'biotronik/warranty_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
    //                 $pendingImplantData['warranty_card'] = $dbFilename;

    //                 Log::info("[$requestId] Warranty card uploaded to S3", [
    //                     'path' => $s3Filename,
    //                     'result' => $path
    //                 ]);
    //             }

    //             if ($request->hasFile('interrogation_report')) {
    //                 $file = $request->file('interrogation_report');
    //                 $s3Filename = 'backend-images/biotronik/interrogation_reports/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $dbFilename = 'biotronik/interrogation_reports/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
    //                 $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
    //                 $pendingImplantData['interrogation_report'] = $dbFilename;

    //                 Log::info("[$requestId] Interrogation report uploaded to S3", [
    //                     'path' => $s3Filename,
    //                     'result' => $path
    //                 ]);
    //             }
    //             // Handle ra_rv_leads as JSON
    //             if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads']) && count($validated['ra_rv_leads']) > 0) {
    //                 Log::info("[$requestId] Using ra_rv_leads array from request", [
    //                     'count' => count($validated['ra_rv_leads']),
    //                     'data' => $validated['ra_rv_leads']
    //                 ]);
    //                 $pendingImplantData['ra_rv_leads'] = $validated['ra_rv_leads'];
    //             }
    //             // For backward compatibility, convert individual fields to JSON if provided
    //             elseif ($request->has('ra_rv_lead_model') && isset($validated['ra_rv_lead_model'])) {
    //                 Log::info("[$requestId] Using legacy ra_rv_lead_model field", [
    //                     'model' => $validated['ra_rv_lead_model'],
    //                     'serial' => $validated['ra_rv_lead_serial'] ?? 'Unknown'
    //                 ]);

    //                 $pendingImplantData['ra_rv_leads'] = [
    //                     [
    //                         'model' => $validated['ra_rv_lead_model'],
    //                         'serial' => $validated['ra_rv_lead_serial'] ?? 'Unknown'
    //                     ]
    //                 ];
    //             } else {
    //                 Log::info("[$requestId] No RA/RV lead information provided");
    //             }

    //             // Create pending implant record
    //             $pendingImplant = PendingImplant::create($pendingImplantData);

    //             Log::info("[$requestId] Pre-Feb 2022 implant submitted for admin approval", [
    //                 'pending_implant_id' => $pendingImplant->id,
    //                 'ipg_serial' => $pendingImplant->ipg_serial_number
    //             ]);

    //             // Here you could implement admin notification
    //             // E.g., Mail::to('admin@example.com')->send(new NewPendingImplantNotification($pendingImplant));

    //             return response()->json([
    //                 'message' => 'Pre-February 2022 implant registration submitted for admin approval',
    //                 'pending_implant_id' => $pendingImplant->id,
    //                 'status' => 'pending'
    //             ], 202); // 202 Accepted
    //         } else {
    //             // Continue with your existing post-Feb 2022 implant logic (unchanged)
    //             Log::info("[$requestId] Processing post-Feb 2022 implant linking", [
    //                 'ipg_serial' => $validated['ipg_serial_number'],
    //                 'lead_brand' => 'Biotronik'
    //             ]);

    //             // Handle post-Feb 2022 implant linking
    //             $existingImplant = Implant::where('ipg_serial_number', $validated['ipg_serial_number'])->first();

    //             if (!$existingImplant) {
    //                 Log::info("[$requestId] No existing implant found, creating new record", [
    //                     'ipg_serial' => $validated['ipg_serial_number']
    //                 ]);

    //                 // Create new implant record
    //                 $implantData = [
    //                     'ipg_serial_number' => $validated['ipg_serial_number'],
    //                     'secret_key' => $validated['secret_key'],
    //                     'patient_id' => $user->id,
    //                     'pre_feb_2022' => false,
    //                     'implantation_date' => now(),
    //                     'warranty_expired_at' => now()->addYear(),
    //                     'lead_brand' => 'Biotronik'
    //                 ];

    //                 // Add ra_rv_leads if provided
    //                 if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads'])) {
    //                     Log::info("[$requestId] Adding ra_rv_leads to new implant", [
    //                         'leads_data' => $validated['ra_rv_leads']
    //                     ]);
    //                     $implantData['ra_rv_leads'] = $validated['ra_rv_leads'];
    //                 }

    //                 $implant = Implant::create($implantData);
    //                 Log::info("[$requestId] New implant created successfully", [
    //                     'implant_id' => $implant->id,
    //                     'ipg_serial' => $implant->ipg_serial_number
    //                 ]);

    //                 $message = 'New implant registered successfully';

    //                 Log::info("[$requestId] Returning successful response for new implant", [
    //                     'status' => 201,
    //                     'implant_id' => $implant->id
    //                 ]);

    //                 return response()->json([
    //                     'message' => $message,
    //                     'implant' => $implant
    //                 ], 201);
    //             }

    //             Log::info("[$requestId] Existing implant found", [
    //                 'implant_id' => $existingImplant->id,
    //                 'current_patient_id' => $existingImplant->patient_id
    //             ]);

    //             if ($existingImplant->patient_id !== null) {
    //                 Log::warning("[$requestId] Implant already associated with a patient", [
    //                     'implant_id' => $existingImplant->id,
    //                     'existing_patient_id' => $existingImplant->patient_id,
    //                     'requesting_patient_id' => $user->id
    //                 ]);

    //                 return response()->json([
    //                     'message' => 'Implant is already associated with another patient'
    //                 ], 400);
    //             }

    //             Log::info("[$requestId] Linking existing implant to patient", [
    //                 'implant_id' => $existingImplant->id,
    //                 'patient_id' => $user->id
    //             ]);

    //             $existingImplant->patient_id = $user->id;

    //             // Update ra_rv_leads if provided
    //             if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads'])) {
    //                 Log::info("[$requestId] Updating ra_rv_leads for existing implant", [
    //                     'leads_data' => $validated['ra_rv_leads']
    //                 ]);
    //                 $existingImplant->ra_rv_leads = $validated['ra_rv_leads'];
    //             }

    //             $existingImplant->save();
    //             Log::info("[$requestId] Existing implant updated successfully");

    //             $implant = $existingImplant;
    //             $message = 'Existing implant linked to patient successfully';

    //             return response()->json([
    //                 'message' => $message,
    //                 'implant' => $implant
    //             ], 201);
    //         }

    //     } catch (ValidationException $e) {
    //         Log::error("[$requestId] Validation error", [
    //             'errors' => $e->errors(),
    //         ]);
    //         throw $e;
    //     } catch (\Exception $e) {
    //         Log::error("[$requestId] Error during implant registration", [
    //             'error_message' => $e->getMessage(),
    //             'error_trace' => $e->getTraceAsString(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine()
    //         ]);

    //         return response()->json([
    //             'message' => 'Error registering implant',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function registerImplant(Request $request)
    {
        $requestId = uniqid('req_');
        Log::info("[$requestId] Starting implant registration process", [
            'user_id' => $request->user() ? $request->user()->id : 'no user',
            'request_method' => $request->method(),
            'request_ip' => $request->ip()
        ]);

        try {
            $user = $request->user();
            Log::info("[$requestId] User retrieved", ['user_id' => $user->id, 'email' => $user->email]);

            Log::info("[$requestId] Starting validation for implant");
            $validated = $request->validate([
                // Common fields
                'pre_feb_2022' => 'required|boolean',
                'ipg_serial_number' => 'required|string',
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

                // New validation for ra_rv_leads
                'ra_rv_leads' => 'nullable|array',
                'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
                'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',

                'has_ra_rv_lead' => 'nullable|boolean',
                'has_extra_lead' => 'nullable|boolean',
                'csp_lead_model' => 'nullable|string',
                'csp_catheter_model' => 'nullable|string',
                'csp_lead_serial' => 'nullable|string',
            ]);

            Log::info("[$requestId] Implant validation successful");

            // Log key request data for debugging
            $logData = [
                'ipg_serial_number' => $validated['ipg_serial_number'],
                'pre_feb_2022' => $validated['pre_feb_2022'] ? 'Yes' : 'No',
                'has_ra_rv_lead' => $request->has('has_ra_rv_lead') ? $validated['has_ra_rv_lead'] ? 'Yes' : 'No' : 'Not provided',
                'has_extra_lead' => $request->has('has_extra_lead') ? $validated['has_extra_lead'] ? 'Yes' : 'No' : 'Not provided'
            ];

            if ($request->has('ra_rv_leads')) {
                $logData['ra_rv_leads_count'] = is_array($validated['ra_rv_leads']) ? count($validated['ra_rv_leads']) : 'Not an array';
                $logData['ra_rv_leads'] = $validated['ra_rv_leads'];
            } else {
                $logData['ra_rv_leads'] = 'Not provided';
            }

            Log::info("[$requestId] Request data", $logData);

            if ($validated['pre_feb_2022']) {
                Log::info("[$requestId] Processing pre-Feb 2022 implant for admin approval", ['ipg_serial' => $validated['ipg_serial_number']]);

                // Check if there's already a pending request for this serial number for this patient
                $existingPending = PendingImplant::where('patient_id', $user->id)
                    ->where('ipg_serial_number', $validated['ipg_serial_number'])
                    ->where('status', 'pending')
                    ->first();

                if ($existingPending) {
                    Log::info("[$requestId] Found existing pending request for this implant", [
                        'pending_id' => $existingPending->id,
                        'created_at' => $existingPending->created_at
                    ]);

                    return response()->json([
                        'message' => 'You already have a pending request for this implant',
                        'pending_implant_id' => $existingPending->id,
                        'submitted_at' => $existingPending->created_at
                    ], 409); // Conflict
                }

                // Prepare data for pending implant
                $pendingImplantData = [
                    'patient_id' => $user->id,
                    'pre_feb_2022' => true,
                    'ipg_serial_number' => $validated['ipg_serial_number'],
                    'implantation_date' => $validated['implantation_date'],
                    'ipg_model' => $validated['ipg_model'],
                    'ipg_model_number' => $validated['ipg_model_number'],
                    'hospital_state' => $validated['hospital_state'],
                    'hospital_name' => $validated['hospital_name'],
                    'doctor_name' => $validated['doctor_name'],
                    'channel_partner' => $validated['channel_partner'],
                    'therapy_name' => $validated['therapy_name'],
                    'device_name' => $validated['device_name'],
                    'has_ra_rv_lead' => $validated['has_ra_rv_lead'] ?? null,
                    'has_extra_lead' => $validated['has_extra_lead'] ?? null,
                    'csp_lead_model' => $validated['csp_lead_model'] ?? null,
                    'csp_catheter_model' => $validated['csp_catheter_model'] ?? null,
                    'csp_lead_serial' => $validated['csp_lead_serial'] ?? null,
                    'lead_brand' => 'Biotronik',
                    'status' => 'pending'
                ];

                Log::info("[$requestId] Files in request", [
                    'has_patient_id_card' => $request->hasFile('patient_id_card'),
                    'has_warranty_card' => $request->hasFile('warranty_card'),
                    'has_interrogation_report' => $request->hasFile('interrogation_report'),
                    'all_files' => $request->allFiles()
                ]);
                // Store file uploads if provided using S3
                if ($request->hasFile('patient_id_card')) {
                    $file = $request->file('patient_id_card');
                    $s3Filename = 'backend-images/biotronik/patient_id_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $dbFilename = 'biotronik/patient_id_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
                    $pendingImplantData['patient_id_card'] = $dbFilename;

                    Log::info("[$requestId] Patient ID card uploaded to S3", [
                        'path' => $s3Filename,
                        'result' => $path
                    ]);
                }

                if ($request->hasFile('warranty_card')) {
                    $file = $request->file('warranty_card');
                    $s3Filename = 'backend-images/biotronik/warranty_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $dbFilename = 'biotronik/warranty_cards/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
                    $pendingImplantData['warranty_card'] = $dbFilename;

                    Log::info("[$requestId] Warranty card uploaded to S3", [
                        'path' => $s3Filename,
                        'result' => $path
                    ]);
                }

                if ($request->hasFile('interrogation_report')) {
                    $file = $request->file('interrogation_report');
                    $s3Filename = 'backend-images/biotronik/interrogation_reports/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $dbFilename = 'biotronik/interrogation_reports/' . time() . '_' . $user->id . '_' . $file->getClientOriginalName();
                    $path = Storage::disk('s3')->put($s3Filename, file_get_contents($file));
                    $pendingImplantData['interrogation_report'] = $dbFilename;

                    Log::info("[$requestId] Interrogation report uploaded to S3", [
                        'path' => $s3Filename,
                        'result' => $path
                    ]);
                }
                // Handle ra_rv_leads as JSON
                if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads']) && count($validated['ra_rv_leads']) > 0) {
                    Log::info("[$requestId] Using ra_rv_leads array from request", [
                        'count' => count($validated['ra_rv_leads']),
                        'data' => $validated['ra_rv_leads']
                    ]);
                    $pendingImplantData['ra_rv_leads'] = $validated['ra_rv_leads'];
                }
                // For backward compatibility, convert individual fields to JSON if provided
                elseif ($request->has('ra_rv_lead_model') && isset($validated['ra_rv_lead_model'])) {
                    Log::info("[$requestId] Using legacy ra_rv_lead_model field", [
                        'model' => $validated['ra_rv_lead_model'],
                        'serial' => $validated['ra_rv_lead_serial'] ?? 'Unknown'
                    ]);

                    $pendingImplantData['ra_rv_leads'] = [
                        [
                            'model' => $validated['ra_rv_lead_model'],
                            'serial' => $validated['ra_rv_lead_serial'] ?? 'Unknown'
                        ]
                    ];
                } else {
                    Log::info("[$requestId] No RA/RV lead information provided");
                }

                // Create pending implant record
                $pendingImplant = PendingImplant::create($pendingImplantData);

                Log::info("[$requestId] Pre-Feb 2022 implant submitted for admin approval", [
                    'pending_implant_id' => $pendingImplant->id,
                    'ipg_serial' => $pendingImplant->ipg_serial_number
                ]);

                // Here you could implement admin notification
                // E.g., Mail::to('admin@example.com')->send(new NewPendingImplantNotification($pendingImplant));

                return response()->json([
                    'message' => 'Pre-February 2022 implant registration submitted for admin approval',
                    'pending_implant_id' => $pendingImplant->id,
                    'status' => 'pending'
                ], 202); // 202 Accepted
            } else {
                try {
                    // Start database transaction for atomicity
                    DB::beginTransaction();

                    // Continue with post-Feb 2022 implant logic
                    Log::info("[$requestId] Processing post-Feb 2022 implant linking", [
                        'ipg_serial' => $validated['ipg_serial_number'],
                        'lead_brand' => 'Biotronik'
                    ]);

                    // Handle post-Feb 2022 implant linking
                    $existingImplant = Implant::where('ipg_serial_number', $validated['ipg_serial_number'])->first();

                    if (!$existingImplant) {
                        Log::info("[$requestId] No existing implant found, creating new record", [
                            'ipg_serial' => $validated['ipg_serial_number']
                        ]);

                        // Create new implant record
                        $implantData = [
                            'ipg_serial_number' => $validated['ipg_serial_number'],
                            'secret_key' => $validated['secret_key']?? null,
                            'patient_id' => $user->id,
                            'pre_feb_2022' => false,
                            'implantation_date' => now(),
                            'warranty_expired_at' => now()->addYear(),
                            'lead_brand' => 'Biotronik'
                        ];

                        // Add ra_rv_leads if provided
                        if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads'])) {
                            Log::info("[$requestId] Adding ra_rv_leads to new implant", [
                                'leads_data' => $validated['ra_rv_leads']
                            ]);
                            $implantData['ra_rv_leads'] = $validated['ra_rv_leads'];
                        }

                        $implant = Implant::create($implantData);
                        Log::info("[$requestId] New implant created successfully", [
                            'implant_id' => $implant->id,
                            'ipg_serial' => $implant->ipg_serial_number
                        ]);

                        // Update IPG serials table to mark this IPG as implanted
                        $this->updateIpgImplantationStatus(
                            $validated['ipg_serial_number'],
                            $implant->ipg_model_number ?? null,
                            $user->id // Patient ID
                        );

                        // Also handle any lead serial numbers if present in ra_rv_leads
                        // if (isset($validated['ra_rv_leads']) && is_array($validated['ra_rv_leads'])) {
                        //     foreach ($validated['ra_rv_leads'] as $lead) {
                        //         if (!empty($lead['serial'])) {
                        //             $this->updateLeadImplantationStatus(
                        //                 $lead['serial'],
                        //                 $lead['model'] ?? null,
                        //                 $user->id
                        //             );
                        //         }
                        //     }
                        // }

                        // Handle CSP lead if provided
                        // if (!empty($validated['csp_lead_serial'])) {
                        //     $this->updateLeadImplantationStatus(
                        //         $validated['csp_lead_serial'],
                        //         $validated['csp_lead_model'] ?? null,
                        //         $user->id
                        //     );
                        // }

                        $message = 'New implant registered successfully';
                        Log::info("[$requestId] Returning successful response for new implant", [
                            'status' => 201,
                            'implant_id' => $implant->id
                        ]);

                        DB::commit();

                        return response()->json([
                            'message' => $message,
                            'implant' => $implant
                        ], 201);
                    }

                    Log::info("[$requestId] Existing implant found", [
                        'implant_id' => $existingImplant->id,
                        'current_patient_id' => $existingImplant->patient_id
                    ]);

                    if ($existingImplant->patient_id !== null) {
                        Log::warning("[$requestId] Implant already associated with a patient", [
                            'implant_id' => $existingImplant->id,
                            'existing_patient_id' => $existingImplant->patient_id,
                            'requesting_patient_id' => $user->id
                        ]);

                        DB::rollBack();
                        return response()->json([
                            'message' => 'Implant is already associated with another patient'
                        ], 400);
                    }

                    Log::info("[$requestId] Linking existing implant to patient", [
                        'implant_id' => $existingImplant->id,
                        'patient_id' => $user->id
                    ]);

                    $existingImplant->patient_id = $user->id;

                    // Update ra_rv_leads if provided
                    if ($request->has('ra_rv_leads') && is_array($validated['ra_rv_leads'])) {
                        Log::info("[$requestId] Updating ra_rv_leads for existing implant", [
                            'leads_data' => $validated['ra_rv_leads']
                        ]);
                        $existingImplant->ra_rv_leads = $validated['ra_rv_leads'];
                    }

                    $existingImplant->save();
                    Log::info("[$requestId] Existing implant updated successfully");

                    // Update IPG serials table to mark this IPG as implanted
                    $this->updateIpgImplantationStatus(
                        $existingImplant->ipg_serial_number,
                        $existingImplant->ipg_model_number ?? null,
                        $user->id // Patient ID
                    );


                    // Also handle any lead serial numbers if present in ra_rv_leads
                    if (isset($validated['ra_rv_leads']) && is_array($validated['ra_rv_leads'])) {
                        foreach ($validated['ra_rv_leads'] as $lead) {
                            if (!empty($lead['serial'])) {
                                // Update lead implantation status in the ipg_leads table
                                $this->updateLeadImplantationStatus(
                                    $lead['serial'],
                                    $lead['model'] ?? null,
                                    $user->id
                                );
                            }
                        }
                    }

                    // Also check for leads in the existing implant record if it exists
                    if ($existingImplant && isset($existingImplant->ra_rv_leads) && is_array($existingImplant->ra_rv_leads)) {
                        foreach ($existingImplant->ra_rv_leads as $lead) {
                            if (!empty($lead['serial'])) {
                                // Mark leads from the existing implant as well
                                $this->updateLeadImplantationStatus(
                                    $lead['serial'],
                                    $lead['model'] ?? null,
                                    $user->id
                                );
                            }
                        }
                    }



                    // // Handle CSP lead if provided
                    // if (!empty($validated['csp_lead_serial'])) {
                    //     $this->updateLeadImplantationStatus(
                    //         $validated['csp_lead_serial'],
                    //         $validated['csp_lead_model'] ?? null,
                    //         $user->id
                    //     );
                    // }

                    $implant = $existingImplant;
                    $message = 'Existing implant linked to patient successfully';

                    DB::commit();

                    return response()->json([
                        'message' => $message,
                        'implant' => $implant
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("[$requestId] Error during IPG registration: " . $e->getMessage(), [
                        'exception' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);

                    throw $e; // Re-throw to be caught by the parent try-catch
                }
            }
        } catch (ValidationException $e) {
            Log::error("[$requestId] Validation error", [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("[$requestId] Error during implant registration", [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Error registering implant',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkIfPatientHasImplant(Request $request)
    {
        try {
            $user = $request->user();

            // Get user profile data
            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'address' => $user->address,
                'state' => $user->state,
                'city' => $user->city,
                'pin_code' => $user->pin_code,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                // 'patient_photo' => $user->patient_photo,
                'patient_photo' => $user->patient_photo ? $this->s3Service->getFileUrl($user->patient_photo) : null,

                // Relative information
                'relative_name' => $user->relative_name,
                'relative_relation' => $user->relative_relation,
                'relative_gender' => $user->relative_gender,
                'relative_address' => $user->relative_address,
                'relative_state' => $user->relative_state,
                'relative_city' => $user->relative_city,
                'relative_pin_code' => $user->relative_pin_code,
                'relative_email' => $user->relative_email,
                'relative_phone' => $user->relative_phone,
            ];

            // Check if basic profile fields are filled
            $requiredProfileFields = [
                'name',
                'date_of_birth',
                'gender',
                'address',
                'state',
                'city',
                'pin_code'
            ];

            $basicProfileComplete = true;
            foreach ($requiredProfileFields as $field) {
                if (empty($profileData[$field])) {
                    $basicProfileComplete = false;
                    break;
                }
            }

            // Check if relative information is filled
            $requiredRelativeFields = [
                'relative_name',
                'relative_relation',
                'relative_gender',
                'relative_address',
                'relative_state',
                'relative_city',
                'relative_pin_code',
                'relative_phone'
            ];

            $relativeInfoComplete = true;
            foreach ($requiredRelativeFields as $field) {
                if (empty($profileData[$field])) {
                    $relativeInfoComplete = false;
                    break;
                }
            }

            // Determine overall profile completion status
            $profileStatus = [
                'basic_profile_complete' => $basicProfileComplete,
                'relative_info_complete' => $relativeInfoComplete,
                'profile_complete' => $basicProfileComplete && $relativeInfoComplete,
            ];


            $IsOldImplant = false;
            $PendingOldImplant = PendingImplant::where('patient_id', $user->id)
                ->where(function ($query) {
                    $query->where('status', 'pending')
                        ->orWhere('status', 'approved');
                })
                ->latest()
                ->first();

            $IsOldImplant = ($PendingOldImplant !== null);

            $oldImplant = $PendingOldImplant ? [
                'is_old_implant' => $IsOldImplant,
                'status' => $PendingOldImplant->status,
                'ipg_serial_number' => $PendingOldImplant->ipg_serial_number
            ] : null;

            // Check if user has any implants
            if (!$user->implant()->exists()) {
                return response()->json([
                    'message' => 'No implants found for this patient',
                    'has_implant' => false,
                    'profile_data' => $profileData,
                    'profile_status' => $profileStatus,
                    'is_old_implant' => $IsOldImplant,
                    'old_implant_details' => $oldImplant,
                ], 200);
            }

            // Get all implants for the patient, ordered by created date (newest first)
            $implants = Implant::where('patient_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get([
                    'id',
                    'ipg_serial_number',
                    'ipg_model',
                    'ipg_model_number',
                    'implantation_date',
                    'device_name',
                    'active',
                    'implantation_date',
                    // 'pre_feb_2022'

                ]);





            // Find the active implant
            $activeImplant = $implants->firstWhere('active', true);

            return response()->json([
                'message' => 'Patient implants retrieved successfully',
                'has_implant' => true,
                'active_implant' => $activeImplant,
                'all_implants' => $implants,
                'implant_count' => $implants->count(),
                'profile_data' => $profileData,
                'profile_status' => $profileStatus,
                'is_old_implant' => $IsOldImplant,
                'old_implant_details' => $oldImplant

            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving patient implants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $requestId = uniqid('req_');
        Log::info("[$requestId] Starting implant registration process", [
            'user_id' => $request->user() ? $request->user()->id : 'no user',
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'request_ip' => $request->ip()
        ]);

        try {
            // First check if user already has an implant
            $user = $request->user();
            Log::info("[$requestId] User retrieved", ['user_id' => $user->id, 'email' => $user->email]);

            Log::info("[$requestId] Starting validation");
            $validated = $request->validate([
                // Basic patient fields
                'name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:Male,Female,Other',
                'address' => 'required|string',
                'state' => 'required|string',
                'city' => 'required|string',
                'pin_code' => 'required|string',

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

                // New validation for ra_rv_leads
                'ra_rv_leads' => 'nullable|array',
                'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
                'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',

                'has_ra_rv_lead' => 'nullable|boolean',
                'has_extra_lead' => 'nullable|boolean',
                'csp_lead_model' => 'nullable|string',
                'csp_catheter_model' => 'nullable|string',
                'csp_lead_serial' => 'nullable|string',
            ]);
            Log::info("[$requestId] Validation successful");

            // Log key request data for debugging
            $logData = [
                'ipg_serial_number' => $request->ipg_serial_number,
                'pre_feb_2022' => $request->pre_feb_2022 ? 'Yes' : 'No',
                'has_ra_rv_lead' => $request->has_ra_rv_lead ? 'Yes' : 'No',
                'has_extra_lead' => $request->has_extra_lead ? 'Yes' : 'No'
            ];

            if ($request->has('ra_rv_leads')) {
                $logData['ra_rv_leads_count'] = is_array($request->ra_rv_leads) ? count($request->ra_rv_leads) : 'Not an array';
                $logData['ra_rv_leads'] = $request->ra_rv_leads;
            } else {
                $logData['ra_rv_leads'] = 'Not provided';
            }

            Log::info("[$requestId] Request data", $logData);

            Log::info("[$requestId] Updating user information", ['user_id' => $user->id]);
            // Update patient information
            $user->update([
                'patient_photo' => $request->file('patient_photo'),
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
            Log::info("[$requestId] User information updated successfully", ['user_id' => $user->id]);

            if ($request->pre_feb_2022) {
                Log::info("[$requestId] Processing pre-Feb 2022 implant", ['ipg_serial' => $request->ipg_serial_number]);

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
                    'has_ra_rv_lead' => $request->has_ra_rv_lead,
                    'has_extra_lead' => $request->has_extra_lead,
                    'csp_lead_model' => $request->csp_lead_model,
                    'csp_catheter_model' => $request->csp_catheter_model,
                    'csp_lead_serial' => $request->csp_lead_serial,
                    'warranty_expired_at' => Carbon::parse($request->implantation_date)->addMonths(3),
                    'lead_brand' => 'Biotronik'
                ];


                Log::debug("[$requestId] Base implant data prepared", [
                    'data' => array_diff_key($implantData, ['ra_rv_leads' => ''])
                ]);

                // Handle ra_rv_leads as JSON
                if ($request->has('ra_rv_leads') && is_array($request->ra_rv_leads) && count($request->ra_rv_leads) > 0) {
                    Log::info("[$requestId] Using ra_rv_leads array from request", [
                        'count' => count($request->ra_rv_leads),
                        'data' => $request->ra_rv_leads
                    ]);
                    $implantData['ra_rv_leads'] = $request->ra_rv_leads;
                }
                // For backward compatibility, convert individual fields to JSON if provided
                elseif ($request->has('ra_rv_lead_model') && $request->ra_rv_lead_model) {
                    Log::info("[$requestId] Using legacy ra_rv_lead_model field", [
                        'model' => $request->ra_rv_lead_model,
                        'serial' => $request->ra_rv_lead_serial ?? 'Unknown'
                    ]);

                    $implantData['ra_rv_leads'] = [
                        [
                            'model' => $request->ra_rv_lead_model,
                            'serial' => $request->ra_rv_lead_serial ?? 'Unknown'
                        ]
                    ];
                } else {
                    Log::info("[$requestId] No RA/RV lead information provided");
                }

                Log::info("[$requestId] Creating implant record for user", [
                    'user_id' => $user->id,
                    'ipg_serial' => $implantData['ipg_serial_number']
                ]);

                $implant = $user->implant()->create($implantData);

                Log::info("[$requestId] Implant record created successfully", [
                    'implant_id' => $implant->id,
                    'ipg_serial' => $implant->ipg_serial_number
                ]);

                $message = 'Patient and implant information saved successfully';
            } else {
                Log::info("[$requestId] Processing post-Feb 2022 implant linking", [
                    'ipg_serial' => $request->ipg_serial_number,
                    'lead_brand' => 'Biotronik'
                ]);

                // Handle post-Feb 2022 implant linking
                $existingImplant = Implant::where('ipg_serial_number', $request->ipg_serial_number)->first();

                if (!$existingImplant) {
                    Log::info("[$requestId] No existing implant found, creating new record", [
                        'ipg_serial' => $request->ipg_serial_number
                    ]);

                    // Create new implant record
                    $implantData = [
                        'ipg_serial_number' => $request->ipg_serial_number,
                        'secret_key' => $request->secret_key,
                        'patient_id' => $user->id,
                        'pre_feb_2022' => false,
                        'implantation_date' => now(),
                        // 'warranty_expired_at' => now()->addMonths(3),
                        //change from 3 months to 1year as perdiscussion
                        'warranty_expired_at' => now()->addYear(),
                        'lead_brand' => 'Biotronik'
                    ];

                    // Add ra_rv_leads if provided
                    if ($request->has('ra_rv_leads') && is_array($request->ra_rv_leads)) {
                        Log::info("[$requestId] Adding ra_rv_leads to new implant", [
                            'leads_data' => $request->ra_rv_leads
                        ]);
                        $implantData['ra_rv_leads'] = $request->ra_rv_leads;
                    }

                    $implant = Implant::create($implantData);
                    Log::info("[$requestId] New implant created successfully", [
                        'implant_id' => $implant->id,
                        'ipg_serial' => $implant->ipg_serial_number
                    ]);

                    $message = 'New implant registered successfully';

                    Log::info("[$requestId] Returning successful response for new implant", [
                        'status' => 201,
                        'implant_id' => $implant->id
                    ]);

                    return response()->json([
                        'message' => $message,
                        'implant' => $implant
                    ], 201);
                }

                Log::info("[$requestId] Existing implant found", [
                    'implant_id' => $existingImplant->id,
                    'current_patient_id' => $existingImplant->patient_id
                ]);

                if ($existingImplant->patient_id !== null) {
                    Log::warning("[$requestId] Implant already associated with a patient", [
                        'implant_id' => $existingImplant->id,
                        'existing_patient_id' => $existingImplant->patient_id,
                        'requesting_patient_id' => $user->id
                    ]);

                    return response()->json([
                        'message' => 'Implant is already associated with another patient'
                    ], 400);
                }

                Log::info("[$requestId] Linking existing implant to patient", [
                    'implant_id' => $existingImplant->id,
                    'patient_id' => $user->id
                ]);

                $existingImplant->patient_id = $user->id;

                // Update ra_rv_leads if provided
                if ($request->has('ra_rv_leads') && is_array($request->ra_rv_leads)) {
                    Log::info("[$requestId] Updating ra_rv_leads for existing implant", [
                        'leads_data' => $request->ra_rv_leads
                    ]);
                    $existingImplant->ra_rv_leads = $request->ra_rv_leads;
                }

                $existingImplant->save();
                Log::info("[$requestId] Existing implant updated successfully");

                $implant = $existingImplant;
                $message = 'Existing implant linked to patient successfully';
            }

            Log::info("[$requestId] Registration process completed successfully", [
                'patient_id' => $user->id,
                'implant_id' => $implant->id
            ]);

            return response()->json([
                'message' => $message,
                'patient' => $user,
                'implant' => $implant
            ], 201);

        } catch (ValidationException $e) {
            Log::error("[$requestId] Validation error", [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("[$requestId] Error during implant registration", [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Error saving information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //commenting this on 1 May to test the alternative (updated lead serial)
    // public function submitByEngineer(Request $request)
    // {
    //     $validated = $request->validate([
    //         'hospital_name' => 'required|string',
    //         'doctor_name' => 'required|string',
    //         'channel_partner' => 'required|string',
    //         'therapy_name' => 'required|string',
    //         'device_name' => 'required|string',
    //         'implantation_date' => 'required|date',
    //         'ipg_serial_number' => 'required|string',
    //         'ipg_model' => 'required|string',
    //         'ipg_model_number' => 'required|string',
    //         'ra_rv_lead_model' => 'nullable|string',
    //         'ra_rv_lead_serial' => 'nullable|string',
    //         'has_ra_rv_lead' => 'nullable|boolean',
    //         'state' => 'required|string',
    //         'csp_catheter_model' => 'nullable|string',
    //         'has_extra_lead' => 'nullable|boolean',
    //         'csp_lead_model' => 'nullable|string',
    //         'csp_lead_serial' => 'nullable|string',
    //         'ra_rv_leads' => 'nullable|array',
    //         'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
    //         'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',

    //     ]);

    //     try {
    //         $user = $request->user();
    //         $implant = Implant::where('ipg_serial_number', $validated['ipg_serial_number'])->first();
    //         $isNewImplant = !$implant;

    //         $implantData = [
    //             'pre_feb_2022' => false,
    //             'hospital_name' => $validated['hospital_name'],
    //             'doctor_name' => $validated['doctor_name'],
    //             'channel_partner' => $validated['channel_partner'],
    //             'therapy_name' => $validated['therapy_name'],
    //             'device_name' => $validated['device_name'],
    //             'implantation_date' => $validated['implantation_date'],
    //             // 'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
    //             'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addYear(),
    //             'ipg_model' => $validated['ipg_model'],
    //             'hospital_state' => $validated['state'],
    //             'ipg_model_number' => $validated['ipg_model_number'],
    //             'ra_rv_lead_model' => $validated['ra_rv_lead_model'] ?? null,
    //             'ra_rv_lead_serial' => $validated['ra_rv_lead_serial'] ?? null,
    //             'has_ra_rv_lead' => $validated['has_ra_rv_lead'] ?? null,
    //             'csp_catheter_model' => $validated['csp_catheter_model'] ?? null,
    //             'has_extra_lead' => $validated['has_extra_lead'] ?? null,
    //             'csp_lead_model' => $validated['csp_lead_model'] ?? null,
    //             'csp_lead_serial' => $validated['csp_lead_serial'] ?? null,
    //             'user_id' => $user->id,

    //         ];

    //         // Add ra_rv_leads JSON to implant data if present
    //         if (isset($validated['ra_rv_leads']) && is_array($validated['ra_rv_leads']) && count($validated['ra_rv_leads']) > 0) {
    //             $implantData['ra_rv_leads'] = $validated['ra_rv_leads'];
    //             \Log::info("Adding ra_rv_leads to implant", [
    //                 'count' => count($validated['ra_rv_leads']),
    //                 'data' => $validated['ra_rv_leads']
    //             ]);
    //         }

    //         if ($isNewImplant) {
    //             // Create new implant with new secret key
    //             $secretKey = Str::random(16);
    //             $implantData['secret_key'] = $secretKey;
    //             $implantData['ipg_serial_number'] = $validated['ipg_serial_number'];

    //             $implant = Implant::create($implantData);
    //             $message = 'Implant details registered successfully';
    //         } else {
    //             // Update existing implant but keep original secret key and IPG serial number
    //             $implant->update($implantData);
    //             $secretKey = $implant->secret_key;
    //             $message = 'Implant details updated successfully';
    //         }



    //         // Log the implant data after creation/update to verify ra_rv_leads is stored
    //         \Log::info("Implant " . ($isNewImplant ? "created" : "updated") . " with ra_rv_leads", [
    //             'implant_id' => $implant->id,
    //             'ra_rv_leads' => $implant->ra_rv_leads
    //         ]);

    //         return response()->json([
    //             'message' => $message,
    //             'secret_key' => $secretKey,
    //             'ipg_serial_number' => $validated['ipg_serial_number']
    //         ], 201);

    //     } catch (\Exception $e) {
    //         \Log::error("Error managing implant details", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'message' => 'Error managing implant details',
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
            'state' => 'required|string',
            'csp_catheter_model' => 'nullable|string',
            'has_extra_lead' => 'nullable|boolean',
            'csp_lead_model' => 'nullable|string',
            'csp_lead_serial' => 'nullable|string',
            'ra_rv_leads' => 'nullable|array',
            'ra_rv_leads.*.model' => 'required_with:ra_rv_leads|string',
            'ra_rv_leads.*.serial' => 'required_with:ra_rv_leads|string',
            'patient_id' => 'nullable|exists:patients,id', // Allow specifying patient_id
        ]);

        try {
            DB::beginTransaction();

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
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addYear(),
                'ipg_model' => $validated['ipg_model'],
                'hospital_state' => $validated['state'],
                'ipg_model_number' => $validated['ipg_model_number'],
                'ra_rv_lead_model' => $validated['ra_rv_lead_model'] ?? null,
                'ra_rv_lead_serial' => $validated['ra_rv_lead_serial'] ?? null,
                'has_ra_rv_lead' => $validated['has_ra_rv_lead'] ?? null,
                'csp_catheter_model' => $validated['csp_catheter_model'] ?? null,
                'has_extra_lead' => $validated['has_extra_lead'] ?? null,
                'csp_lead_model' => $validated['csp_lead_model'] ?? null,
                'csp_lead_serial' => $validated['csp_lead_serial'] ?? null,
                'user_id' => $user->id,
            ];

            // Add patient_id if provided or available from existing implant
            $patientId = $validated['patient_id'] ?? ($implant ? $implant->patient_id : null);
            if ($patientId) {
                $implantData['patient_id'] = $patientId;
            }

            // Add ra_rv_leads JSON to implant data if present
            if (isset($validated['ra_rv_leads']) && is_array($validated['ra_rv_leads']) && count($validated['ra_rv_leads']) > 0) {
                $implantData['ra_rv_leads'] = $validated['ra_rv_leads'];
                \Log::info("Adding ra_rv_leads to implant", [
                    'count' => count($validated['ra_rv_leads']),
                    'data' => $validated['ra_rv_leads']
                ]);
            }

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

            // Mark the IPG serial as implanted
            $this->updateIpgImplantationStatus(
                $validated['ipg_serial_number'],
                $validated['ipg_model_number'],
                $patientId
            );

            // Update lead_serials information for the implanted leads
            // First, handle single lead if provided via ra_rv_lead_serial
            // if (!empty($validated['ra_rv_lead_serial'])) {
            //     $this->updateLeadImplantationStatus(
            //         $validated['ra_rv_lead_serial'], 
            //         $validated['ra_rv_lead_model'] ?? null, 
            //         $patientId
            //     );
            // }

            // Next, handle any leads in the ra_rv_leads array
            if (isset($validated['ra_rv_leads']) && is_array($validated['ra_rv_leads'])) {
                foreach ($validated['ra_rv_leads'] as $lead) {
                    if (!empty($lead['serial'])) {
                        $this->updateLeadImplantationStatus(
                            $lead['serial'],
                            $lead['model'] ?? null,
                            $patientId
                        );
                    }
                }
            }

            // Handle CSP lead if provided
            if (!empty($validated['csp_lead_serial'])) {
                $this->updateLeadImplantationStatus(
                    $validated['csp_lead_serial'],
                    $validated['csp_lead_model'] ?? null,
                    $patientId
                );
            }

            // Log the implant data after creation/update to verify ra_rv_leads is stored
            \Log::info("Implant " . ($isNewImplant ? "created" : "updated") . " with ra_rv_leads", [
                'implant_id' => $implant->id,
                'ra_rv_leads' => $implant->ra_rv_leads
            ]);

            DB::commit();

            return response()->json([
                'message' => $message,
                'secret_key' => $secretKey,
                'ipg_serial_number' => $validated['ipg_serial_number']
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Error managing implant details", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error managing implant details',
                'error' => $e->getMessage()
            ], 500);
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
                    // Check if model exists
                    $leadModel = \App\Models\LeadModel::where('model_number', $modelNumber)->first();

                    if ($leadModel) {
                        // Create new lead serial record
                        $leadSerial = \App\Models\LeadSerial::create([
                            'serial_number' => $serialNumber,
                            'lead_model_number' => $modelNumber,
                            'is_implanted' => true,
                            'patient_id' => $patientId
                        ]);

                        \Log::info("Created new lead serial record and marked as implanted", [
                            'lead_serial_id' => $leadSerial->id,
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
    public function getImplantAndPatientDetails($implantId)
    {
        try {
            // Load the implant record along with the patient relationship
            $implant = Implant::with('patient')->findOrFail($implantId);

            // Build documents array with AWS S3 URLs if files are available
            $documents = [
                'patient_id_card' => $implant->patient_id_card
                    ? \Storage::disk('s3')->url($implant->patient_id_card)
                    : null,
                'warranty_card' => $implant->warranty_card
                    ? \Storage::disk('s3')->url($implant->warranty_card)
                    : null,
                'interrogation_report' => $implant->interrogation_report
                    ? \Storage::disk('s3')->url($implant->interrogation_report)
                    : null,
            ];

            // Determine status based on pre_feb_2022 flag (adjust as needed)
            $status = $implant->pre_feb_2022 ? 'pending' : 'active';

            // Build patient details array if patient exists
            $patient = null;
            if ($implant->patient) {
                $patient = [
                    'id' => $implant->patient->id,
                    'patient_photo' => $implant->patient->patient_photo
                        ? \Storage::disk('s3')->url($implant->patient->patient_photo)
                        : null,
                    'name' => $implant->patient->name,
                    'date_of_birth' => $implant->patient->date_of_birth,
                    'gender' => $implant->patient->gender,
                    'email' => $implant->patient->email,
                    'phone_number' => $implant->patient->phone_number,
                    'address' => $implant->patient->address,
                    'state' => $implant->patient->state,
                    'city' => $implant->patient->city,
                    'pincode' => $implant->patient->pin_code,
                ];
            }

            // Build implant details array with the desired fields
            $implantData = [
                'pre_feb_2022' => $implant->pre_feb_2022,
                'ipg_serial_number' => $implant->ipg_serial_number,
                'implantation_date' => $implant->implantation_date,
                'ipg_model' => $implant->ipg_model,
                'ipg_model_number' => $implant->ipg_model_number,
                'hospital_state' => $implant->hospital_state,
                'hospital_name' => $implant->hospital_name,
                'doctor_name' => $implant->doctor_name,
                'channel_partner' => $implant->channel_partner,
                'therapy_name' => $implant->therapy_name,
                'device_name' => $implant->device_name,
                'ra_rv_leads' => $implant->ra_rv_leads,
                'has_ra_rv_lead' => $implant->has_ra_rv_lead,
                'has_extra_lead' => $implant->has_extra_lead,
                'csp_lead_model' => $implant->csp_lead_model,
                'csp_catheter_model' => $implant->csp_catheter_model,
                'csp_lead_serial' => $implant->csp_lead_serial,
                'lead_brand' => $implant->lead_brand,
            ];

            return response()->json([
                'message' => 'Implant and patient details retrieved successfully',
                'data' => [
                    'id' => $implant->id,
                    'patient' => $patient,
                    'implant' => $implantData,
                    'documents' => $documents,
                    'status' => $status,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving implant details',
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
                    'id',
                    'secret_key',
                    'ipg_serial_number',
                    'hospital_name',
                    'doctor_name',
                    'hospital_state'


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


    // public function getWarrantyStatus(Request $request)
    // {
    //     $user = $request->user();
    //     $implant = $user->implant;

    //     if (!$implant) {
    //         return response()->json(['message' => 'Implant not found.'], 404);
    //     }

    //     $warrantyExpiration = $implant->warranty_expired_at;
    //     $isExpired = now()->greaterThan($warrantyExpiration);

    //     return response()->json([
    //         'warranty_expiration_date' => $warrantyExpiration,
    //         'is_expired' => $isExpired,
    //     ]);
    // }
    public function getWarrantyStatus(Request $request)
{
    $user = $request->user();

    // Retrieve the latest implant for the user
    $implant = Implant::where('patient_id', $user->id)
        ->latest('created_at')
        ->first();

    if (!$implant) {
        return response()->json(['message' => 'Implant not found.'], 404);
    }

    $warrantyExpiration = $implant->warranty_expired_at;
    $isExpired = $warrantyExpiration ? now()->greaterThan($warrantyExpiration) : true;

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

            // Base validation - now includes replacement_reason and planned_replacement_date for all cases
            $validationRules = [
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'doctor_name' => 'required|string',
                'channel_partner' => 'required|string',
                'replacement_reason' => 'required|string|nullable',
                'planned_replacement_date' => 'nullable|required|date_format:Y-m-d H:i|after:now',
            ];

            $validated = $request->validate($validationRules);

            $replacementData = [
                'patient_id' => $patient->id,
                'implant_id' => $implant->id,
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'channel_partner' => $validated['channel_partner'],
                'replacement_reason' => $validated['replacement_reason'],
                'planned_replacement_date' => $validated['planned_replacement_date'],
                'is_warranty_claim' => $isWarranty, // Set warranty flag based on actual warranty status
            ];

            if ($isWarranty) {
                if ($request->hasFile('interrogation_report')) {
                    $file = $request->file('interrogation_report');
                    // Use S3 service to upload
                    $uploadResult = $this->s3Service->uploadFile(
                        $file,
                        'replacement_requests/interrogation_reports', // Example S3 path
                        $patient->id . '_' . time() // Example unique identifier
                    );
                    if (!$uploadResult['success']) {
                        throw new \Exception('Failed to upload interrogation report: ' . $uploadResult['error']);
                    }
                    $replacementData['interrogation_report_path'] = $uploadResult['db_path'];
                    Log::info("Interrogation report uploaded for warranty claim", ['path' => $uploadResult['s3_path']]);
                }

                if ($request->hasFile('prescription')) {
                    $file = $request->file('prescription');
                    // Use S3 service to upload
                    $uploadResult = $this->s3Service->uploadFile(
                        $file,
                        'replacement_requests/prescriptions', // Example S3 path
                        $patient->id . '_' . time() // Example unique identifier
                    );
                    if (!$uploadResult['success']) {
                        throw new \Exception('Failed to upload prescription: ' . $uploadResult['error']);
                    }
                    $replacementData['prescription_path'] = $uploadResult['db_path'];
                    Log::info("Prescription uploaded for warranty claim", ['path' => $uploadResult['s3_path']]);
                }
            }



            // Set status based on warranty
            if ($isWarranty) {
                $replacementData['status'] = DeviceReplacement::STATUS_PENDING;
            } else {
                $replacementData['status'] = DeviceReplacement::STATUS_APPROVED;
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

            $responseData = $replacement->toArray();
            if (!empty($responseData['interrogation_report_path'])) {
                $responseData['interrogation_report_url'] = $this->s3Service->getFileUrl($responseData['interrogation_report_path']);
            }
            if (!empty($responseData['prescription_path'])) {
                $responseData['prescription_url'] = $this->s3Service->getFileUrl($responseData['prescription_path']);
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


    public function submitReplacement1(Request $request)
    {
        \Log::info('submitReplacement method started', ['request' => $request->all()]);

        $validated = $request->validate([
            'new_ipg_serial_number' => 'required|string|exists:implants,ipg_serial_number',
            'state' => 'required|string',
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_model' => 'required|string',
        ]);

        \Log::info('Request validated successfully', ['validated_data' => $validated]);

        try {
            \DB::beginTransaction();
            \Log::info('Transaction started');

            // Find the existing implant record
            $implant = Implant::where('ipg_serial_number', $validated['new_ipg_serial_number'])->first();

            if (!$implant) {
                \Log::warning('Implant not found', ['ipg_serial_number' => $validated['new_ipg_serial_number']]);
                return response()->json([
                    'message' => 'Implant not found',
                ], 404);
            }

            \Log::info('Implant found', ['implant_id' => $implant->id]);

            // Update the implant record with new information
            $implant->update([
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'therapy_name' => $validated['therapy_name'],
                'device_name' => $validated['device_name'],
                'implantation_date' => $validated['implantation_date'],
                'ipg_model' => $validated['ipg_model'],
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3),
                'user_id' => $request->user()->id
            ]);

            \Log::info('Implant updated successfully', ['implant_id' => $implant->id]);

            // Find and update the related device replacement record
            $replacementRequest = DeviceReplacement::where('new_ipg_serial_number', $validated['new_ipg_serial_number'])
                ->where('status', 'registered')
                ->where('service_completed', false)
                ->latest()
                ->first();

            if ($replacementRequest) {
                \Log::info('Replacement request found', ['replacement_id' => $replacementRequest->id]);

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

                \Log::info('Replacement request updated successfully', ['replacement_id' => $replacementRequest->id]);

                // Log after successful update
                \Log::info('Device replacement service completed successfully', [
                    'replacement_id' => $replacementRequest->id,
                    'engineer_id' => $request->user()->id
                ]);
            } else {
                \Log::info('No replacement request found for this implant', ['ipg_serial_number' => $validated['new_ipg_serial_number']]);
            }

            \DB::commit();
            \Log::info('Transaction committed');

            return response()->json([
                'message' => 'Implant information updated successfully',
                'data' => $implant,
                'replacement_updated' => $replacementRequest ? true : false
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Transaction rolled back due to error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'message' => 'Error updating implant information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitReplacement(Request $request)
    {
        \Log::info('submitReplacement method started', ['request' => $request->all()]);

        // Updated validation: Expect implant_id, new serial, and new model number
        $validated = $request->validate([
            'implant_id' => 'required|integer|exists:implants,id', // ID of the placeholder implant
            'new_ipg_serial_number' => [
                'required',
                'string',
                Rule::unique('implants', 'ipg_serial_number')->ignore($request->input('implant_id')), // Ensure unique, ignoring the current record
            ],
            'ipg_model_number' => 'required|string', // Added model number validation
            'state' => 'required|string',
            'hospital_name' => 'required|string',
            'doctor_name' => 'required|string',
            'therapy_name' => 'required|string',
            'device_name' => 'required|string',
            'implantation_date' => 'required|date',
            'ipg_model' => 'required|string',
        ]);

        \Log::info('Request validated successfully', ['validated_data' => $validated]);

        try {
            \DB::beginTransaction();
            \Log::info('Transaction started');

            // Find the implant record using the provided implant_id
            $implant = Implant::find($validated['implant_id']);

            if (!$implant) {
                \Log::warning('Implant not found with ID', ['implant_id' => $validated['implant_id']]);
                return response()->json([
                    'message' => 'Implant record not found.',
                ], 404);
            }

            // Check if the implant is already fully registered (e.g., not a placeholder)
            // You might need a more specific check depending on your placeholder logic (e.g., checking if ipg_serial_number is temporary)
            if ($implant->ipg_serial_number !== 'TEMP_' . $implant->id && $implant->ipg_serial_number !== $validated['new_ipg_serial_number']) {
                // Add more robust check if needed, e.g., checking for a specific placeholder pattern
                // \Log::warning('Attempted to update an already registered implant', ['implant_id' => $implant->id, 'current_serial' => $implant->ipg_serial_number]);
                // return response()->json(['message' => 'This implant record seems to be already finalized.'], 400);
            }


            \Log::info('Implant found', ['implant_id' => $implant->id, 'patient_id' => $implant->patient_id]);

            // Update the implant record with new information including the actual serial and model number
            $implant->update([
                'ipg_serial_number' => $validated['new_ipg_serial_number'], // Update serial number
                'ipg_model_number' => $validated['ipg_model_number'], // Update model number
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'doctor_name' => $validated['doctor_name'],
                'therapy_name' => $validated['therapy_name'],
                'device_name' => $validated['device_name'],
                'implantation_date' => $validated['implantation_date'],
                'ipg_model' => $validated['ipg_model'],
                // 'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addMonths(3), // Consider if warranty should be 1 year
                'warranty_expired_at' => Carbon::parse($validated['implantation_date'])->addYear(),
                'user_id' => $request->user()->id, // Service Engineer ID
                'active' => true // Ensure the new implant is active
            ]);

            \Log::info('Implant updated successfully with new details', ['implant_id' => $implant->id, 'new_serial' => $implant->ipg_serial_number]);


            // Update the IPG serial in ipg_serials table
            $this->updateIpgImplantationStatus(
                $validated['new_ipg_serial_number'],
                $validated['ipg_model_number'],
                $implant->patient_id
            );

            \Log::info('IPG serial updated in ipg_serials table', [
                'ipg_serial_number' => $validated['new_ipg_serial_number'],
                'ipg_model_number' => $validated['ipg_model_number'],
                'patient_id' => $implant->patient_id
            ]);
    

            // Find and update the related device replacement record
            // Find using patient_id and status 'registered' as the new implant ID might not be directly on the replacement record yet.
            $replacementRequest = DeviceReplacement::where('patient_id', $implant->patient_id)
                ->where('status', 'registered') // Status set by admin during assignment
                ->where('service_completed', false)
                ->latest() // Get the most recent one for the patient
                ->first();

            if ($replacementRequest) {
                \Log::info('Related DeviceReplacement request found', ['replacement_id' => $replacementRequest->id]);

                // Log before updating
                \Log::info('Device replacement service completion initiated', [
                    'replacement_id' => $replacementRequest->id,
                    'patient_id' => $replacementRequest->patient_id,
                    'new_ipg_serial_to_update' => $validated['new_ipg_serial_number'], // Log the serial being updated
                    'engineer_id' => $request->user()->id
                ]);

                // Update the replacement request with the new serial number and mark as completed
                $replacementRequest->update([
                    'new_ipg_serial_number' => $validated['new_ipg_serial_number'], // Update the serial number here too
                    // 'new_ipg_model_number' => $validated['ipg_model_number'], // Add if this field exists in DeviceReplacement model/table
                    'service_completed' => true
                ]);

                \Log::info('DeviceReplacement request updated successfully', ['replacement_id' => $replacementRequest->id]);

            } else {
                \Log::warning('No matching DeviceReplacement request found to update', [
                    'patient_id' => $implant->patient_id,
                    'status_searched' => 'registered'
                ]);
            }

            \DB::commit();
            \Log::info('Transaction committed');
            $implant->refresh();

            return response()->json([
                'status' => 'success', // Added status field
                'message' => 'Implant information updated and replacement marked complete successfully',
                'data' => $implant,
                'replacement_updated' => $replacementRequest ? true : false
            ], 200);

        } catch (ValidationException $e) {
            // Don't rollback for validation errors, just rethrow
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Transaction rolled back due to error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error', // Added status field for error
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
                    'lead_brand',
                    'ra_rv_leads'


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
                        'lead_brand' => $implant->lead_brand,
                        'ra_rv_leads' => $implant->ra_rv_leads,

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

            // Find implants with matching secret key
            $implants = Implant::where('secret_key', $validated['secret_key'])->get();

            // Find the specific implant with matching IPG serial number
            $primaryImplant = $implants->firstWhere('ipg_serial_number', $validated['ipg_serial_number']);

            if (!$primaryImplant) {
                return response()->json([
                    'message' => 'Invalid IPG serial number or secret key'
                ], 404);
            }

            // Check if any of these implants are already linked to a different patient
            $alreadyLinked = $implants->filter(function ($implant) use ($patientId) {
                return $implant->patient_id !== null && $implant->patient_id != $patientId;
            })->count() > 0;

            if ($alreadyLinked) {
                return response()->json([
                    'message' => 'One or more implants with this secret key are already linked to another patient'
                ], 400);
            }

            // Begin transaction
            \DB::beginTransaction();
            try {
                // Deactivate ALL existing implants for this patient
                Implant::where('patient_id', $patientId)
                    ->update(['active' => false]);

                // Find the most recent implant to copy fields from
                $oldImplant = Implant::where('patient_id', $patientId)
                    ->latest()
                    ->first();

                // Fields to copy from old implant if it exists
                $fieldsToCopy = [
                    'channel_partner',
                    'has_ra_rv_lead',
                    'has_extra_lead',
                    'patient_id_card',
                    'warranty_card',
                    'interrogation_report'
                ];

                // Link all implants with the same secret key to this patient
                foreach ($implants as $implant) {
                    // Copy fields from old implant if it exists
                    if ($oldImplant) {
                        foreach ($fieldsToCopy as $field) {
                            if (isset($oldImplant->$field) && $oldImplant->$field) {
                                $implant->$field = $oldImplant->$field;
                            }
                        }
                    }

                    // Set the implant's patient_id
                    $implant->patient_id = $patientId;

                    // Only the implant with the specified serial number should be active
                    $implant->active = ($implant->ipg_serial_number == $validated['ipg_serial_number']);

                    $implant->save();
                }

                \DB::commit();

                return response()->json([
                    'message' => 'Implant(s) linked to patient successfully',
                    'data' => [
                        'primary_ipg_serial_number' => $primaryImplant->ipg_serial_number,
                        'total_implants_linked' => $implants->count(),
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


    public function getImplantDetailsBySerial($ipgSerialNumber)
    {
        try {
            $implant = Implant::with([
                'patient' => function ($query) {
                    $query->select('id', 'name', 'email', 'phone_number', 'date_of_birth', 'gender');
                }
            ])
                ->where('ipg_serial_number', $ipgSerialNumber)
                ->first();

            if (!$implant) {
                return response()->json([
                    'message' => 'No implant found with the provided IPG serial number'
                ], 404);
            }

            return response()->json([
                'message' => 'Implant details retrieved successfully',
                'data' => [
                    'implant' => [
                        'id' => $implant->id,
                        'ipg_serial_number' => $implant->ipg_serial_number,
                        'ipg_model' => $implant->ipg_model,
                        'ipg_model_number' => $implant->ipg_model_number,
                        'implantation_date' => $implant->implantation_date,
                        'hospital_name' => $implant->hospital_name,
                        'hospital_state' => $implant->hospital_state,
                        'doctor_name' => $implant->doctor_name,
                        'channel_partner' => $implant->channel_partner,
                        'therapy_name' => $implant->therapy_name,
                        'device_name' => $implant->device_name,
                        'warranty_status' => [
                            'expired_at' => $implant->warranty_expired_at,
                            'is_active' => now()->lt($implant->warranty_expired_at)
                        ],
                        'active' => $implant->active,
                        'lead_details' => [
                            'ra_rv_leads' => $implant->ra_rv_leads,
                            'has_ra_rv_lead' => $implant->has_ra_rv_lead,
                            'has_extra_lead' => $implant->has_extra_lead,
                            'csp_lead_model' => $implant->csp_lead_model,
                            'csp_lead_serial' => $implant->csp_lead_serial,
                            'csp_catheter_model' => $implant->csp_catheter_model
                        ],
                    ],
                    'patient' => $implant->patient ? [
                        'id' => $implant->patient->id,
                        'name' => $implant->patient->name,
                        'email' => $implant->patient->email,
                        'phone_number' => $implant->patient->phone_number,
                        'date_of_birth' => $implant->patient->date_of_birth,
                        'gender' => $implant->patient->gender
                    ] : null
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get the current active implant details for the authenticated patient
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentImplant1(Request $request)
    {
        try {
            $user = $request->user();

            $implant = Implant::where('patient_id', $user->id)
                ->where('active', true)
                ->latest('created_at')
                ->first();

            if (!$implant) {
                return response()->json([
                    'message' => 'No active implant found',
                    'has_implant' => false
                ], 404);
            }

            // Check for warranty extension payment
            $warrantyExtension = \App\Models\Payment::where('patient_id', $user->id)
                ->where('payment_type', 'warranty_extension')
                ->where('payment_status', 'completed')
                ->first();

            // Determine warranty type
            $warrantyType = 'standard';
            if ($warrantyExtension) {
                $warrantyType = 'extended';
            }

            $implantData = [
                'id' => $implant->id,
                'ipg_serial_number' => $implant->ipg_serial_number,
                'ipg_model' => $implant->ipg_model,
                'ipg_model_number' => $implant->ipg_model_number,
                'implantation_date' => $implant->implantation_date,
                'hospital_name' => $implant->hospital_name,
                'hospital_state' => $implant->hospital_state,
                'doctor_name' => $implant->doctor_name,
                'therapy_name' => $implant->therapy_name,
                'device_name' => $implant->device_name,
                'warranty_status' => [
                    'expired_at' => $implant->warranty_expired_at,
                    'is_active' => now()->lt($implant->warranty_expired_at),
                    'type' => $warrantyType
                ],
                'lead_details' => [
                    'ra_rv_leads' => $implant->ra_rv_leads,
                    'has_ra_rv_lead' => $implant->has_ra_rv_lead,
                    'has_extra_lead' => $implant->has_extra_lead,
                    'csp_lead_model' => $implant->csp_lead_model,
                    'csp_catheter_model' => $implant->csp_catheter_model,
                    'csp_lead_serial' => $implant->csp_lead_serial,
                ]
            ];

            return response()->json([
                'message' => 'Current implant details retrieved successfully',
                'has_implant' => true,
                'data' => $implantData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving current implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Get the current active implant along with all previous implants for the authenticated patient
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentImplant(Request $request)
    {
        try {
            $user = $request->user();

            // Get all implants for this patient, sorted by created_at descending
            $allImplants = Implant::where('patient_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Find the active implant (should be the first if sorted properly, but let's be explicit)
            $activeImplant = $allImplants->where('active', true)->first();

            if (!$activeImplant) {
                return response()->json([
                    'message' => 'No active implant found',
                    'has_implant' => false
                ], 404);
            }

            // Check for warranty extension payment
            $warrantyExtension = \App\Models\Payment::where('patient_id', $user->id)
                ->where('payment_type', 'warranty_extension')
                ->where('payment_status', 'completed')
                ->first();

            // Determine warranty type
            $warrantyType = 'standard';
            if ($warrantyExtension) {
                $warrantyType = 'extended';
            }

            // $activeImplantData = [
            //     'id' => $activeImplant->id,
            //     'ipg_serial_number' => $activeImplant->ipg_serial_number,
            //     'ipg_model' => $activeImplant->ipg_model,
            //     'ipg_model_number' => $activeImplant->ipg_model_number,
            //     'implantation_date' => $activeImplant->implantation_date,
            //     'hospital_name' => $activeImplant->hospital_name,
            //     'hospital_state' => $activeImplant->hospital_state,
            //     'doctor_name' => $activeImplant->doctor_name,
            //     'therapy_name' => $activeImplant->therapy_name,
            //     'device_name' => $activeImplant->device_name,
            //     'warranty_status' => [
            //         'expired_at' => $activeImplant->warranty_expired_at,
            //         'is_active' => now()->lt($activeImplant->warranty_expired_at),
            //         'type' => $warrantyType
            //     ],
            //     'lead_details' => [
            //         'ra_rv_leads' => $activeImplant->ra_rv_leads,
            //         'has_ra_rv_lead' => $activeImplant->has_ra_rv_lead,
            //         'has_extra_lead' => $activeImplant->has_extra_lead,
            //         'csp_lead_model' => $activeImplant->csp_lead_model,
            //         'csp_catheter_model' => $activeImplant->csp_catheter_model,
            //         'csp_lead_serial' => $activeImplant->csp_lead_serial,
            //     ]
            // ];



            $activeImplantData = [
                'id' => $activeImplant->id,
                'ipg_serial_number' => $activeImplant->ipg_serial_number,
                'ipg_model' => $activeImplant->ipg_model,
                'ipg_model_number' => $activeImplant->ipg_model_number,
                'implantation_date' => $activeImplant->implantation_date,
                'hospital_name' => $activeImplant->hospital_name,
                'hospital_state' => $activeImplant->hospital_state,
                'doctor_name' => $activeImplant->doctor_name,
                'therapy_name' => $activeImplant->therapy_name,
                'device_name' => $activeImplant->device_name,
                'warranty_status' => [
                    'expired_at' => $activeImplant->warranty_expired_at,
                    'is_active' => $activeImplant->warranty_expired_at ? now()->lt($activeImplant->warranty_expired_at) : false,
                    'type' => $warrantyType
                ],
                'lead_details' => [
                    'ra_rv_leads' => $activeImplant->ra_rv_leads,
                    'has_ra_rv_lead' => $activeImplant->has_ra_rv_lead,
                    'has_extra_lead' => $activeImplant->has_extra_lead,
                    'csp_lead_model' => $activeImplant->csp_lead_model,
                    'csp_catheter_model' => $activeImplant->csp_catheter_model,
                    'csp_lead_serial' => $activeImplant->csp_lead_serial,
                ]
            ];

            // Format previous implants (excluding the active one)
            $previousImplants = $allImplants->where('active', false)->map(function ($implant) {
                return [
                    'id' => $implant->id,
                    'ipg_serial_number' => $implant->ipg_serial_number,
                    'ipg_model' => $implant->ipg_model,
                    'ipg_model_number' => $implant->ipg_model_number,
                    'implantation_date' => $implant->implantation_date,
                    'hospital_name' => $implant->hospital_name,
                    'hospital_state' => $implant->hospital_state,
                    'doctor_name' => $implant->doctor_name,
                    'therapy_name' => $implant->therapy_name,
                    'device_name' => $implant->device_name,
                    'warranty_status' => [
                        'expired_at' => $implant->warranty_expired_at,
                        // 'is_active' => now()->lt($implant->warranty_expired_at),
                        'is_active' => $implant->warranty_expired_at ? now()->lt($implant->warranty_expired_at) : false,
                    ],
                    'lead_details' => [
                        'ra_rv_leads' => $implant->ra_rv_leads,
                        'has_ra_rv_lead' => $implant->has_ra_rv_lead,
                        'has_extra_lead' => $implant->has_extra_lead,
                        'csp_lead_model' => $implant->csp_lead_model,
                        'csp_catheter_model' => $implant->csp_catheter_model,
                        'csp_lead_serial' => $implant->csp_lead_serial,
                    ]
                ];
            })->values();

            return response()->json([
                'message' => 'Implant details retrieved successfully',
                'has_implant' => true,
                'data' => [
                    'active_implant' => $activeImplantData,
                    'previous_implants' => $previousImplants,
                    'implant_count' => $allImplants->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving implant details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

