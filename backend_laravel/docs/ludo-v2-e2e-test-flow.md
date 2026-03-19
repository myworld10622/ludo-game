# Ludo V2 End-to-End Test Flow

This document is the practical test flow for validating the current `ludo_v2` implementation from login through room join, bot fill, match completion, and wallet settlement.

## Goal

Validate that the following works together:

1. Unity login and lobby
2. `ludo_v2` room queue join
3. Sequential bot fill every 8 seconds
4. Match start
5. Match finish
6. Match persistence in Laravel
7. Wallet hold capture
8. Winner payout credit

## Current Scope

This test flow is for the current `ludo_v2` path only.

It assumes:

- Unity is using `ludo_v2`
- Node server is running on port `3002`
- Laravel server is running on port `8000`
- internal Laravel sync is enabled in Node
- the user has enough wallet balance for the selected entry fee

## Pre-Test Checklist

Before running the test, confirm all of the following:

### Laravel

- `.env` contains `INTERNAL_API_TOKEN=betzono123`
- app is running:

```powershell
cd D:\Live-Code\games\backend_laravel
php artisan serve --host=127.0.0.1 --port=8000
```

### Node

- `.env` contains:
  - `INTERNAL_API_TOKEN=betzono123`
  - `LUDO_LARAVEL_SYNC_ENABLED=true`
  - `LARAVEL_INTERNAL_BASE_URL=http://127.0.0.1:8000/api/internal/v1`
- server is running:

```powershell
cd D:\Live-Code\games\node
npm start
```

### Unity

- `ludo_v2` is enabled in configuration
- Unity backend URLs point to:
  - `http://localhost:8000/`
  - `http://localhost:3002`
- login is working

## Test User Preparation

Use a known user account with enough wallet balance.

Recommended minimum:

- wallet balance: `500` or more

If the balance is low, top it up manually in DB for test purposes.

Example SQL:

```sql
UPDATE wallets
SET balance = 500.0000
WHERE user_id = 4;
```

Confirm current wallet:

```sql
SELECT user_id, balance, currency
FROM wallets
WHERE user_id = 4;
```

## DB Snapshot Before Test

Run these before starting the match:

```sql
SELECT user_id, balance
FROM wallets
WHERE user_id = 4;
```

```sql
SELECT id, transaction_type, status, amount, balance_before, balance_after, reference_type, reference_id, description
FROM wallet_transactions
WHERE user_id = 4
ORDER BY id DESC
LIMIT 10;
```

```sql
SELECT id, room_uuid, status, entry_fee, max_players, current_players, current_real_players, current_bot_players
FROM game_rooms
ORDER BY id DESC
LIMIT 5;
```

```sql
SELECT id, match_uuid, game_room_id, status, winner_user_id, prize_pool
FROM game_matches
ORDER BY id DESC
LIMIT 5;
```

## Primary Cash Match Test

### Step 1: Login

In Unity:

1. launch the app
2. login with the test account
3. verify lobby opens correctly

Expected:

- no blocking errors
- wallet visible
- Ludo tile visible

### Step 2: Open Ludo

1. click Ludo
2. choose the entry-fee flow, not Pass N Play
3. choose 4-player mode
4. select a cash table, for example `50`

Expected:

- wallet amount should be enough
- room join should succeed
- board should open
- first player seat should show current user

### Step 3: Observe Sequential Bot Fill

Expected timing:

1. seat 1 = human
2. wait 8 seconds for seat 2
3. if no real player joins, bot fills seat 2
4. wait another 8 seconds for seat 3
5. bot fills seat 3
6. wait another 8 seconds for seat 4
7. bot fills seat 4
8. room starts

Expected UI behavior:

- board remains visible
- empty seats show waiting
- seats fill one-by-one
- start countdown begins only after room is full

### Step 4: Verify Hold on Entry

After room join and before match settlement, check wallet transaction:

```sql
SELECT id, transaction_type, status, amount, balance_before, balance_after, reference_type, reference_id, description
FROM wallet_transactions
WHERE user_id = 4
ORDER BY id DESC
LIMIT 5;
```

Expected:

- a new wallet transaction exists for the Ludo room reservation
- type should reflect hold/debit reservation behavior
- status should indicate hold or pending capture

Also check latest room:

```sql
SELECT id, room_uuid, status, entry_fee, current_players, current_real_players, current_bot_players
FROM game_rooms
ORDER BY id DESC
LIMIT 1;
```

Expected:

- room exists
- status should move from `waiting` to `playing`

### Step 5: Verify Match Start Persistence

Once the room starts, confirm a match was created:

```sql
SELECT id, match_uuid, game_room_id, status, winner_user_id, prize_pool, started_at, completed_at
FROM game_matches
ORDER BY id DESC
LIMIT 3;
```

Expected:

- a new `game_matches` row exists
- status should be `playing` or equivalent until completion

Check player rows:

```sql
SELECT game_match_id, seat_no, user_id, player_type, status, is_winner, payout_amount, finish_position
FROM game_match_players
WHERE game_match_id = (
    SELECT id
    FROM game_matches
    ORDER BY id DESC
    LIMIT 1
)
ORDER BY seat_no;
```

Expected:

- all 4 seats present
- human user on one seat
- remaining seats marked as `bot`

### Step 6: Play Full Match

In Unity:

1. play until the match result screen appears
2. confirm the winner panel shows
3. confirm the room finishes normally

Recommended test case:

- first run: make the test user win
- second run: make the test user lose

### Step 7: Verify Match Completion Settlement

After the result screen appears, run:

```sql
SELECT id, match_uuid, status, winner_user_id, prize_pool, completed_at
FROM game_matches
ORDER BY id DESC
LIMIT 3;
```

Expected:

- latest match status = `completed`
- `winner_user_id` should be set if a human user won

Check match players:

```sql
SELECT seat_no, user_id, player_type, status, is_winner, payout_amount, finish_position, score
FROM game_match_players
WHERE game_match_id = (
    SELECT id
    FROM game_matches
    ORDER BY id DESC
    LIMIT 1
)
ORDER BY seat_no;
```

Expected:

- winner row has `is_winner = 1`
- payout amount present for winner
- finish positions populated

### Step 8: Verify Wallet Settlement

Check wallet ledger:

```sql
SELECT id, transaction_type, status, amount, balance_before, balance_after, reference_type, reference_id, description
FROM wallet_transactions
WHERE user_id = 4
ORDER BY id DESC
LIMIT 15;
```

Expected for winner:

- entry hold should be captured
- prize credit should be inserted

Expected for loser:

- entry hold should be captured
- no prize credit

Check final balance:

```sql
SELECT user_id, balance
FROM wallets
WHERE user_id = 4;
```

## Result Expectations

### If user wins

Expected wallet behavior:

- join time: entry amount reserved/held
- finish time: hold captured
- prize credited

### If user loses

Expected wallet behavior:

- join time: entry amount reserved/held
- finish time: hold captured
- no prize credit

### If match is cancelled later

Expected wallet behavior:

- hold refunded
- match status becomes `cancelled`

## Recommended Test Matrix

Run these cases:

1. cash room, human wins
2. cash room, human loses
3. practice room, human wins
4. room exit before match start
5. app close or disconnect during waiting state
6. reconnect during match

## Common Failure Signs

### Problem: room joins but no match record created

Check:

- Node `.env` sync flags
- Laravel internal token
- Node logs for internal sync errors

### Problem: match finishes but wallet does not settle

Check:

- Unity emitted `ludo.match.complete`
- Node logs for `notifyMatchCompleted`
- Laravel logs for internal complete route

### Problem: duplicate settlement

Check:

- `hasReportedMatchCompletion` guard in Unity bridge
- repeated result screen calls

## Minimal Runtime Log Checklist

During a successful run, you should see evidence of:

1. `ludo_v2` waiting room
2. bot joined seat 2
3. bot joined seat 3
4. bot joined seat 4
5. room ready / starting
6. result screen
7. match completion emitted

## Final Verification Query Pack

Use these after the full test:

```sql
SELECT id, room_uuid, status, entry_fee, current_players, current_real_players, current_bot_players, completed_at
FROM game_rooms
ORDER BY id DESC
LIMIT 5;
```

```sql
SELECT id, match_uuid, status, winner_user_id, prize_pool, started_at, completed_at
FROM game_matches
ORDER BY id DESC
LIMIT 5;
```

```sql
SELECT game_match_id, seat_no, user_id, player_type, status, is_winner, payout_amount, finish_position, score
FROM game_match_players
ORDER BY id DESC
LIMIT 20;
```

```sql
SELECT id, user_id, transaction_type, status, amount, balance_before, balance_after, reference_type, reference_id, description
FROM wallet_transactions
WHERE user_id = 4
ORDER BY id DESC
LIMIT 20;
```

```sql
SELECT user_id, balance
FROM wallets
WHERE user_id = 4;
```

## Current Limitation

This flow is designed for the current hybrid `ludo_v2` state.

It validates:

- room lifecycle
- match persistence
- settlement foundation

It does not yet prove a fully authoritative online multiplayer engine for all turn logic across multiple real human clients.
