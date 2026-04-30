<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'steam' => [
        'client_id' => env('STEAM_CLIENT_ID', null),
        'client_secret' => env('STEAM_CLIENT_SECRET'),
        'redirect' => env('STEAM_REDIRECT_URL'),
        'allowed_hosts' => array_filter(array_map('trim', explode(',', (string) env('STEAM_ALLOWED_HOSTS', '')))),
        'admin_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'ADMIN_STEAM_IDS',
            (string) env('ADMIN_STEAM_ID', '')
        ))))),
    ],

    'pterodactyl' => [
        'panel_url' => env('PTERODACTYL_URL'),
        'client_api_key' => env('PTERODACTYL_CLIENT_API_KEY'),
        'application_api_key' => env('PTERODACTYL_APPLICATION_API_KEY'),
        'account_email' => env('PTERODACTYL_ACCOUNT_EMAIL'),
        'timeout' => env('PTERODACTYL_TIMEOUT', 5),
    ],

    'csgoserver' => [
        'lock_dir' => env('CSGOSERVER_LOCK_DIR', '/var/www/afterreload/csgoserver/lgsm/lock'),
        'port_map' => [
            27015 => 'csgoserver-started.lock',
            27016 => 'csgoserver-wingman-started.lock',
            27017 => 'mmserver-started.lock',
        ],
    ],

];
