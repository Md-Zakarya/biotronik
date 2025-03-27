<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\BrevoService;
use App\Services\BrevoSMSService;
use App\Models\User;

class OtpController extends Controller
{
    protected $brevoService;
    protected $brevoSMSService;

    public function __construct(BrevoService $brevoService, BrevoSMSService $brevoSMSService)
    {
        $this->brevoService = $brevoService;
        $this->brevoSMSService = $brevoSMSService;
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp = '123456';

            DB::table('otps')->updateOrInsert(
                ['contact' => $request->contact],
                [
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(10),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            Log::info("OTP for {$request->contact}: {$otp}");

            return response()->json(['message' => 'OTP sent successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function sendLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^[0-9]{10}$/'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Check if user exists
            $userExists = \App\Models\Patient::where('phone_number', $request->phone_number)->exists();

            if (!$userExists) {
                return response()->json([
                    'message' => 'No account found with this phone number. Please register instead.'
                ], 404);
            }

            // Generate OTP (for production, use random_int)
            $otp = '123456'; // For development
            // $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // For production

            // Store OTP in database
            DB::table('otps')->updateOrInsert(
                ['contact' => $request->phone_number],
                [
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(10),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            Log::info("Login OTP for {$request->phone_number}: {$otp}");

            // In production, uncomment to send OTP via SMS
            // $this->brevoSMSService->sendOTP($request->phone_number, $otp);

            return response()->json(['message' => 'Login OTP sent successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send login OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendRegistrationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^[0-9]{10}$/'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Check if user exists
            $userExists = \App\Models\Patient::where('phone_number', $request->phone_number)->exists();

            if ($userExists) {
                return response()->json([
                    'message' => 'An account with this phone number already exists. Please login instead.'
                ], 409); // 409 Conflict
            }

            // Generate OTP (for production, use random_int)
            $otp = '123456'; // For development
            // $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // For production

            // Store OTP in database
            DB::table('otps')->updateOrInsert(
                ['contact' => $request->phone_number],
                [
                    'otp' => $otp,
                    'expires_at' => now()->addMinutes(10),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            Log::info("Registration OTP for {$request->phone_number}: {$otp}");

            // In production, uncomment to send OTP via SMS
            // $this->brevoSMSService->sendOTP($request->phone_number, $otp);

            return response()->json(['message' => 'Registration OTP sent successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send registration OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            Log::info("Verifying OTP for contact: {$request->contact}");

            $otpRecord = DB::table('otps')
                ->where('contact', $request->contact)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpRecord) {
                Log::warning("Invalid or expired OTP for contact: {$request->contact}");
                return response()->json([
                    'message' => 'Invalid or expired OTP'
                ], 400);
            }

            // OTP is valid; delete it from the database (OTP is not getting deleted on OTP validation)
            // DB::table('otps')->where('contact', $request->contact)->delete();

            Log::info("OTP verified successfully for contact: {$request->contact}");
            return response()->json([
                'message' => 'OTP verified successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Verification failed for contact: {$request->contact} with error: {$e->getMessage()}");
            return response()->json([
                'message' => 'Verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function phoneLogin(Request $request)
    {
        $request->validate([
            'phonenumber' => 'required|string|exists:users,phonenumber',
        ]);

        $user = User::where('phonenumber', $request->phonenumber)->first();

        // Generate OTP
        // $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = '123456';
        // Save OTP to database
        DB::table('otps')->updateOrInsert(
            ['contact' => $user->phonenumber],
            [
                'otp' => $otp,
                'expires_at' => now()->addMinutes(10),
                'method' => 'sms',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Log OTP
        Log::info("OTP for {$user->phonenumber}: {$otp}");

        // Send OTP via SMS
        // $this->brevoSMSService->sendOTP($user->phonenumber, $otp);

        return response()->json([
            'message' => 'OTP sent successfully to your phone number.'
        ], 200);
    }

    public function verifyPhoneLogin(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'phonenumber' => 'required|string|exists:users,phonenumber',
                'otp' => 'required|string|size:6',
            ]);

            // Transform request for OTP verification
            $verificationRequest = new Request([
                'contact' => $request->phonenumber,
                'otp' => $request->otp
            ]);

            // Verify OTP
            $otpValid = $this->verifyOtp($verificationRequest);

            if ($otpValid->getStatusCode() !== 200) {
                return response()->json([
                    'message' => 'Invalid or expired OTP.'
                ], 400);
            }

            // Get user and generate token
            $user = User::where('phonenumber', $request->phonenumber)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found.'
                ], 404);
            }

            // Revoke any existing tokens
            $user->tokens()->delete();

            // Generate new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}