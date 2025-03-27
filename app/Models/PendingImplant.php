<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingImplant extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'pre_feb_2022',
        'ipg_serial_number',
        'implantation_date',
        'ipg_model',
        'ipg_model_number',
        'hospital_state',
        'hospital_name',
        'doctor_name',
        'channel_partner',
        'therapy_name',
        'device_name',
        'has_ra_rv_lead',
        'ra_rv_leads',
        'has_extra_lead',
        'csp_lead_model',
        'csp_catheter_model',
        'csp_lead_serial',
        'patient_id_card',
        'warranty_card',
        'interrogation_report',
        'lead_brand',
        'status',
        'rejection_reason'
    ];

    protected $casts = [
        'pre_feb_2022' => 'boolean',
        'has_ra_rv_lead' => 'boolean',
        'has_extra_lead' => 'boolean',
        'ra_rv_leads' => 'array'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}