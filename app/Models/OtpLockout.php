<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpLockout extends Model
{
    protected $fillable = [
        'phone',
        'purpose',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];
}
