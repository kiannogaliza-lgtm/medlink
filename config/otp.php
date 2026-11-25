<?php

return [
    'expiry_minutes' => [
        'registration'   => 30,
        'login'          => 5,
        'password_reset' => 10,
        'phone_change'   => 10,
    ],

    'max_attempts' => 5,

    'lockout_minutes' => 30,

    // Registration: invalidate only, no lockout
    'lockout_purposes' => ['login', 'password_reset', 'phone_change'],

    'resend_cooldown_seconds' => 60,

    'max_sends_per_hour' => 15,

    'log_fallback' => (bool) env('OTP_LOG_FALLBACK', false),
];
