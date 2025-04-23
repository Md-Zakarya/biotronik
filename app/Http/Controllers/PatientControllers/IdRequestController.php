<?php

namespace App\Http\Controllers\PatientControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment; // Import Payment model
use App\Models\IdRequest; // Import IdRequest model
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService; // Import PaymentService

class IdRequestController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Submit a new ID request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitRequest(Request $request)
    {
        DB::beginTransaction();

        try {
            /** @var \App\Models\Patient $patient */
            $patient = $request->user();

            // Validate common request data
            $validator = Validator::make($request->all(), [
                'patient_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'delivery_address' => 'required|string|max:255',
                'state' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'pin_code' => 'required|string|size:6',
                'gst_number' => 'nullable|string|max:15',
                'pan_number' => 'nullable|string|max:10',
                'payment_details' => 'required|array',
                'payment_details.amount' => 'required|numeric|min:1',
                'payment_details.method' => 'required|string|in:card,upi',
                'payment_details.transaction_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            if ($validated['payment_details']['method'] === 'card') {
                $paymentMethodValidator = Validator::make($request->all(), [
                    'payment_details.card_number' => 'required|string|min:13|max:19',
                    'payment_details.expiry_date' => 'required|string',
                    'payment_details.cvc' => 'required|string|size:3',
                    'payment_details.country' => 'required|string|max:100',
                    'payment_details.postal_code' => 'required|string|max:20',
                ]);

                if ($paymentMethodValidator->fails()) {
                    return response()->json([
                        'message' => 'Payment Details Validation Error',
                        'errors' => $paymentMethodValidator->errors()
                    ], 422);
                }

                // Merge additional card details into payment_details
                $validated['payment_details'] = array_merge(
                    $validated['payment_details'],
                    $paymentMethodValidator->validated()
                );
            } elseif ($validated['payment_details']['method'] === 'upi') {
                $paymentMethodValidator = Validator::make($request->all(), [
                    'payment_details.upi_id' => 'required|string',
                    'payment_details.upi_phone_number' => 'required|string|max:20',
                ]);

                if ($paymentMethodValidator->fails()) {
                    return response()->json([
                        'message' => 'Payment Details Validation Error',
                        'errors' => $paymentMethodValidator->errors()
                    ], 422);
                }

                // Merge additional UPI details into payment_details
                $validated['payment_details'] = array_merge(
                    $validated['payment_details'],
                    $paymentMethodValidator->validated()
                );
            }

            // Create payment record
            $paymentData = [
                'gst_number' => $validated['gst_number'] ?? null,
                'pan_number' => $validated['pan_number'] ?? null,
                'amount' => $validated['payment_details']['amount'],
                'payment_status' => 'completed',
                'payment_type' => 'id_request',
                'payment_details' => $validated['payment_details'], // Now includes additional details
            ];

            $payment = $this->paymentService->createPayment($paymentData, $patient->id);

            if (isset($paymentMethodValidator) && $paymentMethodValidator->fails()) {
                return response()->json([
                    'message' => 'Payment Details Validation Error',
                    'errors' => $paymentMethodValidator->errors()
                ], 422);
            }

            // Create payment record
            $paymentData = [
                'gst_number' => $validated['gst_number'] ?? null,
                'pan_number' => $validated['pan_number'] ?? null,
                'amount' => $validated['payment_details']['amount'],
                'payment_status' => 'completed',
                'payment_type' => 'id_request',
                'payment_details' => $validated['payment_details']
            ];

            $payment = $this->paymentService->createPayment($paymentData, $patient->id);

            // Create ID request
            $idRequest = IdRequest::create([
                'patient_id' => $patient->id,
                'patient_name' => $validated['patient_name'],
                'phone_number' => $validated['phone_number'],
                'delivery_address' => $validated['delivery_address'],
                'state' => $validated['state'],
                'city' => $validated['city'],
                'pin_code' => $validated['pin_code'],
                'payment_id' => $payment->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'ID request submitted successfully',
                'data' => [
                    'request_id' => $idRequest->id,
                    'patient_name' => $idRequest->patient_name,
                    'phone_number' => $idRequest->phone_number,
                    'status' => $idRequest->status,
                    'created_at' => $idRequest->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting ID request: ' . $e->getMessage(), [
                'patient_id' => $patient->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error processing ID request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Track an ID request status
     */
    public function trackRequest(Request $request)
    {
        try {
            /** @var \App\Models\Patient $patient */
            $patient = $request->user();

            // Always get the latest ID request for this patient, eager load payment
            $idRequest = IdRequest::with('payment') // Eager load payment relationship
                ->where('patient_id', $patient->id)
                ->latest()
                ->first();

            // Check if an existing request exists
            $existingRequestExists = $idRequest !== null;

            if (!$idRequest) {
                return response()->json([
                    'message' => 'ID request not found',
                    'existing_request' => $existingRequestExists
                ], 404);
            }

            $statusTimestamp = $this->getStatusTimestamp($idRequest);
            $displayStatus = $this->getDisplayStatus($idRequest->status); // Get user-friendly status

            // Calculate the estimated delivery date (1 month from the order date)
            $orderedDate = $idRequest->created_at;
            $estimatedDeliveryDate = $orderedDate->copy()->addMonth();

            return response()->json([
                'message' => 'ID request status retrieved',
                'data' => [
                    'id' => $idRequest->id,
                    'request_id' => $idRequest->request_id,
                    'patient_name' => $idRequest->patient_name,
                    'phone_number' => $idRequest->phone_number,
                    'status' => $displayStatus, // Use the user-friendly status
                    'status_changed_at' => $statusTimestamp instanceof \Carbon\Carbon ? $statusTimestamp->toDateTimeString() : null, 
                    'tracking_id' => $idRequest->tracking_id,
                    'shipping_partner' => $idRequest->shipping_partner,
                    'delivery_address' => $idRequest->delivery_address,
                    'city' => $idRequest->city,
                    'state' => $idRequest->state,
                    'pin_code' => $idRequest->pin_code,
                    // Ensure payment relationship is loaded before accessing it
                    'payment_details' => $idRequest->payment ? $idRequest->payment->payment_details : null,
                    'ordered_date' => $orderedDate->toDateString(), // Day it was ordered
                    'estimated_delivery_date' => $estimatedDeliveryDate->toDateString(), // Estimated delivery date
                    'existing_request' => $existingRequestExists // Indicates if an existing request exists
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error tracking ID request: ' . $e->getMessage(), [
                'patient_id' => optional($request->user())->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error tracking ID request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the status timestamp based on the current status of the ID request.
     *
     * @param  \App\Models\IdRequest  $idRequest
     * @return string|null
     */
    private function getStatusTimestamp(IdRequest $idRequest): ?string
    {
        switch ($idRequest->status) {
            case 'printing':
                return $idRequest->printing_at;
            case 'delivery_partner_picked':
                return $idRequest->delivery_partner_picked_at;
            case 'in_transit':
                return $idRequest->in_transit_at;
            case 'delivered':
                return $idRequest->delivered_at;
            default:
                return null;
        }
    }


      /**
     * Get the user-friendly display status string based on the internal status.
     *
     * @param string|null $status
     * @return string
     */
    private function getDisplayStatus(?string $status): string
    {
        switch ($status) {
            case 'printing':
                return 'Order Confirmed';
            case 'delivery_partner_picked':
                return 'Shipped';
            case 'in_transit':
                return 'In Transit';
            case 'delivered':
                return 'Delivered';
            default:
                return 'Processing'; // Default status if none of the above match
        }
    }
}