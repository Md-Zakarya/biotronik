<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminIdRequestController extends Controller
{   





    /**
     * Get a list of all ID requests with pagination, showing specific fields.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status'); // No default needed, handled by the query

            // Select specific columns from IdRequest and the related patient
            $query = IdRequest::select([
                'id', // Keep ID for reference
                'patient_id', // Needed for the relationship
                'city',
                'state',
                'status', // Assuming 'status' is the delivery status field
                'shipping_partner', // Assuming 'shipping_partner' is the field name
                'delivery_address', // Add delivery address
                'phone_number',     // Add phone number
                'pin_code',
                'tracking_id'           // Add pincode
            ])->with([
                        'patient' => function ($query) {
                            // Select only the name from the patient table
                            $query->select('id', 'name');
                        }
                    ])->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($status) {
                $query->where('status', $status);
            }

            $idRequests = $query->paginate($perPage);

            // Optionally, transform the data for a cleaner API response
            $transformedData = $idRequests->through(function ($request) {
                return [
                    'id' => $request->id,
                    'patient_name' => $request->patient->name ?? null, // Handle potential null patient
                    'city' => $request->city,
                    'state' => $request->state,
                    'delivery_status' => $request->status,
                    'shipping_partner' => $request->shipping_partner,
                    'delivery_address' => $request->delivery_address, // Include delivery address
                    'phone_number' => $request->phone_number,         // Include phone number
                    'pincode' => $request->pin_code, 
                    'tracking_id' => $request->tracking_id                       // Include pincode
                ];
            });


            return response()->json([
                'message' => 'ID requests retrieved successfully',
                // Return the paginated data structure with transformed items
                'data' => [
                    'items' => $transformedData->items(),
                    'current_page' => $idRequests->currentPage(),
                    'last_page' => $idRequests->lastPage(),
                    'per_page' => $idRequests->perPage(),
                    'total' => $idRequests->total(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving ID requests: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error retrieving ID requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the status of an ID request
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'string', Rule::in(['printing', 'delivery_partner_picked', 'in_transit', 'delivered'])],
                'shipping_partner' => ['required_if:status,delivery_partner_picked', 'nullable', 'string', 'max:255'],
                'tracking_id' => ['required_if:status,delivery_partner_picked', 'nullable', 'string', 'max:255'],
            ]);

            $idRequest = IdRequest::with('patient')->findOrFail($id); // Eager load patient

            // Update status
            $idRequest->status = $validated['status'];

            // If status is 'delivery_partner_picked', update shipping info
            if ($validated['status'] === 'delivery_partner_picked') {
                $idRequest->shipping_partner = $validated['shipping_partner'];
                $idRequest->tracking_id = $validated['tracking_id'];
            }

            $idRequest->save(); // This will trigger the model's boot method to update timestamps

            // Prepare response data
            $responseData = [
                'id' => $idRequest->id,
                'request_id' => $idRequest->request_id, // Assuming request_id exists
                'patient_name' => $idRequest->patient->name ?? null, // Use eager loaded patient name
                'phone_number' => $idRequest->phone_number, // Assuming phone_number exists
                'status' => $idRequest->status,
                'shipping_partner' => $idRequest->shipping_partner,
                'tracking_id' => $idRequest->tracking_id,
                'updated_at' => $idRequest->updated_at
            ];

            return response()->json([
                'message' => 'ID request status updated successfully',
                'data' => $responseData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating ID request status: ', $e->errors());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating ID request status: ' . $e->getMessage(), [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error updating ID request status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
















    ////demo ROUTES!
    public function showPublicIdRequests()
    {
        // Fetch the ID requests - adjust query as needed
        $requests = IdRequest::orderBy('created_at', 'desc')->get();

        // Return the view, passing the data
        // Use dot notation for the view path relative to resources/views
        return view('public.id-requests.index', compact('requests'));
    }

    public function updatePublicIdRequestStatus(Request $request, $id)
    {
        // Find the request
        $idRequest = IdRequest::findOrFail($id);
    
        // Validate the incoming status - add more specific validation if needed
        $validated = $request->validate([
            'status' => 'required|string|in:printing,delivery_partner_picked,in_transit,delivered',
            'shipping_partner' => 'required_if:status,delivery_partner_picked|nullable|string|max:255', 
            'tracking_id' => 'required_if:status,delivery_partner_picked|nullable|string|max:255',
        ]);
    
        // Update the status
        $idRequest->status = $validated['status'];
        
        // Update shipping info if status is "picked"
        if ($validated['status'] === 'delivery_partner_picked' && $request->has('shipping_partner') && $request->has('tracking_id')) {
            $idRequest->shipping_partner = $validated['shipping_partner'];
            $idRequest->tracking_id = $validated['tracking_id'];
        }
        
        // Save changes
        $idRequest->save();
    
        // Return a JSON response for the fetch request
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully!',
            'data' => $idRequest // Return updated data
        ]);
    }


}
