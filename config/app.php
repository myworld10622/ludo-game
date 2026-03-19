<?php

use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'GamesBackend'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'public_version' => env('APP_PUBLIC_VERSION', '1.0.0'),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\BackendServiceProvider::class,
    ])->toArray(),
];
