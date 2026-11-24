<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'author_name',
        'author_role',
        'title',
        'content',
        'date',
        'status',
        'sms_sent_at',
        'sms_recipient_count',
    ];

    protected $casts = [
        'date' => 'date',
        'sms_sent_at' => 'datetime',
        'sms_recipient_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
