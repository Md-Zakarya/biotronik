<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Implant extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'implantation_date',
        'pre_feb_2022',
        'hospital_state',
        'hospital_name',
        'doctor_name',
        'channel_partner',
        'therapy_name',
        'ipg_model',
        'ipg_model_number',
        'ra_rv_lead_model',
        'has_ra_rv_lead',
        'has_extra_lead',
        'csp_lead_model',
        'device_name',
        'ipg_serial_number',
        'ra_rv_lead_serial',
        'csp_lead_serial',
        'patient_id_card',
        'warranty_card',
        'interrogation_report',
        'secret_key',
        'csp_catheter_model',
        'user_id',
        'is_service_engineer',
        'warranty_expired_at',
        
    ];

    protected $dates = [
        'implantation_date',
        'warranty_expired_at'  // Add this line
    ];

    protected $casts = [
        'pre_feb_2022' => 'boolean',
        'has_ra_rv_lead' => 'boolean',
        'has_extra_lead' => 'boolean',
        'is_service_engineer' => 'boolean'
        
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function ipgSerialHistories()
    {
        return $this->hasMany(IpgSerialHistory::class);
    }
}