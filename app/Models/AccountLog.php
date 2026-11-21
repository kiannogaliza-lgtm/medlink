<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'phone_number',
        'action',
        'actor_name',
        'actor_role',
        'details',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log an account event easily anywhere in the application.
     */
    public static function record(
        User $user,
        string $action,
        ?string $details = null,
        ?string $actorName = null,
        ?string $actorRole = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): self {
        $actorName = $actorName ?: (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Self');
        $actorRole = $actorRole ?: (auth()->check() ? auth()->user()->role : ($user->role ?? 'system'));

        return self::create([
            'user_id'      => $user->id,
            'user_name'    => trim($user->first_name . ' ' . $user->last_name),
            'user_role'    => $user->role,
            'phone_number' => $user->phone_number,
            'action'       => $action,
            'actor_name'   => $actorName,
            'actor_role'   => $actorRole,
            'details'      => $details,
            'ip_address'   => $ip ?: request()->ip(),
            'user_agent'   => $userAgent ?: substr(request()->userAgent() ?? '', 0, 255),
            'logged_at'    => now(),
        ]);
    }
}
