<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient;
use App\Models\Implant;

class DeviceReplacement extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    protected $fillable = [
        'patient_id',
        'implant_id',
        'state',
        'hospital_name',
        'doctor_name',
        'channel_partner',
        'replacement_reason',
        'planned_replacement_date',
        'interrogation_report_path',
        'prescription_path',
        'service_charge',
        'status',
        'rejection_reason',
        'service_engineer_id',
        'service_completed',
         'is_warranty_claim',
         'new_ipg_serial_number'
        
    ];

    protected $casts = [
        'planned_replacement_date' => 'datetime',
        'service_charge' => 'decimal:2'
    ];


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function implant()
    {
        return $this->belongsTo(Implant::class);
    }
    public function serviceEngineer()
    {
        return $this->belongsTo(User::class, 'service_engineer_id');
    }
}