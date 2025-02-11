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
                ->whereHas('roles', function($q) {
                    // Exclude users with only 'user' role
                    $q->where('name', '!=', 'user');
                })
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames(),
                        // 'created_at' => $user->created_at,
                        'password' => $user->password,
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

    

}