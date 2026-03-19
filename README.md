# Backend Laravel

Separate Laravel backend scaffold for the game platform migration.

## Included

- Laravel 11 style bootstrap entrypoint in `bootstrap/app.php`
- Versioned API placeholders under `/api/v1/...`
- Separate admin API routes in `routes/admin.php`
- Service modules for auth, games, tournaments, wallet, and match orchestration
- MySQL and Redis configuration placeholders
- Node server and Unity versioning environment placeholders

## ERD Summary

- `users` has one `user_profiles`, many `wallets`, and many `tournament_entries`.
- `games` is the root catalog for backend-controlled visibility and owns many `game_settings` and `tournaments`.
- `wallets` stores current balances; `wallet_transactions` is the immutable ledger linked to users, wallets, and optionally games or tournaments.
- `tournaments` belongs to a `game` and owns many `tournament_prizes`, `tournament_entries`, and `tournament_matches`.
- `tournament_entries` allows multiple rows per user per tournament using `entry_no`, enabling controlled re-entry and multi-entry formats.
- `tournament_matches` belongs to a tournament and game; `tournament_match_entries` links match seats to tournament entries and users.
- `admin_users` owns many `admin_action_logs`; `audit_logs` is a broader cross-domain audit stream for user, admin, and system events.

## Next steps

1. Run `composer install` inside `backend_laravel`.
2. Generate an application key with `php artisan key:generate`.
3. Run `php artisan migrate --seed` after configuring MySQL and Redis.
4. Add concrete controllers, requests, and resources based on the CodeIgniter audit.
