<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpgDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipg_serial_number',
        'ipg_model_name',
        'ipg_model_number',
        'is_linked',
        'patient_id',
        'implant_id'
    ];

    protected $casts = [
        'is_linked' => 'boolean',
    ];

    /**
     * Get the patient associated with the IPG device
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the implant associated with the IPG device
     */
    public function implant()
    {
        return $this->belongsTo(Implant::class);
    }
}