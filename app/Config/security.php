<?php

declare(strict_types=1);

// Session/cookie hardening per docs/05-security.md §3. The CSP itself is
// built per-request (it needs a fresh nonce) by SecurityHeadersMiddleware,
// added when the middleware pipeline is built — not a static config value.
return [
    'session' => [
        'name'            => env('SESSION_NAME', 'IIUI_SESS'),
        'lifetime'        => (int) env('SESSION_LIFETIME', 7200),       // absolute cap, seconds
        'idle_timeout'    => (int) env('SESSION_IDLE_TIMEOUT', 1800),   // 30 minutes
        'cookie_secure'   => filter_var(env('SESSION_COOKIE_SECURE', false), FILTER_VALIDATE_BOOLEAN),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],

    'headers' => [
        'x_content_type_options' => 'nosniff',
        'x_frame_options'        => 'DENY',
        'referrer_policy'        => 'strict-origin-when-cross-origin',
        'permissions_policy'     => 'geolocation=(), microphone=(), camera=(), payment=()',
    ],

    // 'login' / 'login_ip' back the two DISTINCT brute-force controls in
    // docs/05-security.md §2 (per-account lockout vs per-IP throttle) and are
    // read directly by AuthService against the login_attempts table — they
    // are not routed through the generic ThrottleMiddleware. Every other key
    // here IS a ThrottleMiddleware bucket (app/Config/middleware.php 'throttle').
    'throttle' => [
        'login'           => ['max_attempts' => 5,  'decay_minutes' => 15], // per account
        'login_ip'        => ['max_attempts' => 10, 'decay_minutes' => 60], // per IP, any account
        'password_reset'  => ['max_attempts' => 3,  'decay_minutes' => 60], // per email
        'admission_apply' => ['max_attempts' => 3,  'decay_minutes' => 60],
        'contact'         => ['max_attempts' => 5,  'decay_minutes' => 60],
    ],
];
