<?php

namespace App\Http\Controllers\PatientAuth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PatientAuthController extends Controller
{
    public function registerPatient(Request $request)
{
    try {
        $validatedData = $request->validate([
            'Auth_name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:patients',
            'phone_number' => 'required|string|unique:patients|regex:/^[0-9]{10}$/',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'otp' => 'required|string|size:6',
        ], [
            'Auth_name.required' => 'Name is required',
            'Auth_name.max' => 'Name cannot exceed 255 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'phone_number.required' => 'Phone number is required',
            'phone_number.unique' => 'This phone number is already registered',
            'phone_number.regex' => 'Please enter a valid 10-digit phone number',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must contain at least one letter, one number, and one special character',
            'otp.required' => 'OTP is required',
            'otp.size' => 'OTP must be 6 digits',
        ]);

        // Verify OTP
        $otpRecord = DB::table('otps')
            ->where('contact', $validatedData['phone_number'])
            ->where('otp', $validatedData['otp'])
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Delete OTP after verification
        DB::table('otps')
            ->where('contact', $validatedData['phone_number'])
            ->where('otp', $validatedData['otp'])
            ->delete();

        $patient = Patient::create([
            'Auth_name' => $validatedData['Auth_name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'password' => Hash::make($validatedData['password']),
            'is_service_engineer' => false
        ]);

        $token = $patient->createToken('patient_auth_token')->plainTextToken;

        DB::table('otps')
        ->where('contact', $validatedData['phone_number'])
        ->where('otp', $validatedData['otp'])
        ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Patient registration successful',
            'data' => [
                'patient' => $patient,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Registration failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function loginWithEmail(Request $request)
{
    try {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required'
        ]);

        // Retrieve the patient by email
        $patient = Patient::where('email', $request->email)->first();

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'No patient account found with this email'
            ], 404);
        }

        if (!Hash::check($request->password, $patient->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password'
            ], 401);
        }

        $patient->tokens()->delete();
        $token = $patient->createToken('patient_auth_token')->plainTextToken;
        $role = $patient->is_service_engineer ? 'service engineer' : 'user';

        $responseData = [
            'patient' => $patient,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role' => $role,
            'message' => 'Patient login successful'
        ];

        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed', 
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Login failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function loginWithPhone(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string|regex:/^[0-9]{10}$/',
                'otp' => 'required|string|size:6'
            ], [
                'phone_number.required' => 'Phone number is required',
                'phone_number.regex' => 'Please enter a valid 10-digit phone number',
                'otp.required' => 'OTP is required',
                'otp.size' => 'OTP must be 6 digits'
            ]);

            $patient = Patient::where('phone_number', $request->phone_number)->first();

            if (!$patient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account not found with this phone number'
                ], 404);
            }

            if (!$this->verifyOtp($request->phone_number, $request->otp)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }
             // Delete OTP after successful verification
            DB::table('otps')
            ->where('contact', $request->phone_number)
            ->where('otp', $request->otp)
            ->delete();

            $patient->tokens()->delete();
            $token = $patient->createToken('patient_auth_token')->plainTextToken;

            DB::table('otps')
            ->where('contact', $request->phone_number)
            ->where('otp', $request->otp)
            ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'patient' => $patient,
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function verifyOtp($phoneNumber, $otp)
    {
        $otpRecord = DB::table('otps')
            ->where('contact', $phoneNumber)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->first();

        if ($otpRecord) {
            DB::table('otps')
                ->where('contact', $phoneNumber)
                ->where('otp', $otp)
                ->delete();
        }

        return $otpRecord ? true : false;
    }
}