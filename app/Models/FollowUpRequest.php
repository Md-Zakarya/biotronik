<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FollowUpRequest extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    protected $fillable = [
        'follow_up_id',
        'patient_id',
        'state',
        'hospital_name', 
        'doctor_name',
        'channel_partner',
        'accompanying_person_name',
        'accompanying_person_phone',
        // 'appointment_date',
        'appointment_datetime',
        // 'appointment_time',
        'reason',
        'status',
        'rejection_reason',
        'service_engineer_id',
        'completion_message',
        'payment_id' // Reference to payment record
    ];

    protected $casts = [
        // 'appointment_date' => 'date',
        // 'appointment_time' => 'datetime',
         'appointment_datetime' => 'datetime'

    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($request) {
            $request->follow_up_id = 'FU-' . Str::random(8);
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function serviceEngineer()
    {
        return $this->belongsTo(User::class, 'service_engineer_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}