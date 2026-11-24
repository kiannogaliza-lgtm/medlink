<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'purpose',
        'code_hash',
        'expires_at',
        'attempts',
        'sent_at',
        'consumed_at',
        'invalidated_at',
        'invalidated_reason',
        'ip_address',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected $casts = [
        'attempts'       => 'integer',
        'expires_at'     => 'datetime',
        'sent_at'        => 'datetime',
        'consumed_at'    => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
