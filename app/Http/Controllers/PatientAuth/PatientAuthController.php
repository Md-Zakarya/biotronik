<?php

namespace App\Http\Controllers\PatientAuth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Validation\Rule;


class PatientAuthController extends Controller
{   



    //register method 01-05-2025 
   
    public function registerPatient(Request $request)
    {
        try {
            $validatedData = $request->validate([
                 // the email uniqueness was removed due to the request, but commented if will be used for later. 
                // 'Auth_name' => 'required|string|max:255',
                // 'email' => [
                //     'required',
                //     'string',
                //     'email',
                //     function ($attribute, $value, $fail) use ($request) {
                //         if (empty($request->phone_number)) {
                //             $exists = Patient::where('email', $value)->exists();
                //             if ($exists) {
                //                 $fail('The email has already been taken.');
                //             }
                //         }
                //     }
                // ],
                // 'phone_number' => 'nullable|string|unique:patients|regex:/^[0-9]{10}$/',
                // 'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
                // 'otp' => 'required_with:phone_number|string|size:6',
                'Auth_name' => 'required|string|max:255',
                'email' => 'nullable|string|email', 
                'phone_number' => 'required|string|unique:patients|regex:/^[0-9]{10}$/', // Changed nullable to required
                'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
                'otp' => 'required|string|size:6',
           
            ], [
                'Auth_name.required' => 'Name is required',
                'Auth_name.max' => 'Name cannot exceed 255 characters',
                'email.required' => 'Email is required',
                'email.email' => 'Please enter a valid email address',
                // 'email.unique' => 'This email is already registered',
                'phone_number.required' => 'Phone number is required',
                'phone_number.unique' => 'This phone number is already registered',
                'phone_number.regex' => 'Please enter a valid 10-digit phone number',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.regex' => 'Password must contain at least one letter, one number, and one special character',
                'otp.required' => 'OTP is required',
                'otp.size' => 'OTP must be 6 digits',
            ]);

            if (isset($validatedData['phone_number']) && !empty($validatedData['phone_number'])) {
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
            }

            $patient = Patient::create([
                'Auth_name' => $validatedData['Auth_name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'] ?? null,
                'password' => Hash::make($validatedData['password']),
                'is_service_engineer' => false
            ]);

            $token = $patient->createToken('patient_auth_token')->plainTextToken;

            if (isset($validatedData['phone_number']) && !empty($validatedData['phone_number'])) {
                DB::table('otps')
                    ->where('contact', $validatedData['phone_number'])
                    ->where('otp', $validatedData['otp'])
                    ->delete();
            }
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

    //fixed the loginWithEmail function with the correct token code generation
    public function loginWithEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);

            // Check if this email belongs to a sales representative
            $patient = Patient::where('email', $request->email)->first();

            if (!$patient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No account found with this email'
                ], 404);
            }

            if (!Hash::check($request->password, $patient->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // If patient is a service engineer (sales representative), generate User token
            if ($patient->is_service_engineer) {
                $user = User::where('id', $patient->user_id)->first();
                $user->tokens()->delete();
                $token = $user->createToken('auth_token')->plainTextToken;
                $role = 'sales-representative';
            } else {
                // For regular patients, generate Patient token
                $patient->tokens()->delete();
                $token = $patient->createToken('patient_auth_token')->plainTextToken;
                $role = 'user';
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'patient' => $patient,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'role' => $role
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






    // public function loginWithEmail(Request $request)
// {
//     try {
//         $request->validate([
//             'email' => 'required|string|email',
//             'password' => 'required|string'
//         ], [
//             'email.required' => 'Email is required',
//             'email.email' => 'Please enter a valid email address',
//             'password.required' => 'Password is required'
//         ]);

    //         // Retrieve the patient by email
//         $patient = Patient::where('email', $request->email)->first();

    //         if (!$patient) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'No patient account found with this email'
//             ], 404);
//         }

    //         if (!Hash::check($request->password, $patient->password)) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Invalid email or password'
//             ], 401);
//         }

    //         $patient->tokens()->delete();
//         $token = $patient->createToken('patient_auth_token')->plainTextToken;
//         $role = $patient->is_service_engineer ? 'service engineer' : 'user';

    //         $responseData = [
//             'patient' => $patient,
//             'access_token' => $token,
//             'token_type' => 'Bearer',
//             'role' => $role,
//             'message' => 'Patient login successful'
//         ];

    //         return response()->json([
//             'status' => 'success',
//             'data' => $responseData
//         ], 200);

    //     } catch (ValidationException $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Validation failed', 
//             'errors' => $e->errors()
//         ], 422);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Login failed',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }


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

            // Check if patient is a service engineer
            if ($patient->is_service_engineer) {
                $user = User::where('id', $patient->user_id)->first();
                $user->tokens()->delete();
                $token = $user->createToken('auth_token')->plainTextToken;
                $role = 'sales-representative';
            } else {
                // For regular patients, generate Patient token
                $patient->tokens()->delete();
                $token = $patient->createToken('patient_auth_token')->plainTextToken;
                $role = 'user';
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'patient' => $patient,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'role' => $role
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




    // public function loginWithPhone(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'phone_number' => 'required|string|regex:/^[0-9]{10}$/',
    //             'otp' => 'required|string|size:6'
    //         ], [
    //             'phone_number.required' => 'Phone number is required',
    //             'phone_number.regex' => 'Please enter a valid 10-digit phone number',
    //             'otp.required' => 'OTP is required',
    //             'otp.size' => 'OTP must be 6 digits'
    //         ]);

    //         $patient = Patient::where('phone_number', $request->phone_number)->first();

    //         if (!$patient) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Account not found with this phone number'
    //             ], 404);
    //         }

    //         if (!$this->verifyOtp($request->phone_number, $request->otp)) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Invalid or expired OTP'
    //             ], 400);
    //         }
    //         // Delete OTP after successful verification
    //         DB::table('otps')
    //             ->where('contact', $request->phone_number)
    //             ->where('otp', $request->otp)
    //             ->delete();

    //         $patient->tokens()->delete();
    //         $token = $patient->createToken('patient_auth_token')->plainTextToken;

    //         DB::table('otps')
    //             ->where('contact', $request->phone_number)
    //             ->where('otp', $request->otp)
    //             ->delete();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Login successful',
    //             'data' => [
    //                 'patient' => $patient,
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer'
    //             ]
    //         ], 200);

    //     } catch (ValidationException $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors()
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Login failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

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