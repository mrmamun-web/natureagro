<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmSetting extends Model
{
    protected $fillable = [
        'farm_name',
        'owner_name',
        'phone_number',
        'address',
        'description',
        'currency',
        'farm_logo',
    ];
}
