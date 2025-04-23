<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupService extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'patient_id',
        'state',
        'hospital_name',
        'channel_partner',
        'appointment_datetime',
        'service_type',
        'service_duration',
        'status',
        'payment_id',
        'service_engineer_id',
       'accompanying_person_name', // <--- Check this line
        'accompanying_person_phone'
        
    ];

    protected $casts = [
        'appointment_datetime' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);

    }

    public function serviceEngineer()
    {
        return $this->belongsTo(User::class, 'service_engineer_id');
    }
}
