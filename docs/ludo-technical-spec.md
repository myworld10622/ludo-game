# Ludo Technical Spec

This document defines the first production-oriented Ludo foundation for the migrated platform.
It is intentionally additive and does not replace the current working Unity or Node flow yet.

## Goals

- Support public cash rooms, private rooms, and future tournament rooms.
- Allow real players to join a room and fill remaining seats with bots after a wait window.
- Keep real-time gameplay authoritative on the socket server.
- Keep money, persistence, admin controls, and auditability in Laravel.
- Reuse the existing Unity Ludo UI with minimal contract-focused changes.

## Responsibility Split

### Laravel

- Auth and player identity
- Wallet debit, hold, refund, and prize settlement
- Game configuration and visibility
- Room and match persistence
- Admin controls
- Match audit trail and reconciliation
- Tournament orchestration integration

### Node

- Real-time room matchmaking
- Wait timer and bot-fill orchestration
- Socket session and reconnect handling
- Turn engine and move validation
- Dice generation and token movement validation
- Live event broadcasting
- Match completion callback to Laravel

## Room Lifecycle

### Public Cash Room

1. Player requests to join a Ludo queue for a specific mode and stake.
2. Laravel validates player and reserves the wallet amount.
3. Node allocates the player to an open waiting room or creates a new room.
4. Room enters `waiting` state.
5. When the first player joins, `fill_bots_at` is scheduled for `now + bot_fill_after_seconds`.
6. If enough real players join before the timer, room starts with real players only.
7. If seats are still empty at timer expiry and bots are allowed, bots occupy remaining seats.
8. Room moves to `starting`.
9. Match state is created and room moves to `in_progress`.
10. On finish:
    - Node computes final placements
    - Laravel settles payouts
    - Match and room move to `completed`

### Failure Cases

- If wallet hold fails, room join is rejected.
- If room cannot start, reserved funds are released or refunded.
- If a player disconnects before match start, seat may be replaced by a bot depending on policy.
- If all humans leave before start, room is cancelled.

## Bot-Fill Policy

Default policy:

- `max_players`: 4
- `min_real_players_to_start`: 1
- `bot_fill_after_seconds`: 8
- `allow_bots`: true for public cash rooms
- `allow_bots`: false for tournament rooms

Examples:

- 1 real player joined by 8 seconds -> 3 bots added, game starts
- 2 real players joined by 8 seconds -> 2 bots added, game starts
- 3 real players joined by 8 seconds -> 1 bot added, game starts
- 4 real players joined before timer -> no bots added

Tournament default:

- bots disabled
- room starts only with required real players

## Server-Authoritative Rules

The client must not own gameplay decisions.

The socket server must be authoritative for:

- room membership
- seat assignment
- countdowns
- dice rolls
- legal token moves
- turn transitions
- timeout actions
- finish detection
- winner determination

The Unity client should render state and submit player intent only.

## Socket Contract

### Client -> Server

- `ludo.queue.join`
- `ludo.room.leave`
- `ludo.room.ready`
- `ludo.game.roll_dice`
- `ludo.game.move_token`
- `ludo.game.emoji`
- `ludo.session.reconnect`

### Server -> Client

- `ludo.room.waiting`
- `ludo.room.player_joined`
- `ludo.room.bot_joined`
- `ludo.room.countdown`
- `ludo.room.starting`
- `ludo.game.snapshot`
- `ludo.game.turn_started`
- `ludo.game.dice_rolled`
- `ludo.game.token_moved`
- `ludo.game.turn_missed`
- `ludo.game.player_finished`
- `ludo.game.result`
- `ludo.wallet.updated`
- `ludo.error`

### Core Payload Requirements

Every room or match payload should contain:

- `room_id`
- `match_id`
- `game_slug`
- `mode`
- `status`
- `max_players`
- `seat_map`
- `real_player_count`
- `bot_player_count`
- `entry_fee`
- `started_at`
- `countdown_remaining`

Each seat should contain:

- `seat_no`
- `player_type` (`human` or `bot`)
- `user_id` or `bot_code`
- `display_name`
- `avatar`
- `is_connected`
- `is_ready`

## Database Schema

### `game_rooms`

Persistent room queue and room lifecycle metadata.

Important columns:

- `room_uuid`
- `game_id`
- `room_type`
- `play_mode`
- `status`
- `max_players`
- `min_real_players`
- `current_players`
- `current_real_players`
- `current_bot_players`
- `entry_fee`
- `prize_pool`
- `allow_bots`
- `bot_fill_after_seconds`
- `started_with_bots`
- `node_namespace`
- `node_room_id`
- `game_mode`
- `fill_bots_at`
- `started_at`
- `completed_at`

### `game_room_players`

Tracks room seat allocation before and during room start.

Important columns:

- `game_room_id`
- `user_id`
- `seat_no`
- `player_type`
- `bot_code`
- `status`
- `wallet_transaction_id`
- `reconnect_token`
- `joined_at`
- `left_at`
- `last_seen_at`

### `game_matches`

Canonical match record for reconciliation and history.

Important columns:

- `match_uuid`
- `game_id`
- `game_room_id`
- `status`
- `mode`
- `max_players`
- `real_players`
- `bot_players`
- `entry_fee`
- `prize_pool`
- `winner_user_id`
- `node_namespace`
- `node_room_id`
- `turn_state`
- `result_payload`
- `started_at`
- `completed_at`

### `game_match_players`

Per-player match outcome and payout record.

Important columns:

- `game_match_id`
- `user_id`
- `game_room_player_id`
- `seat_no`
- `player_type`
- `bot_code`
- `finish_position`
- `score`
- `is_winner`
- `payout_amount`
- `status`
- `stats`
- `joined_at`
- `finished_at`

## Unity Impact

The current Unity Ludo UI is reusable.

Expected changes:

- swap old socket event names to the new contract gradually
- wire waiting-room countdown and bot seat rendering
- add reconnect token handling
- align win/result panel payload parsing

Expected non-changes:

- no full Ludo scene rewrite
- no major art or HUD rebuild

## Migration Path

### Phase 1

- Keep current Node `/ludo` namespace running
- Add Laravel schema for rooms and matches
- Freeze target socket contract
- Implement room and bot-fill services without routing traffic yet

### Phase 2

- Add Node room engine for real player + bot rooms
- Persist room and match snapshots to Laravel
- Connect wallet reservation and settlement flows

### Phase 3

- Adapt Unity from old proxy/socket flow to the new room events
- Remove old CodeIgniter `/api/ludo/...` dependency

## Non-Goals For This Foundation Pass

- Full gameplay engine replacement in this pass
- Full tournament gameplay implementation in this pass
- Full removal of the current socket proxy in this pass
