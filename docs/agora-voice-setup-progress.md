# Agora Voice Setup Progress

Last updated: 2026-05-07

## Current status

Completed:

1. Agora `App ID` and `App Certificate` were added in `.env`.
2. Backend protected API route was created:
   - `GET /api/v1/agora/token`
3. Route is behind existing `api.auth` middleware.
4. Room/private-table membership validation was added before issuing token.
5. `cyberdeep/laravel-agora-token-generator` package was installed.
6. Package config was published to:
   - `config/laravel-agora-token-generator.php`
7. Backend token service was switched to use the installed package.
8. Old custom local Agora token builder files were removed to avoid duplicate logic.
9. Agora Unity SDK package was imported into the Unity project.
10. Unity room voice integration was started:
   - `Assets/_Project/Games/LudoClassic/ScriptOffline/Voice/AgoraVoiceManager.cs`
11. Ludo matchmaking bridge now builds Agora channel names and auto-joins voice on room open.
12. Old in-room text chat is being replaced by voice panel logic.

## Installed package

Command used:

```bash
composer require cyberdeep/laravel-agora-token-generator:^1.0
```

Published config:

```bash
php artisan vendor:publish --tag=laravel-agora-token-generator-config
```

## Important note

During Composer update, Laravel framework and multiple Symfony/Laravel dependencies were also updated in `composer.lock` and tracked `vendor/`.

This means the worktree now includes:

1. Agora package install
2. Framework dependency updates
3. Existing custom Agora endpoint files

Before production deploy, do not pretend this is a package-only diff.

## Env values required

These backend env values are now expected:

```env
AGORA_APP_ID=your_app_id
AGORA_APP_CERTIFICATE=your_app_certificate
AGORA_TOKEN_BUILDER=v2
AGORA_DEFAULT_ROLE=publisher
AGORA_TOKEN_EXPIRE_SECONDS=3600
```

`AGORA_TOKEN_BUILDER=v2` is important because V2 tokens start with `007`.

## Backend API behavior

Request:

```http
GET /api/v1/agora/token?channel=ludo_room_ROOMUUID&uid=USER_ID
Authorization: Bearer USER_ACCESS_TOKEN
```

Response shape:

```json
{
  "success": true,
  "message": "Agora token generated successfully.",
  "data": {
    "appId": "xxx",
    "token": "xxx",
    "channel": "ludo_room_ROOMUUID",
    "uid": 123,
    "expiresIn": 3600
  },
  "errors": null
}
```

## Validation already enforced

1. `uid` must match authenticated user.
2. Channel name must be valid for Agora.
3. `ludo_room_*` and `ludo_tournament_*` require user membership in `game_rooms`.
4. `ludo_private_*` requires user membership in the private table.
5. Unsupported channel prefixes are rejected.

## What still remains

1. Verify Unity compilation after Agora import and API updater changes.
2. Test voice join on 2 devices in same room.
3. Confirm the replacement UI positioning looks correct on the actual gameplay canvas.
4. Validate Android runtime microphone permission on device.
5. Test reconnect flow after internet drop.
6. Test different-room voice isolation.

## Unity progress

Completed in Unity:

1. Agora SDK import was triggered and project files are now under:
   - `unity/Assets/Agora-RTC-Plugin`
2. New runtime voice manager was added:
   - `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Voice/AgoraVoiceManager.cs`
3. New config URL was added:
   - `Configuration.AgoraTokenUrl`
4. `LudoV2MatchmakingBridge` now exposes `GetAgoraVoiceChannelName()`.
5. Room open now requests voice join instead of enabling text chat.
6. Room close/reset/exit now leaves Agora voice too.

Current Unity behavior target:

1. Player enters waiting/playing room.
2. Unity requests backend token from Laravel.
3. Unity joins Agora voice channel matching room type.
4. Old chat button opens voice panel.
5. Voice panel supports:
   - mic on/off
   - speaker on/off
   - leave voice
6. Panel shows connected remote users and speaking or muted state.

## Server steps

Run these on the server inside `backend_laravel`:

```bash
composer install --no-dev --optimize-autoloader
php artisan vendor:publish --tag=laravel-agora-token-generator-config --force
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan route:list --path=api/v1/agora
```

If config cache is used in production, rebuild it after env update:

```bash
php artisan config:cache
php artisan route:cache
```

## Quick server test

Use a real user bearer token:

```bash
curl -H "Authorization: Bearer YOUR_USER_TOKEN" "https://YOUR_DOMAIN/api/v1/agora/token?channel=ludo_room_REALROOMUUID&uid=YOUR_USER_ID"
```

## Risk notes

1. `vendor/` is tracked here, so composer changes are large.
2. Composer update changed more than the Agora package.
3. Unity app must never contain `App Certificate`.
4. If production already pins Laravel dependencies tightly, review the lock/vendor diff before deploy.
