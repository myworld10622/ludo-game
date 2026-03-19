<?php

return [
    'api' => [
        'prefix' => env('API_PREFIX', 'api'),
        'default_version' => env('API_DEFAULT_VERSION', 'v1'),
        'admin_prefix' => env('ADMIN_API_PREFIX', 'admin/api'),
    ],
    'node' => [
        'server_url' => env('NODE_SERVER_URL', 'http://127.0.0.1:3000'),
        'timeout' => (int) env('NODE_SERVER_TIMEOUT', 5),
    ],
    'internal' => [
        'api_token' => env('INTERNAL_API_TOKEN', ''),
    ],
    'unity' => [
        'min_supported_version' => env('UNITY_MIN_SUPPORTED_VERSION', '1.0.0'),
        'current_version' => env('UNITY_CURRENT_VERSION', '1.0.0'),
        'force_update' => (bool) env('UNITY_FORCE_UPDATE', false),
    ],
    'games' => [
        'seed_ludo_only_visible' => (bool) env('GAME_SEED_LUDO_ONLY_VISIBLE', true),
    ],
    'ludo' => [
        'socket_namespace' => env('LUDO_SOCKET_NAMESPACE', '/ludo'),
        'default_game_mode' => env('LUDO_DEFAULT_GAME_MODE', 'classic'),
        'allow_bots_in_public_rooms' => (bool) env('LUDO_ALLOW_BOTS_IN_PUBLIC_ROOMS', true),
        'allow_bots_in_tournaments' => (bool) env('LUDO_ALLOW_BOTS_IN_TOURNAMENTS', false),
        'bot_fill_after_seconds' => (int) env('LUDO_BOT_FILL_AFTER_SECONDS', 8),
        'min_real_players_to_start' => (int) env('LUDO_MIN_REAL_PLAYERS_TO_START', 1),
        'default_max_players' => (int) env('LUDO_DEFAULT_MAX_PLAYERS', 4),
    ],
    'features' => [
        'tournaments_enabled' => (bool) env('FEATURE_TOURNAMENTS_ENABLED', true),
        'external_identity_sync_enabled' => false,
    ],
    'maintenance' => [
        'api_enabled' => (bool) env('MAINTENANCE_API_ENABLED', false),
        'gameplay_enabled' => (bool) env('MAINTENANCE_GAMEPLAY_ENABLED', false),
    ],
    'auth' => [
        'api_guard' => env('AUTH_GUARD_API', 'sanctum'),
        'admin_guard' => env('AUTH_GUARD_ADMIN', 'sanctum'),
    ],
];
