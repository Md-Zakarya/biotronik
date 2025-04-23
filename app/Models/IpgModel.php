<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpgModel extends Model
{
    use HasFactory;

    protected $primaryKey = 'model_number';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'model_number',
        'model_name',
        'device_type',
        'cardiomessenger_enable',
        'warranty',
        'mr_enabled',
    ];

    protected $casts = [
        'cardiomessenger_enable' => 'boolean',
        'mr_enabled' => 'boolean',
        'warranty' => 'integer'
    ];

    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class, 'device_type', 'device_name');
    }

    public function serials()
    {
        return $this->hasMany(IpgSerial::class, 'model_number', 'model_number');
    }
}