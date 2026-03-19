# Ludo Tournament Integration

## Goal

Connect provisioned Laravel tournament rooms to the existing Node `ludo_v2` runtime without breaking the current public cash/practice queue flow.

## Current Safe Integration Points

### Laravel APIs

- `POST /api/v1/tournaments/{tournament}/claim-room`
- `POST /api/internal/v1/tournaments/ludo/rooms/{roomUuid}/complete`

### Node services

- [tournamentLudoRoomService.js](/d:/Live-Code/games/node/services/tournamentLudoRoomService.js)
- [tournamentLudoLaravelSyncService.js](/d:/Live-Code/games/node/services/tournamentLudoLaravelSyncService.js)

## Recommended Socket Contract

### Client to server

- `ludo.tournament.claim_room`
  - `tournamentUuid`
  - `tournamentEntryUuid`
  - `accessToken`

- `ludo.tournament.match_complete`
  - `roomUuid`
  - `seatResults`

### Server to client

- `ludo.tournament.room_claimed`
- `ludo.tournament.room_claim_failed`
- `ludo.tournament.settlement_complete`
- `ludo.tournament.settlement_failed`

## Suggested Flow

1. User joins a tournament through Laravel.
2. Admin or scheduler seeds the tournament into match links.
3. Admin or scheduler provisions Ludo rooms.
4. Client calls `claim-room` using tournament entry UUID.
5. Node receives the room payload and joins the player into the provisioned room instead of queue matchmaking.
6. On match completion, Node maps seat results to tournament entries.
7. Node calls Laravel internal tournament completion endpoint.

## Important Rule

Do not reuse the normal public queue path for tournament rooms.

Tournament rooms are pre-provisioned and should be claimed, not matched.

## Environment

Node should have:

- `LARAVEL_API_BASE_URL=http://127.0.0.1:8000/api`
- `LARAVEL_INTERNAL_BASE_URL=http://127.0.0.1:8000/api/internal/v1`
- `INTERNAL_API_TOKEN=...`
- `LUDO_LARAVEL_SYNC_ENABLED=true`

## Next Wiring Step

Patch the current `ludo_v2` socket namespace to:

- branch cash/practice queue joins into existing flow
- branch tournament claims into Laravel-provisioned room flow
- post tournament results back through the internal completion endpoint
