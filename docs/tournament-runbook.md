# Tournament Runbook

## Bootstrap

Admin tournament routes are loaded through:

- [admin.php](/d:/Live-Code/games/backend_laravel/routes/admin.php)
- [admin_tournaments.php](/d:/Live-Code/games/backend_laravel/routes/admin_tournaments.php)

## Migrations

Run:

```powershell
cd D:\Live-Code\games\backend_laravel
php artisan migrate
```

## Core Endpoints

### Admin

- `GET /admin/tournaments`
- `POST /admin/tournaments`
- `GET /admin/tournaments/{tournament}`
- `PUT /admin/tournaments/{tournament}`
- `POST /admin/tournaments/{tournament}/publish`
- `POST /admin/tournaments/{tournament}/lock`
- `POST /admin/tournaments/{tournament}/cancel`
- `POST /admin/tournaments/{tournament}/seed-ludo`
- `POST /admin/tournaments/{tournament}/provision-ludo-rooms`
- `POST /admin/tournaments/{tournament}/settle`
- `GET /admin/tournaments/{tournament}/entries`
- `GET /admin/tournaments/{tournament}/leaderboard`
- `GET /admin/tournaments/{tournament}/match-links`

### User API

- `GET /api/v1/tournaments`
- `GET /api/v1/tournaments/{tournament}`
- `POST /api/v1/tournaments/{tournament}/join`

## Automation Commands

- `php artisan tournaments:advance-statuses`
- `php artisan tournaments:seed-ludo`
- `php artisan tournaments:provision-ludo-rooms`

## Suggested Scheduler

Run every minute:

- `tournaments:advance-statuses`
- `tournaments:seed-ludo`
- `tournaments:provision-ludo-rooms`

## Status Flow

- `draft`
- `published`
- `entry_open`
- `entry_locked`
- `seeding`
- `running`
- `completed`
- `cancelled`

## Ludo Tournament Seeding

The current foundation groups tournament entries into match-link tables using:

- [TournamentLudoMatchLinkService.php](/d:/Live-Code/games/backend_laravel/app/Services/Tournament/TournamentLudoMatchLinkService.php)

This creates:

- one `external_match_uuid` per seeded table
- one row in `tournament_match_links` per tournament entry

This is the safe first execution bridge for later connection to live `ludo_v2` room creation.

## Manual Verification

### Create and publish

1. Create tournament in admin
2. Publish tournament
3. Confirm status becomes `published`

### Open and lock

1. Set `entry_open_at` in the past
2. Run `php artisan tournaments:advance-statuses`
3. Confirm `published -> entry_open`
4. Set `entry_close_at` in the past
5. Run `php artisan tournaments:advance-statuses`
6. Confirm `entry_open -> entry_locked`

### Ludo seeding

1. Ensure tournament game is `ludo`
2. Ensure there are joined entries
3. Run `php artisan tournaments:seed-ludo`
4. Confirm `tournament_match_links` rows are created
5. Confirm tournament moves to `running`

### Ludo room provisioning

1. Ensure seeded Ludo tournament exists
2. Run `php artisan tournaments:provision-ludo-rooms`
3. Confirm `game_rooms` row exists with `mode = tournament`
4. Confirm `game_room_players` rows exist for the seeded entries
5. Confirm `tournament_match_links.external_match_uuid` matches the provisioned room UUID

### Settlement

1. Call admin settle endpoint with rankings
2. Confirm `tournament_entry_results` rows exist
3. Confirm tournament status becomes `completed`
4. Confirm wallet capture and prize rows as applicable
