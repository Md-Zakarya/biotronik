<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phonenumber' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);
    
        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'phonenumber' => $validatedData['phonenumber'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'email_verified_at' => null
            ]);
    
            // Assign 'user' role to newly registered user
            $user->assignRole('user');
    
            // Generate auth token for new user
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'message' => 'Registration successful.',
                'data' => [
                    'user' => new UserResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    // Revoke any existing tokens
    $user->tokens()->delete();

    // Create new token
    $token = $user->createToken('auth_token')->plainTextToken;

    // Get user's roles

    
    // Check if user is a basic user
    $isBasicUser = $user->hasRole('user') && !$user->hasRole('sales-representative');
    
    return response()->json([
        'message' => 'Login successful',
        'data' => [
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'is_basic_user' => $isBasicUser
        ]
    ]);
}

    public function logout(Request $request)
    {
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}