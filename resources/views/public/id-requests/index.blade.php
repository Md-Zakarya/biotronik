{{-- filepath: c:\Users\zakki\OneDrive\Desktop\E-spandan\backend\resources\views\public\id-requests\index.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ID Requests Management</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .request-table th, 
        .request-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .request-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .status-select {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
        }
        .update-btn {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ID Requests Management</h1>
        
        <div id="alert" class="alert"></div>

        <table class="request-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Patient Name</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Shipping Partner</th>
                    <th>Tracking ID</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td>{{ $request->id }}</td>
                        <td>{{ $request->patient_name }}</td>
                        <td>{{ $request->city }}, {{ $request->state }}</td>
                        <td class="status-cell-{{ $request->id }}">{{ ucfirst($request->status) }}</td>
                        <td>{{ $request->shipping_partner ?? 'N/A' }}</td>
                        <td>{{ $request->tracking_id ?? 'N/A' }}</td>
                        <td>{{ $request->created_at ? date('Y-m-d H:i', strtotime($request->created_at)) : 'N/A' }}</td>
                        <td>
                            <select class="status-select" id="status-{{ $request->id }}">
                                <option value="printing" {{ $request->status == 'printing' ? 'selected' : '' }}>Printing</option>
                                <option value="delivery_partner_picked" {{ $request->status == 'delivery_partner_picked' ? 'selected' : '' }}>Picked by Partner</option>
                                <option value="in_transit" {{ $request->status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="delivered" {{ $request->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            </select>
                            <button class="update-btn" onclick="updateStatus({{ $request->id }})">Update</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center;">No ID requests found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    
        function updateStatus(requestId) {
            const status = document.getElementById(`status-${requestId}`).value;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            let requestData = { status };
            
            // If status is "picked", prompt for additional information
            if (status === 'delivery_partner_picked') {
                const shippingPartner = prompt("Enter shipping partner name:");
                if (!shippingPartner) {
                    showAlert('Shipping partner is required for "Picked by Partner" status', 'error');
                    return;
                }
                
                const trackingId = prompt("Enter tracking ID:");
                if (!trackingId) {
                    showAlert('Tracking ID is required for "Picked by Partner" status', 'error');
                    return;
                }
                
                requestData.shipping_partner = shippingPartner;
                requestData.tracking_id = trackingId;
            }
    
            fetch(`/admin/id-requests-demo/${requestId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                // Check if response contains success property or assume success if message exists
                if (data.success || data.message) {
                    showAlert('Status updated successfully!', 'success');
                    document.querySelector(`.status-cell-${requestId}`).textContent = 
                        status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
                        
                    // Also update shipping partner and tracking ID in the table if they were provided
                    if (status === 'delivery_partner_picked' && data.data) {
                        const row = document.getElementById(`status-${requestId}`).closest('tr');
                        const shippingPartnerCell = row.cells[4]; // Adjust index based on your table structure
                        const trackingIdCell = row.cells[5]; // Adjust index based on your table structure
                        
                        shippingPartnerCell.textContent = data.data.shipping_partner || requestData.shipping_partner;
                        trackingIdCell.textContent = data.data.tracking_id || requestData.tracking_id;
                    }
                } else {
                    showAlert(data.message || 'Error updating status', 'error');
                }
            })
            .catch(error => {
                showAlert('Error updating status', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>