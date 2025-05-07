<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class IdRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'patient_id',
        'patient_name',
        'phone_number',
        'delivery_address',
        'state',
        'city',
        'pin_code',
        'payment_id',
        'shipping_partner',
        'tracking_id',
        'status',
        'printing_at',
        'delivery_partner_picked_at',
        'in_transit_at',
        'delivered_at'
    ];

    /**
     * Boot method to set up model event hooks
     */
    protected static function boot()
    {
        parent::boot();

        // Generate a unique request ID when creating a new record
        static::creating(function ($idRequest) {
            // Format: IDR-XXXXXXXX (where X is random alphanumeric)
            $idRequest->request_id = 'IDR-' . strtoupper(Str::random(8));
            
            // Set the initial status timestamp
            $idRequest->printing_at = now();
        });
        
        // Update status timestamps when status changes
        static::updating(function ($idRequest) {
            if ($idRequest->isDirty('status')) {
                $status = $idRequest->status;
                $timestampField = "{$status}_at";
                
                if (in_array($timestampField, [
                    'printing_at',
                    'delivery_partner_picked_at',
                    'in_transit_at',
                    'delivered_at'
                ])) {
                    $idRequest->$timestampField = now();
                }
            }
        });
    }

    /**
     * Get the patient associated with the ID request
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the payment associated with the ID request
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    
    /**
     * Scope to find a request by its unique request_id
     */
    public function scopeFindByRequestId($query, $requestId)
    {
        return $query->where('request_id', $requestId);
    }
}