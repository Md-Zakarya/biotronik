<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSerial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'serial_number',
        'lead_model_number',
        'distributor_id',
        'is_assigned',
    ];

    /**
     * Get the lead model that owns the serial.
     */
    public function leadModel()
    {
        return $this->belongsTo(LeadModel::class, 'lead_model_number', 'model_number');
    }

    /**
     * Get the distributor that owns the serial.
     */
    public function distributor()
    {
        return $this->belongsTo(User::class, 'distributor_id');
    }
}