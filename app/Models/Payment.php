<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'patient_id',
        'gst_number',
        'pan_number',
        'amount',
        'payment_status',
        'payment_date',
        'payment_type', // 'follow_up', 'replacement', etc.
        'payment_details',
        'service_engineer_id'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'payment_details' => 'array'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function followUpRequests()
    {
        return $this->hasMany(FollowUpRequest::class);
    }
}