<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'old_implantation_date',
        'old_implant_brand',
        'old_ipg_model',
        'old_lead_brand',
        'old_ra_rv_lead_model',
        'old_csp_catheter_brand',
        'old_csp_lead_model',
        'state',
        'hospital_name',
        'doctor_name',
        'channel_partner',
        'service_engineer_id',
        'new_implantation_date',
        'new_ipg_serial_number',
        'new_ipg_model',
        'new_ipg_model_number',
        'new_therapy_name',
        'new_device_name',
        'new_ra_rv_leads',
        'new_csp_catheter_model',
        'new_csp_lead_model',
        'new_csp_lead_serial',
        'status',
    ];

    protected $casts = [
        'new_ra_rv_leads' => 'array',
        'old_implantation_date' => 'date',
        'new_implantation_date' => 'date',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function serviceEngineer()
    {
        return $this->belongsTo(User::class, 'service_engineer_id');
    }
}