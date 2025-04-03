<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_number',
        'model_name',
        'device_type',
    ];

    /**
     * Get the serials for the lead model.
     */
    public function serials()
    {
        return $this->hasMany(LeadSerial::class, 'lead_model_number', 'model_number');
    }
    
    /**
     * Get the number of available (unassigned) leads for this model
     */
   
    
    /**
     * Get the total number of leads for this model
     */
    public function getTotalCountAttribute()
    {
        return $this->serials()->count();
    }
    
}