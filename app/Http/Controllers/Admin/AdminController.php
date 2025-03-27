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
}