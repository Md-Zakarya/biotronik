<?php

namespace App\Http\Controllers\Dist;

use App\Models\IpgModel;
use App\Models\IpgSerial;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\DeviceType;


class IpgModelController extends Controller
{


    /**
     * Associate multiple IPG serial numbers with existing models and optionally assign to distributors
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function associateMultipleSerials(Request $request)
    {
        try {
            // Validate the request has devices array
            $validator = Validator::make($request->all(), [
                'devices' => 'required|array|min:1',
                'devices.*.ipg_serial_number' => 'required|string|distinct|unique:ipg_serials,ipg_serial_number',
                'devices.*.model_number' => 'required|string|exists:ipg_models,model_number',
                'devices.*.distributor_id' => 'nullable|exists:users,id',
                'devices.*.date_added' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                // Format errors to identify different types of issues
                $duplicateSerialRows = [];
                $invalidModelRows = [];
                $invalidDistributorRows = [];
                $otherErrors = [];
                $errorMessages = $validator->errors()->toArray();

                foreach ($errorMessages as $field => $messages) {
                    // Extract the index from field name patterns like "devices.0.ipg_serial_number"
                    if (preg_match('/devices\.(\d+)\.([a-zA-Z_]+)/', $field, $matches)) {
                        $index = $matches[1];
                        $fieldName = $matches[2];
                        $rowNumber = intval($index) + 1; // Convert to 1-based indexing for user display
                        $serial = $request->devices[$index]['ipg_serial_number'] ?? "Unknown Serial";

                        // Categorize errors by field and error type
                        if ($fieldName === 'ipg_serial_number' && strpos($messages[0], 'already been taken') !== false) {
                            $duplicateSerialRows[] = "Row no {$rowNumber}";
                        } else if (
                            $fieldName === 'model_number' && (
                                strpos($messages[0], 'selected model_number is invalid') !== false ||
                                strpos($messages[0], 'selected devices') !== false &&
                                strpos($messages[0], 'model_number is invalid') !== false
                            )
                        ) {
                            $invalidModelRows[] = "Row no {$rowNumber}";
                        } else if (
                            $fieldName === 'distributor_id' && (
                                strpos($messages[0], 'selected distributor_id is invalid') !== false ||
                                strpos($messages[0], 'selected devices') !== false &&
                                strpos($messages[0], 'distributor_id is invalid') !== false
                            )
                        ) {
                            $invalidDistributorRows[] = "Row no {$rowNumber}";
                        } else {
                            // Other field-specific errors
                            $otherErrors[] = "Row no {$rowNumber} ({$serial}): {$fieldName} - {$messages[0]}";
                        }
                    } else {
                        // For other error types not related to specific devices
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

            $results = [];
            $failedDevices = [];

            // Process each device
            foreach ($request->devices as $device) {
                try {
                    // Find the model for this device
                    $model = IpgModel::where('model_number', $device['model_number'])->first();

                    if (!$model) {
                        $failedDevices[] = [
                            'ipg_serial_number' => $device['ipg_serial_number'],
                            'model_number' => $device['model_number'],
                            'error' => 'Model not found'
                        ];
                        continue;
                    }

                    // If distributor_id is provided, verify the user exists and has distributor role
                    $distributorId = isset($device['distributor_id']) ? $device['distributor_id'] : null;

                    if ($distributorId) {
                        $distributor = \App\Models\User::find($distributorId);
                        if (!$distributor || !$distributor->hasRole('distributor')) {
                            $failedDevices[] = [
                                'ipg_serial_number' => $device['ipg_serial_number'],
                                'model_number' => $device['model_number'],
                                'distributor_id' => $distributorId,
                                'error' => 'Invalid distributor ID or user is not a distributor'
                            ];
                            continue;
                        }
                    }

                    // Create new IPG Serial record with distributor_id if provided
                    $ipgSerial = IpgSerial::create([
                        'ipg_serial_number' => $device['ipg_serial_number'],
                        'model_number' => $device['model_number'],
                        'distributor_id' => $distributorId,
                        'date_added' => $device['date_added'] ?? now(),
                    ]);

                    $results[] = [
                        'serial' => $ipgSerial,
                        'model' => [
                            'model_number' => $model->model_number,
                            'model_name' => $model->model_name,
                            'device_type' => $model->device_type
                        ],
                        'distributor_id' => $distributorId
                    ];
                } catch (\Exception $e) {
                    $failedDevices[] = [
                        'ipg_serial_number' => $device['ipg_serial_number'],
                        'model_number' => $device['model_number'],
                        'distributor_id' => $distributorId ?? null,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Return response with results
            return response()->json([
                'success' => count($results) > 0,
                'message' => count($results) > 0
                    ? 'IPG serials associated with models successfully'
                    : 'Failed to associate any IPG serials',
                'data' => [
                    'successful' => $results,
                    'failed' => $failedDevices
                ],
                'total_processed' => count($request->devices),
                'success_count' => count($results),
                'failure_count' => count($failedDevices)
            ], count($results) > 0 ? 201 : 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process IPG serial registrations',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get all IPG models with their model numbers and names
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = IpgModel::select('model_number', 'model_name', 'device_type');

            // Search by model name only if search parameter is provided
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where('model_name', 'like', "%{$searchTerm}%");
            } else if (!$request->has('device_type')) {
                // If no search or filter is provided, return a limited set instead of all models
                // This prevents loading the entire database when first loading the page
                $query->limit(20);
            }

            // Filter by device type if provided (keeping this filter option)
            if ($request->has('device_type')) {
                $query->where('device_type', $request->device_type);
            }

            // Apply sorting and get results with pagination
            $models = $query->orderBy('model_name')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $models,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch IPG models',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Associate a distributor with an IPG serial
     */
    public function assignDistributor(Request $request)
    {
        $validated = $request->validate([
            'ipg_serial_id' => 'required|exists:ipg_serials,id',
            'distributor_id' => 'required|exists:users,id',
        ]);

        $ipgSerial = IpgSerial::find($validated['ipg_serial_id']);

        // Check if the user has distributor role
        $distributor = \App\Models\User::find($validated['distributor_id']);
        if (!$distributor || !$distributor->hasRole('distributor')) {
            return response()->json([
                'message' => 'Invalid distributor ID or user is not a distributor'
            ], 400);
        }

        $ipgSerial->update([
            'distributor_id' => $validated['distributor_id']
        ]);

        return response()->json([
            'message' => 'Distributor assigned to IPG serial successfully',
            'ipg_serial' => $ipgSerial
        ]);
    }

    /**
     * Get all IPG serial numbers with their details.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllSerials(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $query = IpgSerial::with(['ipgModel:model_number,model_name,device_type,warranty', 'distributor:id,name'])
            ->select('id', 'ipg_serial_number', 'model_number', 'distributor_id', 'created_at')
            ->orderBy('created_at', 'desc');

        // Filter by serial number if provided
        if ($request->has('search')) {
            $query->where('ipg_serial_number', 'like', '%' . $request->search . '%');
        }

        // Filter by model number if provided
        if ($request->has('model_number')) {
            $query->where('model_number', $request->model_number);
        }

        // Filter by distributor if provided
        if ($request->has('distributor_id')) {
            $query->where('distributor_id', $request->distributor_id);
        }

        $serials = $query->paginate($perPage);

        // Transform the data
        $transformedSerials = $serials->through(function ($serial) {
            return [
                'id' => $serial->id,
                'ipg_serial_number' => $serial->ipg_serial_number,
                'model_number' => $serial->model_number,
                'model_name' => $serial->ipgModel ? $serial->ipgModel->model_name : null,
                'device_type' => $serial->ipgModel ? $serial->ipgModel->device_type : null,
                'warranty' => $serial->ipgModel ? $serial->ipgModel->warranty : null,
                'distributor_id' => $serial->distributor_id,
                'distributor_name' => $serial->distributor ? $serial->distributor->name : null,
                'created_at' => $serial->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'serials' => $transformedSerials->items(),
            'meta' => [
                'total' => $transformedSerials->total(),
                'per_page' => $transformedSerials->perPage(),
                'current_page' => $transformedSerials->currentPage(),
                'last_page' => $transformedSerials->lastPage(),
                'from' => $transformedSerials->firstItem(),
                'to' => $transformedSerials->lastItem(),
                'path' => $request->url(),
                'has_more_pages' => $transformedSerials->hasMorePages(),
                'links' => [
                    'first' => $transformedSerials->url(1),
                    'last' => $transformedSerials->url($transformedSerials->lastPage()),
                    'prev' => $transformedSerials->previousPageUrl(),
                    'next' => $transformedSerials->nextPageUrl()
                ]
            ]
        ]);
    }



    public function getDeviceTypes()
    {
        $deviceTypes = DeviceType::all()->map(function ($deviceType) {
            return [
                'device_id' => $deviceType->device_id,
                'device_name' => $deviceType->device_name
            ];
        });

        return response()->json([
            'success' => true,
            'device_types' => $deviceTypes
        ]);
    }


    /**
     * Store a newly created IPG model in the database.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'model_number' => 'required|string|unique:ipg_models,model_number',
                'model_name' => 'required|string|max:255',
                'device_type' => 'required|string|exists:device_types,device_name',
                'cardiomessenger_enable' => 'required|boolean',
                'warranty' => 'required|integer|min:0',
                'mr_enabled' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create new IPG model
            $ipgModel = IpgModel::create([
                'model_number' => $request->model_number,
                'model_name' => $request->model_name,
                'device_type' => $request->device_type,
                'cardiomessenger_enable' => $request->cardiomessenger_enable,
                'warranty' => $request->warranty,
                'mr_enabled' => $request->mr_enabled
            ]);

            return response()->json([
                'success' => true,
                'message' => 'IPG model created successfully',
                'data' => $ipgModel
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create IPG model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all IPG models with optional filtering
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function getAllIpgModels(Request $request)
    // {
    //     $query = IpgModel::query();

    //     // Filter by device type
    //     if ($request->has('device_type')) {
    //         $query->where('device_type', $request->device_type);
    //     }

    //     // Filter by MR compatibility
    //     if ($request->has('mr_enabled')) {
    //         $query->where('mr_enabled', $request->boolean('mr_enabled'));
    //     }

    //     // Filter by CardioMessenger compatibility
    //     if ($request->has('cardiomessenger_enable')) {
    //         $query->where('cardiomessenger_enable', $request->boolean('cardiomessenger_enable'));
    //     }

    //     // Filter by warranty period (minimum days)
    //     if ($request->has('min_warranty')) {
    //         $query->where('warranty', '>=', $request->min_warranty);
    //     }

    //     // Search by model name or number
    //     if ($request->has('search')) {
    //         $searchTerm = $request->search;
    //         $query->where(function ($q) use ($searchTerm) {
    //             $q->where('model_name', 'like', "%{$searchTerm}%")
    //                 ->orWhere('model_number', 'like', "%{$searchTerm}%");
    //         });
    //     }

    //     // Order results
    //     $orderBy = $request->input('order_by', 'model_name');
    //     $orderDirection = $request->input('order_direction', 'asc');

    //     // Validate order_by field to prevent SQL injection
    //     $validOrderFields = ['model_name', 'model_number', 'device_type', 'warranty'];
    //     if (in_array($orderBy, $validOrderFields)) {
    //         $query->orderBy($orderBy, $orderDirection === 'desc' ? 'desc' : 'asc');
    //     }

    //     // Load relationships if needed
    //     if ($request->has('with_device_type')) {
    //         $query->with('deviceType');
    //     }

    //     if ($request->has('with_serials')) {
    //         $query->with('serials');
    //     }

    //     // Pagination
    //     $perPage = $request->input('per_page', 15);
    //     $models = $query->paginate($perPage);

    //     return response()->json([
    //         'message' => 'IPG models retrieved successfully',
    //         'data' => $models,
    //         'meta' => [
    //             'total' => $models->total(),
    //             'per_page' => $models->perPage(),
    //             'current_page' => $models->currentPage(),
    //             'last_page' => $models->lastPage(),
    //         ]
    //     ]);
    // }


    public function getAllIpgModels(Request $request)
    {
        $query = IpgModel::query();

        // Filter by device type
        if ($request->has('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Filter by MR compatibility
        if ($request->has('mr_enabled')) {
            $query->where('mr_enabled', $request->boolean('mr_enabled'));
        }

        // Filter by CardioMessenger compatibility
        if ($request->has('cardiomessenger_enable')) {
            $query->where('cardiomessenger_enable', $request->boolean('cardiomessenger_enable'));
        }

        // Filter by warranty period (minimum days)
        if ($request->has('min_warranty')) {
            $query->where('warranty', '>=', $request->min_warranty);
        }

        // Search by model name or number
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('model_name', 'like', "%{$searchTerm}%")
                    ->orWhere('model_number', 'like', "%{$searchTerm}%");
            });
        }

        // Order results
        $orderBy = $request->input('order_by', 'created_at'); // Changed default to created_at
        $orderDirection = $request->input('order_direction', 'desc'); // Changed default to desc

        // Validate order_by field to prevent SQL injection
        $validOrderFields = ['model_name', 'model_number', 'device_type', 'warranty', 'created_at']; // Added created_at
        if (in_array($orderBy, $validOrderFields)) {
            $query->orderBy($orderBy, $orderDirection === 'desc' ? 'desc' : 'asc');
        }

        // Load relationships if needed
        if ($request->has('with_device_type')) {
            $query->with('deviceType');
        }

        if ($request->has('with_serials')) {
            $query->with('serials');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $models = $query->paginate($perPage);

        return response()->json([
            'message' => 'IPG models retrieved successfully',
            'data' => $models,
            'meta' => [
                'total' => $models->total(),
                'per_page' => $models->perPage(),
                'current_page' => $models->currentPage(),
                'last_page' => $models->lastPage(),
            ]
        ]);
    }



    public function searchSerials(Request $request)
    {
        try {
            $query = IpgSerial::select('id', 'ipg_serial_number', 'model_number')
                ->with('ipgModel:model_number,model_name,device_type');

            // Add a condition to exclude serials that exist in implants
            $query->whereNotExists(function ($subquery) {
                $subquery->select(\DB::raw(1))
                    ->from('implants')
                    ->whereColumn('implants.ipg_serial_number', 'ipg_serials.ipg_serial_number');
            });

            // Search term
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where('ipg_serial_number', 'like', "%{$searchTerm}%");
            }

            // Limit results (default 10)
            $limit = $request->input('limit', 10);
            $serials = $query->limit($limit)->get();

            // Format for dropdown
            $formattedSerials = $serials->map(function ($serial) {
                $modelInfo = $serial->ipgModel ?
                    " ({$serial->ipgModel->model_name} - {$serial->ipgModel->device_type})" :
                    "";

                // Determine therapy name based on device type
                $deviceType = $serial->ipgModel ? $serial->ipgModel->device_type : '';
                $therapyName = (stripos($deviceType, 'tachy') !== false) ? 'Tachy' : 'Brady';

                return [
                    'id' => $serial->id,
                    'value' => $serial->ipg_serial_number,
                    'label' => $serial->ipg_serial_number . $modelInfo,
                    // Additional model information
                    'model_number' => $serial->model_number,
                    'model_name' => $serial->ipgModel ? $serial->ipgModel->model_name : null,
                    'device_type' => $serial->ipgModel ? $serial->ipgModel->device_type : null,
                    'therapy_name' => $therapyName // Added therapy name based on device type
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSerials
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search IPG serials',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Search for available IPG serials that haven't been used in implants
     */
    public function searchAvailableSerials(Request $request)
    {
        try {
            // Start with the same query as before
            $query = IpgSerial::select('id', 'ipg_serial_number', 'model_number')
                ->with('ipgModel:model_number,model_name,device_type');

            // Add a condition to exclude serials that exist in implants
            $query->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('implants')
                    ->whereColumn('implants.ipg_serial_number', 'ipg_serials.ipg_serial_number');
            });

            // Search term
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where('ipg_serial_number', 'like', "%{$searchTerm}%");
            }

            // Limit results (default 10)
            $limit = $request->input('limit', 10);
            $serials = $query->limit($limit)->get();

            // Format for dropdown (same as original)
            $formattedSerials = $serials->map(function ($serial) {
                $modelInfo = $serial->ipgModel ?
                    " ({$serial->ipgModel->model_name} - {$serial->ipgModel->device_type})" :
                    "";

                return [
                    'id' => $serial->id,
                    'value' => $serial->ipg_serial_number,
                    'label' => $serial->ipg_serial_number . $modelInfo,
                    // Additional model information
                    'model_number' => $serial->model_number,
                    'model_name' => $serial->ipgModel ? $serial->ipgModel->model_name : null,
                    'device_type' => $serial->ipgModel ? $serial->ipgModel->device_type : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSerials
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search available IPG serials',
                'error' => $e->getMessage()
            ], 500);
        }
    }







}


