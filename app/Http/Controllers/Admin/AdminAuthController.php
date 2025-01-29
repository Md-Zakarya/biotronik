<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'role' => 'required|string|in:admin,sales-representative,distributor,finance,supply,back-office,zonal-manager',
            'email' => 'required|string|email|max:255|lowercase',
            'password' => 'required|string|min:8',
        ], [
            'role.required' => 'Role selection is required',
            'role.in' => 'Invalid role selected',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters'
        ]);
    
        $user = User::where('email', trim($request->email))->first();
    
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials provided.'],
            ])->status(401);
        }
    
    
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid credentials provided.'],
            ])->status(401);
        }
    
        if (!$user->hasRole($request->role)) {
            throw ValidationException::withMessages([
                'role' => ['Access denied. Insufficient permissions.'],
            ])->status(403);
        }
    
        // Log successful login
        Log::info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $request->role,
            'ip' => $request->ip()
        ]);
    
        // Revoke existing tokens
        $user->tokens()->delete();  
    
        // Create new token
        $token = $user->createToken('auth_token', [$request->role])->plainTextToken;
    
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $request->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }
}