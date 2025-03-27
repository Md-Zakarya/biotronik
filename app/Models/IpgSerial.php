<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpgSerial extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'ipg_serial_number',
        'model_number',
        'distributor_id'
    ];

    public function ipgModel()
    {
        return $this->belongsTo(IpgModel::class, 'model_number', 'model_number');
    }
    
    public function ipgDevice()
    {
        return $this->hasOne(IpgDevice::class, 'ipg_serial_number', 'ipg_serial_number');
    }
    public function distributor()
{
    return $this->belongsTo(\App\Models\User::class, 'distributor_id');
}
}