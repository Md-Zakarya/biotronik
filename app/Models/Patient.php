<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'patient_photo',
        'name',
        'date_of_birth',
        'gender',
        'address',
        'state',
        'city',
        'pin_code',
        'relative_name',
        'relative_relation',
        'relative_gender',
        'relative_address',
        'relative_state',
        'relative_city',
        'relative_pin_code',
        'relative_email',
        'relative_phone',
        'user_id',


        //new Auth credentials Added
        'Auth_name',
        'email',
        'password',
        'is_service_engineer',
        'phone_number',





    ];

    protected $dates = ['date_of_birth'];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function implant()
    {
        return $this->hasOne(Implant::class);
    }
    public function ipgDevices()
    {
        return $this->hasMany(IpgDevice::class);
    }
}