<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Patient;
use App\Models\DeviceReplacement;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    public function addEmployee(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign roles
            $user->assignRole($request->roles);
            if (in_array('sales-representative', $request->roles)) {
                // Create corresponding patient record
                $patient = Patient::create([
                    'Auth_name' => $user->name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password,
                    'is_service_engineer' => true,
                    'user_id' => $user->id
                ]);
            }

            Log::info('New employee created', [
                'admin_id' => auth()->id(),
                'employee_id' => $user->id,
                'roles' => $request->roles
            ]);

            return response()->json([
                'message' => 'Employee created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating employee', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Error creating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function listEmployees()
    {
        try {
            // Get all users with their roles
            $employees = User::with('roles')
                ->whereHas('roles', function ($q) {
                    // Exclude users with only 'user' role
                    $q->where('name', '!=', 'user');
                })
                ->get()
                ->map(function ($user) {
                    // Extract first and last name from the full name
                    $nameParts = explode(' ', $user->name, 2);
                    $firstName = $nameParts[0];
                    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),

                        // 'created_at' => $user->created_at,
                        // 'password' => $user->password,
                        // 'is_service_engineer' => $user->roles->contains('name', 'sales-representative')
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Employees retrieved successfully',
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving employees', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateEmployee1(Request $request, $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the user
            $user = User::findOrFail($id);

            // Update user details only if provided
            if ($request->has('first_name') || $request->has('last_name')) {
                // Get current first and last name if not provided
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $request->input('first_name', $nameParts[0]);
                $lastName = $request->input('last_name', $nameParts[1] ?? '');
                $user->name = $firstName . ' ' . $lastName;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            // Only update password if provided
            if ($request->has('password') && !empty($request->password)) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Only sync roles if provided in the request
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);

                // Update or create patient record if user is a service engineer
                if (in_array('sales-representative', $request->roles)) {
                    $patient = Patient::updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'Auth_name' => $user->name,
                            'name' => $user->name,
                            'email' => $user->email,
                            'password' => $user->password,
                            'is_service_engineer' => true
                        ]
                    );
                }
            }

            // Log what fields were actually changed
            $changedFields = array_filter($request->only(['first_name', 'last_name', 'email', 'password', 'roles']), function ($value) {
                return !is_null($value) && (!is_string($value) || strlen($value) > 0);
            });

            Log::info('Employee updated', [
                'admin_id' => auth()->id(),
                'employee_id' => $user->id,
                'updated_fields' => array_keys($changedFields),
                'roles' => $request->has('roles') ? $request->roles : $user->getRoleNames()
            ]);

            return response()->json([
                'message' => 'Employee updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating employee', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'employee_id' => $id
            ]);

            return response()->json([
                'message' => 'Error updating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateEmployee(Request $request, $id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the user
            $user = User::findOrFail($id);

            // Track password update
            $passwordUpdated = false;

            // Update user details only if provided
            if ($request->has('first_name') || $request->has('last_name')) {
                // Get current first and last name if not provided
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $request->input('first_name', $nameParts[0]);
                $lastName = $request->input('last_name', $nameParts[1] ?? '');
                $user->name = $firstName . ' ' . $lastName;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            // Only update password if provided
            if ($request->has('password') && !empty($request->password)) {
                $user->password = Hash::make($request->password);
                $passwordUpdated = true;
            }

            $user->save();

            // Only sync roles if provided in the request
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Update patient record if user is (or becoming) a service engineer
            $hasServiceEngineerRole = $request->has('roles') ?
                in_array('sales-representative', $request->roles) :
                $user->hasRole('sales-representative');

            if ($hasServiceEngineerRole) {
                $patientData = [
                    'Auth_name' => $user->name,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_service_engineer' => true
                ];

                // Only update password in patient record if it was changed
                if ($passwordUpdated && $request->has('password')) {
                    $patientData['password'] = Hash::make($request->password);
                }

                $patient = Patient::updateOrCreate(
                    ['user_id' => $user->id],
                    $patientData
                );
            }

            // Log what fields were actually changed
            $changedFields = array_filter($request->only(['first_name', 'last_name', 'email', 'password', 'roles']), function ($value) {
                return !is_null($value) && (!is_string($value) || strlen($value) > 0);
            });

            Log::info('Employee updated', [
                'admin_id' => auth()->id(),
                'employee_id' => $user->id,
                'updated_fields' => array_keys($changedFields),
                'roles' => $request->has('roles') ? $request->roles : $user->getRoleNames(),
                'patient_record_updated' => $hasServiceEngineerRole,
                'password_updated' => $passwordUpdated
            ]);

            return response()->json([
                'message' => 'Employee updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error updating employee', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'employee_id' => $id
            ]);

            return response()->json([
                'message' => 'Error updating employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteEmployee(Request $request, $id)
    {
        try {
            // Find the user
            $user = User::findOrFail($id);

            // Get user details for logging
            $userName = $user->name;
            $userEmail = $user->email;
            $userRoles = $user->getRoleNames();

            // Begin transaction to ensure all related data is handled properly
            \DB::beginTransaction();

            // Check and handle implants related to this user
            $implants = \DB::table('implants')->where('user_id', $id)->get();

            if ($implants->count() > 0) {
                // Option 1: Set implants' user_id to null (if the column allows nulls)
                \DB::table('implants')->where('user_id', $id)->update(['user_id' => null]);

                // Option 2 (alternative): Re-assign implants to a default admin user
                // \DB::table('implants')->where('user_id', $id)->update(['user_id' => 1]); // 1 is usually the admin ID
            }

            // Delete related patient record if user is a service engineer
            if ($user->hasRole('sales-representative')) {
                Patient::where('user_id', $id)->delete();
            }

            // Check for other possible relationships
            // For example, if there are follow-up requests linked to this user
            if (\Schema::hasTable('follow_up_requests') && \Schema::hasColumn('follow_up_requests', 'service_engineer_id')) {
                \DB::table('follow_up_requests')
                    ->where('service_engineer_id', $id)
                    ->update(['service_engineer_id' => null]);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Remove all roles
            $user->roles()->detach();

            // Delete the user
            $user->delete();

            // Commit transaction
            \DB::commit();

            // Log the deletion
            Log::info('Employee deleted', [
                'admin_id' => auth()->id(),
                'deleted_employee_id' => $id,
                'deleted_employee_name' => $userName,
                'deleted_employee_email' => $userEmail,
                'deleted_employee_roles' => $userRoles,
                'implants_reassigned' => $implants->count()
            ]);

            return response()->json([
                'message' => 'Employee deleted successfully',
                'user' => [
                    'id' => $id,
                    'name' => $userName,
                    'email' => $userEmail,
                    'roles' => $userRoles
                ]
            ], 200);

        } catch (\Exception $e) {
            // Rollback transaction if any error occurs
            \DB::rollBack();

            Log::error('Error deleting employee', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'employee_id' => $id
            ]);

            return response()->json([
                'message' => 'Error deleting employee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listDistributors()
    {
        try {
            $distributors = User::role('distributor')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,

                    ];
                });

            return response()->json([
                'message' => 'Distributors retrieved successfully',
                'data' => $distributors
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving distributors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDashboardCounts()
    {
        try {
            // Get pending replacement requests count
            $pendingReplacementsCount = DeviceReplacement::where('status', DeviceReplacement::STATUS_PENDING)->count();

            // Get pending implants count
            $pendingImplantsCount = \App\Models\PendingImplant::where('status', 'pending')->count();

            // Get pending follow-up requests
            $pendingFollowUpsCount = \App\Models\FollowUpRequest::where('status', \App\Models\FollowUpRequest::STATUS_PENDING)->count();

            // Total actionables
            $totalActionables = $pendingReplacementsCount + $pendingImplantsCount + $pendingFollowUpsCount;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_actionables' => $totalActionables,
                    'pending_replacements' => $pendingReplacementsCount,
                    'pending_implants' => $pendingImplantsCount,
                    'pending_follow_ups' => $pendingFollowUpsCount
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard counts', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching dashboard counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getAdminDashboardCounts()
    {
        try {
            // Get pending replacement requests count
            $pendingReplacementsCount = DeviceReplacement::where('status', DeviceReplacement::STATUS_PENDING)->count();

            // Get pending implants count
            $pendingImplantsCount = \App\Models\PendingImplant::where('status', 'pending')->count();


            // Total actionables
            $totalActionables = $pendingReplacementsCount + $pendingImplantsCount;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_actionables' => $totalActionables,
                    'pending_replacements' => $pendingReplacementsCount,
                    'pending_implants' => $pendingImplantsCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard counts', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching dashboard counts',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate a comprehensive report of all implants in the system
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImplantReport(Request $request)
    {
        try {
            // Get all implants with related data
            $implants = \App\Models\Implant::with([
                'patient',
                'user', // Sales/Technical person
                'user.roles'
            ])->get();

            $reportData = [];

            foreach ($implants as $implant) {
                // Get the current date for year/quarter/month calculations
                $implantationDate = $implant->implantation_date ?
                    \Carbon\Carbon::parse($implant->implantation_date) :
                    \Carbon\Carbon::now();

                $year = $implantationDate->format('Y');
                $quarter = 'Q' . ceil($implantationDate->format('n') / 3);
                $month = $implantationDate->format('F');

                // Get warranty type
                $warrantyExtension = $implant->patient_id ?
                    \App\Models\Payment::where('patient_id', $implant->patient_id)
                        ->where('payment_type', 'warranty_extension')
                        ->where('payment_status', 'completed')
                        ->first() :
                    null;

                $warrantyType = $warrantyExtension ? 'Extended' : 'Standard';

                // Get lead information from ra_rv_leads JSON field
                $leads = [];
                if ($implant->ra_rv_leads && (is_array($implant->ra_rv_leads) || is_string($implant->ra_rv_leads))) {
                    // Handle if the data is stored as a JSON string
                    $leadData = is_string($implant->ra_rv_leads) ?
                        json_decode($implant->ra_rv_leads, true) :
                        $implant->ra_rv_leads;

                    if (is_array($leadData)) {
                        $leadCount = 1;
                        foreach ($leadData as $lead) {
                            if ($leadCount <= 3) { // We only handle up to 3 leads as per the report format
                                // Extract available data
                                $modelName = $lead['model'] ?? 'Unknown Model';
                                $serial = $lead['serial'] ?? 'Unknown Serial';

                                // If model_number doesn't exist, try to look it up
                                $modelNumber = $lead['model_number'] ?? null;

                                // If model_number isn't provided but we need it, look it up
                                if (!$modelNumber && $modelName) {
                                    // Primary lookup in LeadModel
                                    $modelInfo = \App\Models\LeadModel::where('model_name', $modelName)->first();
                                    if ($modelInfo) {
                                        $modelNumber = $modelInfo->model_number;
                                    } else {
                                        // Fallback to a default format or logging
                                        \Log::warning("No model number found for lead model: {$modelName}");
                                        $modelNumber = "UNKNOWN-" . substr(md5($modelName), 0, 8); // Generate a pseudo model number
                                    }
                                }

                                $leads[$leadCount] = [
                                    'model' => $modelName,
                                    'model_number' => $modelNumber ?: 'No Model Number',
                                    'serial' => $serial
                                ];
                                $leadCount++;
                            }
                        }
                    }
                }

                // Get patient information
                $patient = $implant->patient;

                // Format report row with default values for nulls
                $reportRow = [
                    'Year' => $year ?: 'N/A',
                    'Quarter' => $quarter ?: 'N/A',
                    'Month' => $month ?: 'N/A',
                    'Implant_ID' => $implant->id ?: 'N/A',
                    'Implant_Type' => $implant->device_name ?: 'Not Specified',
                    'Status_of_Implant' => $implant->active ? 'Active' : 'Inactive',
                    'Date_of_Implant' => $implant->implantation_date ?: 'Not Recorded',
                    'Zone' => 'Not Specified', // Assuming zone is not currently stored
                    'State' => $implant->hospital_state ?: 'Not Specified',
                    'City' => $patient && $patient->city ? $patient->city : 'Not Specified',
                    'Zonal_Regional_Manager' => 'Not Assigned', // Would need to be added if available
                    'Distributor_Code' => 'Not Assigned', // Would need to be added if available
                    'Distributor_Name' => $implant->channel_partner ?: 'Not Specified',
                    'Sales_Person_Id' => $implant->user_id ?: 'Not Assigned',
                    'Sales_Person_Name' => $implant->user ? $implant->user->name : 'Not Assigned',
                    'Technical_Person_Id' => $implant->user_id ?: 'Not Assigned', // Assuming same as sales for now
                    'Technical_Person_Name' => $implant->user ? $implant->user->name : 'Not Assigned',
                    'Hospital' => $implant->hospital_name ?: 'Not Specified',
                    'Physician_ID' => 'Not Recorded', // Would need to be added if available
                    'Physician_Name' => $implant->doctor_name ?: 'Not Specified',
                    'Warranty_Type' => $warrantyType,
                    'Therapy' => $implant->therapy_name ?: 'Not Specified',
                    'Device_Type' => $implant->device_name ?: 'Not Specified',
                    'IPG_Model_Number' => $implant->ipg_model_number ?: 'Not Specified',
                    'IPG_Model_Name' => $implant->ipg_model ?: 'Not Specified',
                    'IPG_Serial_Number' => $implant->ipg_serial_number ?: 'Not Specified',

                    // Lead 1
                    'Lead_1_Model_Name' => isset($leads[1]) ? $leads[1]['model'] : 'Not Applicable',
                    'Lead_1_Model_Number' => isset($leads[1]) ? $leads[1]['model_number'] : 'Not Applicable',
                    'Lead_1_Serial_Number' => isset($leads[1]) ? $leads[1]['serial'] : 'Not Applicable',

                    // Lead 2
                    'Lead_2_Model_Name' => isset($leads[2]) ? $leads[2]['model'] : 'Not Applicable',
                    'Lead_2_Model_Number' => isset($leads[2]) ? $leads[2]['model_number'] : 'Not Applicable',
                    'Lead_2_Serial_Number' => isset($leads[2]) ? $leads[2]['serial'] : 'Not Applicable',

                    // Lead 3
                    'Lead_3_Model_Name' => isset($leads[3]) ? $leads[3]['model'] : 'Not Applicable',
                    'Lead_3_Model_Number' => isset($leads[3]) ? $leads[3]['model_number'] : 'Not Applicable',
                    'Lead_3_Serial_Number' => isset($leads[3]) ? $leads[3]['serial'] : 'Not Applicable',

                    // CSP and additional information
                    'CSP' => $implant->is_csp_implant ? 'Yes' : 'No',
                    'CSP_Cathetre' => $implant->csp_catheter_model ?: 'Not Applicable',
                    'Extra_Lead' => $implant->has_extra_lead ? 'Yes' : 'No',
                    'CSP_Lead_Model_Name' => $implant->csp_lead_model ?: 'Not Applicable',
                    'CSP_Lead_Model_Number' => 'Not Recorded', // If available in your system
                    'CSP_Lead_Serial_Number' => $implant->csp_lead_serial ?: 'Not Applicable',
                    'Number_of_Unit' => 1, // Assuming 1 unit per record

                    // Patient information
                    'Patient_ID' => $patient ? $patient->id : 'Not Assigned',
                    'Patient_Name' => $patient ? $patient->name : 'Not Assigned',
                    'Patient_Email_ID' => $patient ? ($patient->email ?: 'No Email') : 'Not Assigned',
                    'Patient_Phone' => $patient ? ($patient->phone_number ?: 'No Phone') : 'Not Assigned'
                ];

                $reportData[] = $reportRow;
            }

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'count' => count($reportData)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating implant report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate implant report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Export implant report as CSV
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportImplantReportCsv(Request $request)
    {
        try {
            $implants = \App\Models\Implant::with([
                'patient',
                'user',
                'user.roles'
            ])->get();

            $filename = 'implant_report_' . date('Y-m-d') . '.csv';

            return response()->streamDownload(function () use ($implants) {
                // Open output stream
                $handle = fopen('php://output', 'w');

                // Add UTF-8 BOM to ensure Excel opens the file with proper encoding
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Headers
                $headers = [
                    'Year',
                    'Quarter',
                    'Month',
                    'Implant ID',
                    'Implant Type',
                    'Status of Implant',
                    'Date of Implant',
                    'Zone',
                    'State',
                    'City',
                    'Zonal/Regional Manager',
                    'Distributor Code',
                    'Distributor Name',
                    'Sales Person Id',
                    'Sales Person Name',
                    'Technical Person Id',
                    'Technical Person Name',
                    'Hospital',
                    'Physician ID',
                    'Physician Name',
                    'Warranty Type',
                    'Therapy',
                    'Device Type',
                    'IPG Model Number',
                    'IPG Model Name',
                    'IPG Serial Number',
                    'Lead 1 Model Name',
                    'Lead 1 Model Number',
                    'Lead 1 Serial Number',
                    'Lead 2 Model Name',
                    'Lead 2 Model Number',
                    'Lead 2 Serial Number',
                    'Lead 3 Model Name',
                    'Lead 3 Model Number',
                    'Lead 3 Serial Number',
                    'CSP',
                    'CSP Cathetre',
                    'Extra Lead',
                    'CSP Lead Model Name',
                    'CSP Lead Model Number',
                    'CSP Lead Serial Number',
                    'Number of Unit',
                    'Patient ID',
                    'Patient Name',
                    'Patient Email ID',
                    'Patient Phone'
                ];

                fputcsv($handle, $headers);

                // Process and write data rows
                foreach ($implants as $implant) {
                    // Same logic as in getImplantReport to process the data
                    $implantationDate = $implant->implantation_date ?
                        \Carbon\Carbon::parse($implant->implantation_date) :
                        \Carbon\Carbon::now();

                    $year = $implantationDate->format('Y');
                    $quarter = 'Q' . ceil($implantationDate->format('n') / 3);
                    $month = $implantationDate->format('F');

                    $warrantyExtension = $implant->patient_id ?
                        \App\Models\Payment::where('patient_id', $implant->patient_id)
                            ->where('payment_type', 'warranty_extension')
                            ->where('payment_status', 'completed')
                            ->first() :
                        null;

                    $warrantyType = $warrantyExtension ? 'Extended' : 'Standard';

                    // Process leads
                    $leads = [];
                    if ($implant->ra_rv_leads && (is_array($implant->ra_rv_leads) || is_string($implant->ra_rv_leads))) {
                        $leadData = is_string($implant->ra_rv_leads) ?
                            json_decode($implant->ra_rv_leads, true) :
                            $implant->ra_rv_leads;

                        if (is_array($leadData)) {
                            $leadCount = 1;
                            foreach ($leadData as $lead) {
                                if ($leadCount <= 3) {
                                    $modelName = $lead['model'] ?? 'Unknown Model';
                                    $serial = $lead['serial'] ?? 'Unknown Serial';
                                    $modelNumber = $lead['model_number'] ?? null;

                                    if (!$modelNumber && $modelName) {
                                        $modelInfo = \App\Models\LeadModel::where('model_name', $modelName)->first();
                                        if ($modelInfo) {
                                            $modelNumber = $modelInfo->model_number;
                                        } else {
                                            $modelNumber = "UNKNOWN-" . substr(md5($modelName), 0, 8);
                                        }
                                    }

                                    $leads[$leadCount] = [
                                        'model' => $modelName,
                                        'model_number' => $modelNumber ?: 'No Model Number',
                                        'serial' => $serial
                                    ];
                                    $leadCount++;
                                }
                            }
                        }
                    }

                    $patient = $implant->patient;

                    // Create row data
                    $row = [
                        $year ?: 'N/A',
                        $quarter ?: 'N/A',
                        $month ?: 'N/A',
                        $implant->id ?: 'N/A',
                        $implant->device_name ?: 'Not Specified',
                        $implant->active ? 'Active' : 'Inactive',
                        $implant->implantation_date ?: 'Not Recorded',
                        'Not Specified', // Zone
                        $implant->hospital_state ?: 'Not Specified',
                        $patient && $patient->city ? $patient->city : 'Not Specified',
                        'Not Assigned', // Zonal/Regional Manager
                        'Not Assigned', // Distributor Code
                        $implant->channel_partner ?: 'Not Specified',
                        $implant->user_id ?: 'Not Assigned',
                        $implant->user ? $implant->user->name : 'Not Assigned',
                        $implant->user_id ?: 'Not Assigned', // Technical Person ID
                        $implant->user ? $implant->user->name : 'Not Assigned',
                        $implant->hospital_name ?: 'Not Specified',
                        'Not Recorded', // Physician ID
                        $implant->doctor_name ?: 'Not Specified',
                        $warrantyType,
                        $implant->therapy_name ?: 'Not Specified',
                        $implant->device_name ?: 'Not Specified',
                        $implant->ipg_model_number ?: 'Not Specified',
                        $implant->ipg_model ?: 'Not Specified',
                        $implant->ipg_serial_number ?: 'Not Specified',
                        isset($leads[1]) ? $leads[1]['model'] : 'Not Applicable',
                        isset($leads[1]) ? $leads[1]['model_number'] : 'Not Applicable',
                        isset($leads[1]) ? $leads[1]['serial'] : 'Not Applicable',
                        isset($leads[2]) ? $leads[2]['model'] : 'Not Applicable',
                        isset($leads[2]) ? $leads[2]['model_number'] : 'Not Applicable',
                        isset($leads[2]) ? $leads[2]['serial'] : 'Not Applicable',
                        isset($leads[3]) ? $leads[3]['model'] : 'Not Applicable',
                        isset($leads[3]) ? $leads[3]['model_number'] : 'Not Applicable',
                        isset($leads[3]) ? $leads[3]['serial'] : 'Not Applicable',
                        $implant->is_csp_implant ? 'Yes' : 'No',
                        $implant->csp_catheter_model ?: 'Not Applicable',
                        $implant->has_extra_lead ? 'Yes' : 'No',
                        $implant->csp_lead_model ?: 'Not Applicable',
                        'Not Recorded', // CSP Lead Model Number
                        $implant->csp_lead_serial ?: 'Not Applicable',
                        1, // Number of Unit
                        $patient ? $patient->id : 'Not Assigned',
                        $patient ? $patient->name : 'Not Assigned',
                        $patient ? ($patient->email ?: 'No Email') : 'Not Assigned',
                        $patient ? ($patient->phone_number ?: 'No Phone') : 'Not Assigned'
                    ];

                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating CSV implant report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate CSV implant report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export implant report as PDF
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportImplantReportPdf(Request $request)
    {
        try {
            // Get all implants with related data
            $implants = \App\Models\Implant::with([
                'patient',
                'user',
                'user.roles'
            ])->get();

            $reportData = [];

            // Process each implant and build the report data the same way as in getImplantReport
            foreach ($implants as $implant) {
                // All the same data processing as in getImplantReport method...
                $implantationDate = $implant->implantation_date ?
                    \Carbon\Carbon::parse($implant->implantation_date) :
                    \Carbon\Carbon::now();

                $year = $implantationDate->format('Y');
                $quarter = 'Q' . ceil($implantationDate->format('n') / 3);
                $month = $implantationDate->format('F');

                $warrantyExtension = $implant->patient_id ?
                    \App\Models\Payment::where('patient_id', $implant->patient_id)
                        ->where('payment_type', 'warranty_extension')
                        ->where('payment_status', 'completed')
                        ->first() :
                    null;

                $warrantyType = $warrantyExtension ? 'Extended' : 'Standard';

                // Process leads...
                $leads = [];
                if ($implant->ra_rv_leads && (is_array($implant->ra_rv_leads) || is_string($implant->ra_rv_leads))) {
                    $leadData = is_string($implant->ra_rv_leads) ?
                        json_decode($implant->ra_rv_leads, true) :
                        $implant->ra_rv_leads;

                    if (is_array($leadData)) {
                        $leadCount = 1;
                        foreach ($leadData as $lead) {
                            if ($leadCount <= 3) {
                                $modelName = $lead['model'] ?? 'Unknown Model';
                                $serial = $lead['serial'] ?? 'Unknown Serial';
                                $modelNumber = $lead['model_number'] ?? null;

                                if (!$modelNumber && $modelName) {
                                    $modelInfo = \App\Models\LeadModel::where('model_name', $modelName)->first();
                                    if ($modelInfo) {
                                        $modelNumber = $modelInfo->model_number;
                                    } else {
                                        $modelNumber = "UNKNOWN-" . substr(md5($modelName), 0, 8);
                                    }
                                }

                                $leads[$leadCount] = [
                                    'model' => $modelName,
                                    'model_number' => $modelNumber ?: 'No Model Number',
                                    'serial' => $serial
                                ];
                                $leadCount++;
                            }
                        }
                    }
                }

                $patient = $implant->patient;

                // Build report row
                $reportRow = [
                    'Year' => $year ?: 'N/A',
                    'Quarter' => $quarter ?: 'N/A',
                    'Month' => $month ?: 'N/A',
                    'Implant_ID' => $implant->id ?: 'N/A',
                    'Implant_Type' => $implant->device_name ?: 'Not Specified',
                    'Status_of_Implant' => $implant->active ? 'Active' : 'Inactive',
                    'Date_of_Implant' => $implant->implantation_date ?: 'Not Recorded',
                    'Zone' => 'Not Specified',
                    'State' => $implant->hospital_state ?: 'Not Specified',
                    'City' => $patient && $patient->city ? $patient->city : 'Not Specified',
                    'Zonal_Regional_Manager' => 'Not Assigned',
                    'Distributor_Code' => 'Not Assigned',
                    'Distributor_Name' => $implant->channel_partner ?: 'Not Specified',
                    'Sales_Person_Id' => $implant->user_id ?: 'Not Assigned',
                    'Sales_Person_Name' => $implant->user ? $implant->user->name : 'Not Assigned',
                    'Technical_Person_Id' => $implant->user_id ?: 'Not Assigned',
                    'Technical_Person_Name' => $implant->user ? $implant->user->name : 'Not Assigned',
                    'Hospital' => $implant->hospital_name ?: 'Not Specified',
                    'Physician_ID' => 'Not Recorded',
                    'Physician_Name' => $implant->doctor_name ?: 'Not Specified',
                    'Warranty_Type' => $warrantyType,
                    'Therapy' => $implant->therapy_name ?: 'Not Specified',
                    'Device_Type' => $implant->device_name ?: 'Not Specified',
                    'IPG_Model_Number' => $implant->ipg_model_number ?: 'Not Specified',
                    'IPG_Model_Name' => $implant->ipg_model ?: 'Not Specified',
                    'IPG_Serial_Number' => $implant->ipg_serial_number ?: 'Not Specified',

                    // Lead data
                    'Lead_1_Model_Name' => isset($leads[1]) ? $leads[1]['model'] : 'Not Applicable',
                    'Lead_1_Model_Number' => isset($leads[1]) ? $leads[1]['model_number'] : 'Not Applicable',
                    'Lead_1_Serial_Number' => isset($leads[1]) ? $leads[1]['serial'] : 'Not Applicable',
                    'Lead_2_Model_Name' => isset($leads[2]) ? $leads[2]['model'] : 'Not Applicable',
                    'Lead_2_Model_Number' => isset($leads[2]) ? $leads[2]['model_number'] : 'Not Applicable',
                    'Lead_2_Serial_Number' => isset($leads[2]) ? $leads[2]['serial'] : 'Not Applicable',
                    'Lead_3_Model_Name' => isset($leads[3]) ? $leads[3]['model'] : 'Not Applicable',
                    'Lead_3_Model_Number' => isset($leads[3]) ? $leads[3]['model_number'] : 'Not Applicable',
                    'Lead_3_Serial_Number' => isset($leads[3]) ? $leads[3]['serial'] : 'Not Applicable',

                    // CSP data
                    'CSP' => $implant->is_csp_implant ? 'Yes' : 'No',
                    'CSP_Cathetre' => $implant->csp_catheter_model ?: 'Not Applicable',
                    'Extra_Lead' => $implant->has_extra_lead ? 'Yes' : 'No',
                    'CSP_Lead_Model_Name' => $implant->csp_lead_model ?: 'Not Applicable',
                    'CSP_Lead_Model_Number' => 'Not Recorded',
                    'CSP_Lead_Serial_Number' => $implant->csp_lead_serial ?: 'Not Applicable',
                    'Number_of_Unit' => 1,

                    // Patient data
                    'Patient_ID' => $patient ? $patient->id : 'Not Assigned',
                    'Patient_Name' => $patient ? $patient->name : 'Not Assigned',
                    'Patient_Email_ID' => $patient ? ($patient->email ?: 'No Email') : 'Not Assigned',
                    'Patient_Phone' => $patient ? ($patient->phone_number ?: 'No Phone') : 'Not Assigned'
                ];

                $reportData[] = $reportRow;
            }

            // Generate PDF using DomPDF
            $pdf = PDF::loadView('admin.reports.implant_report_pdf', [
                'reportData' => $reportData,
                'generatedAt' => now()->format('Y-m-d H:i:s')
            ]);

            // Set PDF options
            $pdf->setPaper('a3', 'landscape'); // Use A3 size in landscape orientation

            return $pdf->download('implant_report_' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Error generating PDF implant report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF implant report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}