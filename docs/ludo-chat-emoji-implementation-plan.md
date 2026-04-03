# Ludo Chat and Emoji Implementation Plan

This document defines a practical rollout plan for adding player-to-player chat and emoji reactions inside the current Ludo project.

The plan is based on the existing architecture already present in this repository:

- `node/sockets/ludoRoomSocket.js` manages live Ludo room lifecycle.
- `node/services/ludoRoomEngineService.js` manages room creation and bot-fill decisions.
- `backend_laravel/app/Services/Match/LudoMatchmakingService.php` manages room reservation and wallet hold.
- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoV2MatchmakingBridge.cs` manages Unity matchmaking and Ludo v2 socket events.

The goal is to let players who are inside the same active Ludo room:

- send text messages to each other
- send quick emoji reactions
- reopen recent room chat if they reconnect
- stay inside a safe, rate-limited, abuse-controlled experience

## 1. Scope

### In scope

- Room-based text chat for players in the same Ludo room
- Quick emoji reactions during gameplay
- Reconnect-safe recent message sync
- Support for public cash rooms, practice rooms, private rooms, and tournament rooms
- Basic moderation controls and audit trail
- Unity in-game chat panel and emoji tray

### Out of scope for phase 1

- Image, voice note, sticker, GIF, or file sharing
- Cross-room friend inbox or DM system
- Translation
- AI moderation
- Push notifications for offline chat

## 2. Product Decision

We should treat this as **two separate communication layers**:

### Layer A: Emoji reactions

- Very fast interaction
- Lightweight payload
- No database persistence required in phase 1
- Good for low-risk first release

### Layer B: Text chat

- Real room conversation
- Requires validation, rate limiting, moderation, and history
- Should be persisted for short retention

This separation reduces risk. Emoji can go live first, then full text chat can be enabled after load and moderation checks.

## 3. Recommended User Experience

### Waiting room

- Players can chat as soon as they join the room
- System messages can appear:
  - `Player 2 joined`
  - `Bot joined`
  - `Match starting...`

### In-match

- Chat drawer opens from a small chat icon
- Bottom input bar for short text
- Separate emoji button opens a fixed emoji tray
- New unread badge shown when drawer is closed
- Latest message preview optional near chat icon

### Reconnect

- Player reconnects to same room
- Client fetches last 30 to 50 messages
- Emoji reactions are not replayed in full history by default

### Post-match

- Chat becomes read-only for a short grace period like 60 to 120 seconds
- Then room chat closes

## 4. Current Architecture Fit

The cleanest implementation is to keep chat inside the same Ludo room identity already used by `ludoRoomSocket`.

### Why this fits well

- Room membership already exists in Node
- Socket join and leave lifecycle already exists
- Reconnect flow already exists
- Laravel already stores room and match records
- Unity already listens to the Ludo v2 namespace

### Important design choice

Do not create a completely separate socket namespace for chat in phase 1.

Instead:

- reuse `/ludo_v2`
- reuse `room.roomId`
- add chat-specific events within the existing namespace

This keeps room authorization simpler and avoids duplicate connection state.

## 5. Functional Requirements

### Text chat requirements

- Only active participants in the room can send messages
- Message max length: 200 characters recommended
- Plain text only
- Unicode allowed for multilingual chat
- Server trims whitespace
- Empty message blocked
- Message history available on room join and reconnect
- Sender info included with each message
- Message timestamps included

### Emoji requirements

- Use a predefined emoji catalog
- Only catalog IDs are sent over socket
- No custom Unicode spam payloads for emoji reactions
- Emoji renders as floating reaction near sender seat or in chat stream
- Cooldown should be stricter than text chat burst

### Safety requirements

- Rate limit per user
- Basic bad-word filter
- Optional mute/block by admin later
- Auditability for dispute handling

## 6. Room Chat Rules

### Who can chat

- Human players in the same room
- Bots cannot send chat in phase 1

### Who can receive

- All sockets currently joined to the room
- Reconnected player can fetch recent history

### Room eligibility

- `waiting`
- `waiting_bot_fill`
- `starting`
- `in_progress`
- `settlement_pending`

### Room closure

- `completed` and `cancelled` should become read-only
- optional grace period after completion

## 7. Technical Architecture

### Realtime path

1. Unity sends chat event over `/ludo_v2`
2. Node verifies socket belongs to the room
3. Node validates payload, length, cooldown, and membership
4. Node optionally persists text message via Laravel API or direct DB-backed service
5. Node broadcasts message to `namespace.to(room.roomId)`
6. Unity appends message to chat UI

### Persistence path

Recommended for text chat:

1. Node accepts message
2. Laravel persists canonical room message
3. Laravel returns saved message payload with ID and timestamp
4. Node emits canonical payload to room

Recommended for emoji:

- realtime only in phase 1
- optional persistence later only if analytics or abuse tracking is needed

## 8. Event Contract Proposal

### Client -> Server

- `ludo.chat.send`
- `ludo.chat.history`
- `ludo.chat.emoji`
- `ludo.chat.mark_read` optional

### Server -> Client

- `ludo.chat.message`
- `ludo.chat.history`
- `ludo.chat.emoji`
- `ludo.chat.system`
- `ludo.chat.error`

## 9. Payload Proposal

### `ludo.chat.send`

```json
{
  "room_id": "room-uuid",
  "client_message_id": "tmp-123",
  "message": "bhai dice mast aa gaya"
}
```

### `ludo.chat.message`

```json
{
  "message_id": "chat-msg-uuid",
  "room_id": "room-uuid",
  "match_uuid": "match-uuid-or-null",
  "sender": {
    "user_id": 101,
    "seat_no": 2,
    "display_name": "Player 2",
    "avatar": null
  },
  "message": "bhai dice mast aa gaya",
  "message_type": "text",
  "created_at": "2026-04-03T12:00:00Z",
  "client_message_id": "tmp-123"
}
```

### `ludo.chat.history`

```json
{
  "room_id": "room-uuid",
  "messages": []
}
```

### `ludo.chat.emoji`

```json
{
  "room_id": "room-uuid",
  "emoji_id": "laugh",
  "sender": {
    "user_id": 101,
    "seat_no": 2,
    "display_name": "Player 2"
  },
  "created_at": "2026-04-03T12:00:05Z"
}
```

### `ludo.chat.system`

```json
{
  "room_id": "room-uuid",
  "message_type": "system",
  "message": "Match starting...",
  "created_at": "2026-04-03T12:00:07Z"
}
```

## 10. Database Design

Text chat should be stored in Laravel because moderation, audit, and support workflows belong there.

### New table: `game_room_messages`

Recommended columns:

- `id`
- `message_uuid`
- `game_room_id`
- `game_match_id` nullable
- `user_id`
- `seat_no` nullable
- `message_type` enum: `text`, `system`
- `content`
- `status` enum: `visible`, `deleted`, `flagged`
- `meta` json nullable
- `created_at`
- `updated_at`
- `deleted_at` nullable

### Optional future table: `game_room_message_reports`

- `id`
- `game_room_message_id`
- `reported_by_user_id`
- `reason`
- `meta`
- `created_at`

### Why not store emoji in same table in phase 1

- high-volume transient events
- low moderation value
- better as socket-only reactions initially

If analytics is needed later, add a lightweight `game_room_reactions` table or event stream.

## 11. Laravel Responsibilities

Laravel should own:

- message persistence
- room-to-user authorization check
- message history API
- content moderation policy
- admin reporting and deletion
- retention policy

### Suggested new API endpoints

Unity-facing authenticated endpoints:

- `GET /api/v1/ludo/rooms/{roomUuid}/messages`

Internal Node-facing endpoints:

- `POST /api/internal/v1/ludo/rooms/{roomUuid}/messages`
- `GET /api/internal/v1/ludo/rooms/{roomUuid}/messages?limit=50`

### Suggested service classes

- `App\Services\Chat\LudoRoomChatService`
- `App\Http\Controllers\Api\V1\LudoRoomMessageController`
- `App\Http\Controllers\Api\Internal\V1\LudoRoomMessageController`

### Validation rules

- room exists
- user is current participant of room
- message length <= 200
- profanity filter
- no blank content
- room status allows chat

## 12. Node Responsibilities

Node should own:

- low-latency socket receive and broadcast
- room socket authorization
- in-memory cooldown tracking
- reconnect-triggered history request
- system event emission

### Recommended integration point

Add chat handlers inside `node/sockets/ludoRoomSocket.js`.

Suggested internal helpers:

- `handleChatSend(socket, payload)`
- `handleChatHistory(socket, payload)`
- `handleChatEmoji(socket, payload)`
- `emitSystemMessage(room, message)`

### In-memory state recommended

Per room:

- recent read-through cache optional
- per-user rate limit counters

Per socket:

- `socket.data.userId`
- `socket.data.roomId`
- `socket.data.seatNo`

If not already set consistently, enrich socket metadata at join time.

## 13. Unity Responsibilities

Unity should own:

- chat panel UI
- emoji tray UI
- unread badge
- message list rendering
- local pending message state
- reconnect history refresh

### Recommended integration point

Primary place:

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoV2MatchmakingBridge.cs`

Additional UI classes recommended:

- `LudoRoomChatController`
- `LudoRoomChatMessageItem`
- `LudoRoomEmojiTrayController`

### Unity event handling

Bridge should subscribe to:

- `ludo.chat.message`
- `ludo.chat.history`
- `ludo.chat.emoji`
- `ludo.chat.system`
- `ludo.chat.error`

### UI behaviour recommendation

- keep chat collapsed by default
- open full panel only on tap
- show mini floating emoji animation above player seat
- show text messages in side drawer, not over the board

## 14. Security and Moderation

### Mandatory controls

- message length cap
- server-side trimming
- rate limit per user
- repeated duplicate suppression
- profanity keyword filter
- audit log retention

### Recommended rate limits

Text chat:

- max 4 messages in 10 seconds
- hard cap 20 messages in 60 seconds

Emoji:

- 1 reaction per 2 seconds
- burst cap 6 per 30 seconds

### Admin operations for later phase

- delete message
- flag user
- mute user from room chat
- view room chat transcript by room UUID or match UUID

## 15. Performance and Scale Notes

This feature can create sudden socket traffic, especially emoji spam.

### Controls to keep it safe

- fixed emoji catalog IDs instead of full payloads
- no typing indicators in phase 1
- no read receipts in phase 1
- history limit 30 to 50 messages only
- lazy-open chat panel in Unity
- keep system messages concise

### Caching idea

If Laravel persistence becomes a bottleneck, Node can:

- write canonical message through Laravel
- keep recent room history cache in memory for short time
- fall back to Laravel on reconnect if cache miss

## 16. Rollout Plan

### Phase 1: Emoji reactions only

Goal:

- deliver player expression quickly with low risk

Work:

- add `ludo.chat.emoji`
- predefined emoji catalog
- Unity animation hookup
- cooldown and room authorization

No DB required.

### Phase 2: Basic room text chat

Goal:

- allow players in same room to talk during waiting and active match

Work:

- `game_room_messages` table
- Laravel message persistence APIs
- `ludo.chat.send`
- `ludo.chat.message`
- `ludo.chat.history`
- Unity chat drawer
- rate limiting and profanity filter

### Phase 3: Moderation and admin tooling

Goal:

- make support and abuse handling production-ready

Work:

- admin transcript viewer
- delete/flag flow
- mute controls
- retention rules
- report abuse flow

### Phase 4: Nice-to-have polish

- quick chat presets
- friend-only private match chat styles
- local mute
- advanced analytics

## 17. Detailed Engineering Plan

### Backend Laravel tasks

1. Create migration for `game_room_messages`
2. Create model and resource classes
3. Create internal controller for Node write and history fetch
4. Create Unity-facing read endpoint if needed
5. Add room participant authorization checks
6. Add profanity filter service
7. Add admin retrieval endpoint later

### Node tasks

1. Add new chat socket event constants in `node/constants/ludoRoom.js`
2. Extend room join flow so socket stores user and seat metadata
3. Add chat event handlers in `node/sockets/ludoRoomSocket.js`
4. Add rate-limit helper
5. Add Laravel sync service for room messages
6. Emit system messages on join, leave, bot join, and start if desired

### Unity tasks

1. Extend `LudoV2MatchmakingBridge.cs` with chat event subscriptions
2. Add chat send method
3. Add emoji send method
4. Add room chat UI prefab/panel
5. Add message cell rendering
6. Add reconnect history fetch after room join
7. Add unread counter and open/close behaviour

## 18. Suggested Event Constants Update

Inside `node/constants/ludoRoom.js` we should add:

### Client

- `CHAT_SEND: "ludo.chat.send"`
- `CHAT_HISTORY: "ludo.chat.history"`
- `CHAT_EMOJI: "ludo.chat.emoji"`

### Server

- `CHAT_MESSAGE: "ludo.chat.message"`
- `CHAT_HISTORY: "ludo.chat.history"`
- `CHAT_EMOJI: "ludo.chat.emoji"`
- `CHAT_SYSTEM: "ludo.chat.system"`
- `CHAT_ERROR: "ludo.chat.error"`

## 19. Suggested System Messages

Use sparingly. Recommended only for meaningful room lifecycle events:

- `Player joined the room`
- `Player left the room`
- `Bot joined the room`
- `Match starting`
- `Match completed`

Too many system messages will make chat noisy.

## 20. Testing Plan

### Unit tests

- message validation
- profanity filter
- room membership auth
- rate limit logic
- chat disabled in invalid room state

### Integration tests

- player A and B join same room and exchange messages
- player from another room cannot inject message
- reconnect returns recent history
- tournament room chat works only for assigned participants
- cancelled room becomes read-only

### Unity QA

- chat panel opens/closes cleanly
- emoji animation renders on correct seat
- unread badge increments correctly
- reconnect does not duplicate history
- long names do not break layout
- keyboard opening on Android does not overlap board badly

### Load tests

- 100 to 500 concurrent room chats with emoji bursts
- verify Node event loop latency
- verify Laravel write throughput

## 21. Risks

### Risk 1: Spam and abuse

Mitigation:

- strict rate limiting
- bad-word filtering
- admin transcript visibility

### Risk 2: Socket room auth bypass

Mitigation:

- always validate `socket.data.roomId`
- validate sender belongs to seat in room
- do not trust client `room_id` alone

### Risk 3: Chat impacts gameplay performance

Mitigation:

- separate lightweight handlers
- avoid expensive DB calls for emoji
- keep text payload small

### Risk 4: Reconnect duplicates messages in UI

Mitigation:

- include `message_id`
- Unity de-duplicates by canonical ID or `client_message_id`

## 22. Recommended Delivery Order

Best delivery order for this repo:

1. Add emoji reactions in Node + Unity
2. Verify seat-based rendering and cooldowns
3. Add Laravel message persistence
4. Add text chat socket handlers
5. Add Unity chat drawer
6. Add moderation controls

This order keeps the first release fast and avoids mixing UI, DB, and moderation risk in one big change.

## 23. Final Recommendation

For this project, the safest and most maintainable solution is:

- keep chat room identity same as existing Ludo room ID
- use current `/ludo_v2` socket namespace
- launch emoji first
- launch text chat second with Laravel-backed storage
- keep bots silent in phase 1
- keep feature room-scoped, not friend-DM scoped

If later you want a true friend chat system outside gameplay, that should be built as a separate feature with separate room and inbox design.

## 24. Implementation Summary

### Best architecture choice

- Reuse existing Ludo room and socket namespace

### Best release strategy

- Phase 1 emoji
- Phase 2 room text chat
- Phase 3 moderation

### Best storage choice

- text in Laravel
- emoji realtime only initially

### Best Unity UX

- compact chat drawer plus emoji tray

### Best safety posture

- rate limit plus basic moderation from day one
