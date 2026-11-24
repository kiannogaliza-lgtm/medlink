<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'user_id',
        'purpose',
        'phone_masked',
        'status',
        'provider_message_id',
        'error_code',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'related_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
