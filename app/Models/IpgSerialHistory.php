<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpgSerialHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'implant_id',
        'old_ipg_serial_number',
        'new_ipg_serial_number',
    ];

    public function implant()
    {
        return $this->belongsTo(Implant::class);
    }
}