# Tournament Load Testing

This document describes the current operator workflow for preparing and running tournament load tests against the Ludo tournament stack.

## Goals

- prepare a large tournament fixture quickly
- test tournament room claims at scale
- optionally auto-complete claimed matches for round progression checks

## Backend Fixture Prep

Use the backend command:

```powershell
php artisan tournaments:prepare-load-fixture --match-size=4 --entries=128 --output=storage/app/tournament-load-fixture.json
```

What it does:

- creates a dedicated tournament
- creates the requested number of joined entries
- exports a JSON fixture file with:
  - `tournament_uuid`
  - `entry_uuid`
  - `user_id`
  - `ticket_no`

Important:

- `access_token` is intentionally `null` in the exported file
- before running socket load tests, populate valid tokens for each entry user

To mint tokens directly during fixture prep:

```powershell
php artisan tournaments:prepare-load-fixture --match-size=4 --entries=128 --with-tokens --output=storage/app/tournament-load-fixture.json
```

To enrich an existing fixture with tokens:

```powershell
php artisan tournaments:enrich-load-fixture-tokens storage/app/tournament-load-fixture.json
```

## Node Socket Load Test

Use the Node script:

```powershell
$env:LUDO_LOAD_FIXTURE_PATH='D:\Live-Code\games\backend_laravel\storage\app\tournament-load-fixture.json'
$env:LUDO_LOAD_CONCURRENCY='20'
$env:LUDO_LOAD_AUTO_COMPLETE='false'
node .\scripts\test-ludo-v2-tournament-load.js
```

Optional:

- set `LUDO_LOAD_AUTO_COMPLETE=true` to emit tournament match completion payloads automatically when rooms start
- use smaller concurrency first, then scale up
- for larger runs, use staged batching:
  - `LUDO_LOAD_BATCH_SIZE`
  - `LUDO_LOAD_BATCH_PAUSE_MS`

## Recommended Progression

1. Prepare a fixture with `32` or `64` entries.
2. Fill real `access_token` values in the JSON file.
3. Run claims with `AUTO_COMPLETE=false`.
4. Inspect backend:
   - tournament health endpoint
   - admin matches endpoint
5. Repeat with:
   - higher concurrency
   - larger fixture sizes
   - `AUTO_COMPLETE=true`

## Full Lifecycle Test Tip

For a true end-to-end auto-complete tournament run, keep `LUDO_LOAD_CONCURRENCY`
at least equal to the number of first-round entries you expect to claim at once.

Example:

- 8-entry tournament full-run: use `LUDO_LOAD_CONCURRENCY=8`

If concurrency is lower than the active first-round entry count, unclaimed seats may
be bot-replaced before their client starts, which will show up as expected
`room assignment not found` for those late entries rather than a backend failure.

For very large runs such as `256+` entries, prefer staged waves instead of one-shot
parallel floods.

Example:

```powershell
$env:LUDO_LOAD_CONCURRENCY='64'
$env:LUDO_LOAD_BATCH_SIZE='64'
$env:LUDO_LOAD_BATCH_PAUSE_MS='3000'
$env:LUDO_LOAD_PROGRESS_LOG_INTERVAL_MS='15000'
```

This keeps each wave manageable while still exercising full tournament progression.
`LUDO_LOAD_BATCH_SIZE` works as a wave launcher, so later waves start on schedule
without waiting for earlier waves to fully complete.

## Useful Admin Endpoints

- `GET /admin/tournaments/{tournament}/health`
- `GET /admin/tournaments/{tournament}/matches`
- `POST /admin/tournaments/{tournament}/retry-round-lifecycle`
- `POST /admin/tournaments/{tournament}/retry-round-seeding`
- `POST /admin/tournaments/{tournament}/retry-round-provisioning`

## Current Limits

- this tooling prepares the workflow; it does not generate or mint auth tokens
- the best signal comes from real queue workers and a live Node socket server
- for very large tests, monitor DB load, queue latency, and room creation throughput
