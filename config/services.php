<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'betzono' => [
        'deposit_url'      => env('BETZONO_DEPOSIT_URL'),
        'withdraw_url'     => env('BETZONO_WITHDRAW_URL'),
        'merchant_id'      => env('BETZONO_MERCHANT_ID'),
        'proxy_user_id'    => env('BETZONO_PROXY_USER_ID'),
        'proxy_email'      => env('BETZONO_PROXY_EMAIL'),
        'proxy_phone'      => env('BETZONO_PROXY_PHONE'),
        'callback_base_url' => env('BETZONO_CALLBACK_URL_BASE'),
        'debug_fallback_url' => env('BETZONO_DEBUG_FALLBACK_URL'),
    ],

];
