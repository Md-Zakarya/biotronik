<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplantRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo',
        'name',
        'dob',
        'gender',
        'address',
        'state',
        'city',
        'pin',
    ];
}