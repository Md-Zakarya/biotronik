<?php

namespace App\Http\Controllers\PatientControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Payment;


class WarrantyExtensionController extends Controller
{

    /**
     * Extend warranty for a patient's implant
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function extendWarranty(Request $request)
    {
        try {
            $user = $request->user();
            $implant = $user->implant;

            if (!$implant) {
                return response()->json(['message' => 'No implant found for this user'], 404);
            }

            $validated = $request->validate([
                'warranty_type' => 'required|in:15,25,lifetime',
                'gst_number' => 'nullable|string|min:15|max:15',
                'pan_number' => 'nullable|string|min:10|max:10',
                'payment_method' => 'required|in:upi,card',
                'amount' => 'required|numeric',

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

            // Calculate new warranty expiration date based on warranty_type
            $currentWarrantyDate = Carbon::parse($implant->warranty_expired_at);
            $newWarrantyDate = null;

            if ($validated['warranty_type'] === 'lifetime') {
                $newWarrantyDate = Carbon::parse($currentWarrantyDate)->addYears(99);
            } else {
                $years = (int) $validated['warranty_type'];
                $newWarrantyDate = now()->addYears($years);
            }

            // Store payment details in a structured format
            $paymentDetails = [
                'method' => $validated['payment_method']
            ];

            if ($validated['payment_method'] === 'upi') {
                $paymentDetails['upi_id'] = $validated['upi_id'];
                $paymentDetails['upi_phone'] = $validated['upi_phone'];
            } else {
                $paymentDetails['card_number'] = $validated['card_number'];
                $paymentDetails['card_expiry'] = $validated['card_expiry'];
                $paymentDetails['card_cvc'] = $validated['card_cvc'];
                $paymentDetails['card_country'] = $validated['card_country'];
                $paymentDetails['card_postal_code'] = $validated['card_postal_code'];
            }

            // Use transaction to ensure both records are updated
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'patient_id' => $user->id,
                'gst_number' => $validated['gst_number'],
                'pan_number' => $validated['pan_number'],
                'amount' => $validated['amount'],
                'payment_status' => 'completed',
                'payment_date' => now(),
                'payment_type' => 'warranty_extension',
                'payment_details' => json_encode([
                    'payment_method' => $paymentDetails,
                    'warranty_type' => $validated['warranty_type'],
                    'previous_expiry_date' => $currentWarrantyDate,
                    'new_expiry_date' => $newWarrantyDate,
                    'implant_id' => $implant->id
                ])
            ]);

            // Update implant warranty date
            $implant->warranty_expired_at = $newWarrantyDate;
            $implant->save();

            DB::commit();

            return response()->json([
                'message' => 'Warranty extended successfully',
                'data' => [
                    'warranty_type' => $validated['warranty_type'],
                    'previous_expiry_date' => $currentWarrantyDate->format('Y-m-d'), // Format the Carbon object
                    'new_expiry_date' => $newWarrantyDate->format('Y-m-d'), // Format the Carbon object
                    'payment_id' => $payment->id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error extending warranty',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get warranty details for the patient's active implant
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarrantyDetails(Request $request)
    {
        try {
            $user = $request->user();

            // Get the active implant
            $implant = $user->implant()->where('active', true)->first();

            if (!$implant) {
                return response()->json(['message' => 'No active implant found for this user'], 404);
            }

            // Get warranty extension payments for this implant
            $warrantyExtensions = Payment::where('patient_id', $user->id)
                ->where('payment_type', 'warranty_extension')
                ->orderBy('payment_date', 'desc')
                ->get();

            $hasExtensions = $warrantyExtensions->count() > 0;

            // Calculate days remaining in warranty
            $warrantyEndDate = Carbon::parse($implant->warranty_expired_at);
            $daysRemaining = now()->diffInDays($warrantyEndDate, false);

            // Check if this IPG model is eligible for lifetime warranty
            $ipgModel = \App\Models\IpgModel::where('model_number', $implant->ipg_model_number)->first();
            $isEligibleForLifetime = false;

            // Check lifetime warranty eligibility from the IPG model
            if ($ipgModel) {
                $isEligibleForLifetime = $ipgModel->lifetime_warranty ?? false;
            }


            return response()->json([
                'message' => 'Warranty details retrieved successfully',
                'data' => [
                    'implant' => [
                        'ipg_serial_number' => $implant->ipg_serial_number,
                        'ipg_model' => $implant->ipg_model,
                        'implantation_date' => $implant->implantation_date,
                    ],
                    'warranty' => [
                        'has_extensions' => $hasExtensions,
                        'expiry_date' => $implant->warranty_expired_at,
                        'days_remaining' => $daysRemaining,
                        'is_active' => $daysRemaining > 0,
                        'eligible_for_lifetime_warranty' => (bool) $isEligibleForLifetime,

                    ],
                    'extensions' => $warrantyExtensions->map(function ($extension) {
                        $details = json_decode($extension->payment_details, true);
                        return [
                            'payment_id' => $extension->id,
                            'payment_date' => $extension->payment_date,
                            'amount' => $extension->amount,
                            'warranty_type' => $details['warranty_type'] ?? null,
                            'previous_expiry_date' => $details['previous_expiry_date'] ?? null,
                            'new_expiry_date' => $details['new_expiry_date'] ?? null,
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving warranty details',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}