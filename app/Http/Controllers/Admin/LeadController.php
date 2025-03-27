<?php

namespace App\Http\Controllers\Admin;
use App\Models\LeadSerial;
use App\Http\Controllers\Controller;
use App\Models\LeadModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\DeviceType;
use App\Models\User;
use App\Exports\LeadsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeadModelsExport;



class LeadController extends Controller
{


    public function exportLeadModelsCSV()
    {
        return Excel::download(new LeadModelsExport, 'lead_models_export.csv');
    }

    public function exportLeadsCSV()
    {
        return Excel::download(new LeadsExport, 'leads_export.csv');
    }


    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leads' => 'required|array',
            'leads.*.serial_number' => 'required|string|unique:lead_serials,serial_number',
            'leads.*.model_number' => 'required|string|exists:lead_models,model_number',
            'leads.*.distributor_id' => 'nullable|integer|exists:users,id',
        ], [
            'leads.*.model_number.exists' => 'The model number :input is not registered in the system.',
            'leads.*.serial_number.unique' => 'The serial number :input has already been taken.',
        ]);

        if ($validator->fails()) {
            // Format errors to identify different types of issues
            $duplicateSerialRows = [];
            $invalidModelRows = [];
            $invalidDistributorRows = [];
            $otherErrors = [];
            $errorMessages = $validator->errors()->toArray();

            foreach ($errorMessages as $field => $messages) {
                // Extract the index from field name patterns like "leads.0.serial_number"
                if (preg_match('/leads\.(\d+)\.([a-zA-Z_]+)/', $field, $matches)) {
                    $index = $matches[1];
                    $fieldName = $matches[2];
                    $rowNumber = intval($index) + 1; // Convert to 1-based indexing for user display
                    $serial = $request->leads[$index]['serial_number'] ?? "Unknown Serial";

                    // Categorize errors by field and error type
                    if ($fieldName === 'serial_number' && strpos($messages[0], 'already been taken') !== false) {
                        $duplicateSerialRows[] = "Row no {$rowNumber}";
                    } else if ($fieldName === 'model_number' && strpos($messages[0], 'not registered') !== false) {
                        $invalidModelRows[] = "Row no {$rowNumber}";
                    } else if ($fieldName === 'distributor_id' && strpos($messages[0], 'selected distributor_id is invalid') !== false) {
                        $invalidDistributorRows[] = "Row no {$rowNumber}";
                    } else {
                        // Other field-specific errors
                        $otherErrors[] = "Row no {$rowNumber} ({$serial}): {$fieldName} - {$messages[0]}";
                    }
                } else {
                    // For other error types not related to specific leads
                    $otherErrors[] = "{$field}: {$messages[0]}";
                }
            }

            // Helper function to format row lists consistently
            $formatErrorMessage = function ($rows, $singularMsg, $pluralMsg) {
                if (empty($rows))
                    return "";

                if (count($rows) == 1) {
                    return $rows[0] . " " . $singularMsg;
                } else {
                    return implode(", ", $rows) . " " . $pluralMsg;
                }
            };

            $errorMessages = [];

            // Add formatted error messages for each category
            if (!empty($duplicateSerialRows)) {
                $errorMessages[] = $formatErrorMessage(
                    $duplicateSerialRows,
                    "has duplicate serial number",
                    "have duplicate serial numbers"
                );
            }

            if (!empty($invalidModelRows)) {
                $errorMessages[] = $formatErrorMessage(
                    $invalidModelRows,
                    "has invalid model number",
                    "have invalid model numbers"
                );
            }

            if (!empty($invalidDistributorRows)) {
                $errorMessages[] = $formatErrorMessage(
                    $invalidDistributorRows,
                    "has invalid distributor ID",
                    "have invalid distributor IDs"
                );
            }

            // Add other errors if any
            if (!empty($otherErrors)) {
                $errorMessages[] = "Additional errors: " . implode("; ", $otherErrors);
            }

            // Join all error messages with periods
            $finalErrorMessage = implode(". ", $errorMessages);

            return response()->json([
                'success' => false,
                'message' => $finalErrorMessage ?: 'Validation failed',
                'errors' => $validator->errors() // Keep original errors for reference if needed
            ], 422);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->input('leads') as $index => $lead) {
                try {
                    LeadSerial::create([
                        'serial_number' => $lead['serial_number'],
                        'lead_model_number' => $lead['model_number'],
                        'distributor_id' => $lead['distributor_id'] ?? null,
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row no " . ($index + 1) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            if ($errorCount === 0) {
                DB::commit();
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed. No records were saved.',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "{$successCount} leads uploaded successfully",
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getLeads(Request $request)
    {
        $query = LeadSerial::with(['leadModel', 'distributor']);

        // Apply filters if provided
        if ($request->has('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        if ($request->has('device_type')) {
            $query->whereHas('leadModel', function ($q) use ($request) {
                $q->where('device_type', $request->device_type);
            });
        }

        if ($request->has('model_number')) {
            $query->where('lead_model_number', $request->model_number);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 15);
        $leads = $query->paginate($perPage);

        // Transform data to include all required fields
        $transformedData = $leads->through(function ($lead) {
            return [
                'id' => $lead->id,
                'serial_number' => $lead->serial_number,
                'model_number' => $lead->lead_model_number,
                'model_name' => $lead->leadModel ? $lead->leadModel->model_name : null,
                'device_type' => $lead->leadModel ? $lead->leadModel->device_type : null,
                'distributor_id' => $lead->distributor_id,
                'distributor_name' => $lead->distributor ? $lead->distributor->name : null,

            ];
        });

        return response()->json($transformedData);
    }

    /**
     * Get all lead models with their information
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLeadModels(Request $request)
    {
        try {
            $query = LeadModel::query();

            // Search by model name or number as user types
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('model_name', 'like', "%{$searchTerm}%")
                        ->orWhere('model_number', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by device type if provided
            if ($request->filled('device_type')) {
                $query->where('device_type', $request->device_type);
            }

            // Apply sorting with fallback to default
            $sortField = $request->get('sort_by', 'model_name');
            $sortDirection = $request->get('sort_direction', 'asc');

            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['id', 'model_name', 'model_number', 'device_type', 'created_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
            } else {
                $query->orderBy('model_name'); // Default fallback
            }

            // Get paginated results with configurable per_page parameter
            $perPage = min(max(intval($request->get('per_page', 15)), 1), 100);

            // Optimize query by using withCount instead of with + count in the transform
            $leadModels = $query->withCount('serials')->paginate($perPage);

            // Preserve query parameters in pagination links
            $leadModels->appends($request->except('page'));

            // Transform data to include all required fields
            $transformedData = $leadModels->through(function ($model) {
                return [
                    'id' => $model->id,
                    'model_number' => $model->model_number,
                    'model_name' => $model->model_name,
                    'device_type' => $model->device_type,
                    'total_serials' => $model->serials_count, // Using the count from withCount
                    'created_at' => $model->created_at,
                    'updated_at' => $model->updated_at
                ];
            });

            return response()->json([
                'data' => $transformedData->items(),
                'pagination' => [
                    'current_page' => $transformedData->currentPage(),
                    'per_page' => $transformedData->perPage(),
                    'total' => $transformedData->total(),
                    'last_page' => $transformedData->lastPage(),
                    'from' => $transformedData->firstItem(),
                    'to' => $transformedData->lastItem(),
                ],
                'links' => [
                    'first' => $transformedData->url(1),
                    'last' => $transformedData->url($transformedData->lastPage()),
                    'prev' => $transformedData->previousPageUrl(),
                    'next' => $transformedData->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving lead models',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Create a new lead model
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createLeadModel(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'model_number' => 'required|string|unique:lead_models,model_number',
            'model_name' => 'required|string',
            'device_type' => 'required|string',
        ]);

        // Return any validation errors
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ensure device_type exists in device_types.device_name
        $exists = DeviceType::where('device_name', $request->device_type)->exists();
        if (!$exists) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'device_type' => ['The selected device type does not exist in our records.'],
                ],
            ], 422);
        }

        // Create the new Lead Model record
        $leadModel = LeadModel::create([
            'model_number' => $request->model_number,
            'model_name' => $request->model_name,
            'device_type' => $request->device_type,
        ]);

        // Return success response
        return response()->json([
            'message' => 'Lead model created successfully',
            'data' => $leadModel,
        ], 201);
    }

    public function assignDistributor(Request $request)
    {
        $validated = $request->validate([
            'lead_serial_id' => 'required|exists:lead_serials,id',
            'distributor_id' => 'required|exists:users,id',
        ]);

        try {
            $leadSerial = LeadSerial::find($validated['lead_serial_id']);

            // Check if the user has distributor role
            $distributor = User::find($validated['distributor_id']);
            if (!$distributor || !$distributor->hasRole('distributor')) {
                return response()->json([
                    'message' => 'Invalid distributor ID or user is not a distributor'
                ], 400);
            }

            $leadSerial->update([
                'distributor_id' => $validated['distributor_id']
            ]);

            return response()->json([
                'message' => 'Distributor assigned to lead serial successfully',
                'data' => [
                    'id' => $leadSerial->id,
                    'serial_number' => $leadSerial->serial_number,
                    'model_number' => $leadSerial->lead_model_number,
                    'distributor_name' => $distributor->name
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error assigning distributor to lead serial',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Search lead serials by serial number
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchLeadSerials(Request $request)
    {
        try {
            $query = LeadSerial::with(['leadModel', 'distributor']);

            // Search by serial number
            if ($request->filled('serial_number')) {
                $serialNumber = $request->serial_number;
                $query->where('serial_number', 'like', "%{$serialNumber}%");
            }

            // Optionally filter by model number
            if ($request->filled('model_number')) {
                $query->where('lead_model_number', $request->model_number);
            }

            // Optionally filter by device type
            if ($request->filled('device_type')) {
                $query->whereHas('leadModel', function ($q) use ($request) {
                    $q->where('device_type', $request->device_type);
                });
            }

            // Optionally filter by distributor
            if ($request->filled('distributor_id')) {
                $query->where('distributor_id', $request->distributor_id);
            }

            // Get results (paginated or all based on request)
            if ($request->get('paginate', true)) {
                $perPage = min(max(intval($request->get('per_page', 15)), 1), 100);
                $results = $query->paginate($perPage);

                // Transform data to include all required fields
                $transformedData = $results->through(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'serial_number' => $lead->serial_number,
                        'model_number' => $lead->lead_model_number,
                        'model_name' => $lead->leadModel ? $lead->leadModel->model_name : null,
                        'device_type' => $lead->leadModel ? $lead->leadModel->device_type : null,
                        'distributor_id' => $lead->distributor_id,
                        'distributor_name' => $lead->distributor ? $lead->distributor->name : null,
                    ];
                });

                return response()->json([
                    'data' => $transformedData->items(),
                    'pagination' => [
                        'current_page' => $transformedData->currentPage(),
                        'per_page' => $transformedData->perPage(),
                        'total' => $transformedData->total(),
                        'last_page' => $transformedData->lastPage(),
                    ]
                ]);
            } else {
                // Return all results (use with caution)
                $results = $query->get()->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'serial_number' => $lead->serial_number,
                        'model_number' => $lead->lead_model_number,
                        'model_name' => $lead->leadModel ? $lead->leadModel->model_name : null,
                        'device_type' => $lead->leadModel ? $lead->leadModel->device_type : null,
                        'distributor_id' => $lead->distributor_id,
                        'distributor_name' => $lead->distributor ? $lead->distributor->name : null,
                    ];
                });

                return response()->json(['data' => $results]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error searching lead serials',
                'error' => $e->getMessage()
            ], 500);
        }
    }





}
