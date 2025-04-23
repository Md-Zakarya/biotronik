<?php

namespace App\Http\Controllers\PatientControllers;

use App\Http\Controllers\Controller;
use App\Models\BackupService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Patient;

class BackupServiceController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'state' => 'required|string',
                'hospital_name' => 'required|string',
                'channel_partner' => 'required|string',
                'appointment_datetime' => [
                    'required',
                    'date_format:Y-m-d H:i',
                    'after:now'
                ],
                'service_type' => 'required|string',
                'service_duration' => 'required|string',

                // Payment validation 
                'gst_number' => 'nullable|string',
                'pan_number' => 'nullable|string',
                'amount' => 'required|numeric',
                'payment_method' => 'required|in:upi,card',

                // UPI payment fields
                'upi_id' => 'required_if:payment_method,upi|string',
                'upi_phone' => 'required_if:payment_method,upi|string|min:10',

                // Card payment fields
                'card_number' => 'required_if:payment_method,card|string|min:16|max:16',
                'card_expiry' => 'required_if:payment_method,card|string',
                'card_cvc' => 'required_if:payment_method,card|string|min:3|max:4',
                'card_country' => 'required_if:payment_method,card|string',
                'card_postal_code' => 'required_if:payment_method,card|string',
            ]);

            DB::beginTransaction();

            // Prepare payment details
            $paymentData = [
                'gst_number' => $validated['gst_number'] ?? null,
                'pan_number' => $validated['pan_number'] ?? null,
                'amount' => $validated['amount'],
                'payment_type' => 'backup_service',
                'payment_status' => 'completed',
                'payment_details' => $this->preparePaymentDetails($validated)
            ];

            $user = $request->user(); // Get the authenticated User object
            if (!$user) {
                DB::rollBack();
                return response()->json(['message' => 'User not authenticated.'], 401);
            }

            // Create payment using PaymentService, passing the user's ID
            $payment = $this->paymentService->createPayment(
                $paymentData,
                $user->id // Pass the integer ID of the user here
            );

            $patientModel = Patient::where('id', $user->id)->first();

            if ($patientModel) {
                logger()->info('Patient found for user ID: ' . $user->id, ['patient_id' => $patientModel->id, 'relative_name' => $patientModel->relative_name, 'relative_phone' => $patientModel->relative_phone]);
            } else {
                logger()->warning('No patient found for user ID: ' . $user->id);
            }

            // Generate a unique backup ID (format: BKP-YYYYMMDD-XXXXX)
            $date = now()->format('Ymd');
            $randomPart = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $backupId = "BKP-{$date}-{$randomPart}";

            $backupService = BackupService::create([
                'backup_id' => $backupId,  // Add this line
                'state' => $validated['state'],
                'hospital_name' => $validated['hospital_name'],
                'channel_partner' => $validated['channel_partner'],
                'appointment_datetime' => $validated['appointment_datetime'],
                'service_type' => $validated['service_type'],
                'service_duration' => $validated['service_duration'],
                'patient_id' => $user->id, // Use the user's ID
                'payment_id' => $payment->id,
                'status' => 'pending',
                'accompanying_person_name' => $patientModel->relative_name, // Use the found Patient model
                'accompanying_person_phone' => $patientModel->relative_phone // Use the found Patient model
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Backup service request created successfully',
                'data' => [
                    'backup_service' => $backupService,
                    'payment' => $payment
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating backup service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function preparePaymentDetails(array $validated): array
    {
        $details = [
            'method' => $validated['payment_method']
        ];

        if ($validated['payment_method'] === 'upi') {
            $details['upi_id'] = $validated['upi_id'];
            $details['upi_phone'] = $validated['upi_phone'];
        } else {
            $details['card_number'] = $validated['card_number'];
            $details['card_expiry'] = $validated['card_expiry'];
            $details['card_cvc'] = $validated['card_cvc'];
            $details['card_country'] = $validated['card_country'];
            $details['card_postal_code'] = $validated['card_postal_code'];
        }

        return $details;
    }


    public function getBackupServiceStatus(Request $request)
    {
        try {
            $user = $request->user();

            // Retrieve the latest backup service request for the authenticated user
            $backupService = BackupService::with('serviceEngineer')
                ->where('patient_id', $user->id)
                ->latest()
                ->first();

            $hasRegistered = false; // Initialize the variable

            if (!$backupService) {
                return response()->json([
                    'message' => 'Backup service request not found',
                    'has_registered' => $hasRegistered // Include the variable in the response
                ], 404);
            }

            $hasRegistered = true; // Set to true if a backup service is found

            // Format the response data
            $responseData = [
                'id' => $backupService->id,
                'backup_id' => $backupService->backup_id,
                'status' => $backupService->status,
                'appointment_datetime' => $backupService->appointment_datetime->format('Y-m-d H:i'),
                'service_type' => $backupService->service_type,
                'service_duration' => $backupService->service_duration,
                'hospital_name' => $backupService->hospital_name,
                'state' => $backupService->state,
                'channel_partner' => $backupService->channel_partner,
                'patient_name' => $user->name,
                'patient_phone' => $user->phone_number,
                'patient_email' => $user->email,
                'accompanying_person_name' => $backupService->accompanying_person_name,
                'accompanying_person_phone' => $backupService->accompanying_person_phone,
                'purchased_date' => $backupService->created_at->format('l, d F, Y'), // Modified line
            ];

            // Add service engineer details if one has been assigned
            if ($backupService->serviceEngineer) {
                $responseData['service_engineer'] = [
                    'name' => $backupService->serviceEngineer->name,
                    'email' => $backupService->serviceEngineer->email,
                    'phone_number' => $backupService->serviceEngineer->phone_number ?? null
                ];
            }

            return response()->json([
                'message' => 'Backup service status retrieved successfully',
                'data' => $responseData,
                'has_registered' => $hasRegistered // Include the variable in the response
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving backup service status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Cancel the patient's backup service request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelBackupService(Request $request)
    {
        try {
            $user = $request->user();

            // Retrieve the latest backup service request for the authenticated user
            $backupService = BackupService::where('patient_id', $user->id)
                ->latest()
                ->first();

            if (!$backupService) {
                return response()->json([
                    'message' => 'Backup service request not found'
                ], 404);
            }

            // Check if the backup service is already cancelled
            if ($backupService->status === 'cancelled') {
                return response()->json([
                    'message' => 'Backup service request is already cancelled'
                ], 400);
            }

            // Update the status to cancelled
            $backupService->status = 'cancelled';
            $backupService->save();

            return response()->json([
                'message' => 'Backup service request cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error cancelling backup service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    

}