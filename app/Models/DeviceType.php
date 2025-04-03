<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    use HasFactory;

    protected $primaryKey = 'device_id';
    
    protected $fillable = [
        'device_name',
        'therapy_id'
    ];

    public function ipgModels()
    {
        return $this->hasMany(IpgModel::class, 'device_type', 'device_name');
    }
}