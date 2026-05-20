<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'telegram_alerts' => env('FEATURE_TELEGRAM_ALERTS', false),
        'deployments'     => env('FEATURE_DEPLOYMENTS', false),
        'multi_user'      => env('FEATURE_MULTI_USER', false),
        'mobile_api'      => env('FEATURE_MOBILE_API', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'api' => [
        'token_expiry_days' => env('API_TOKEN_EXPIRY_DAYS', 365),
        'rate_limit'        => env('API_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'alert_cooldown_minutes' => env('ALERT_COOLDOWN_MINUTES', 30),
        'channels'               => ['email', 'telegram'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployments
    |--------------------------------------------------------------------------
    */
    'deployments' => [
        'timeout_seconds'    => env('DEPLOYMENT_TIMEOUT', 300),
        'max_log_bytes'      => 1 * 1024 * 1024, // 1 MB
        'retain_days'        => env('DEPLOYMENT_RETAIN_DAYS', 30),
    ],

];
