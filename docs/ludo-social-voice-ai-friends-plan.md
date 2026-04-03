# Ludo Social, Voice, AI Chat, and Friends Plan

This is the master implementation document for expanding the current Ludo project with:

- room text chat
- emoji reactions
- bot AI chat
- voice talk using Agora
- friend request system
- add-friend by player ID
- online and gameplay notifications for friends

This plan is based on the current repo structure:

- `node/` for realtime sockets and match flow
- `backend_laravel/` for persistence, APIs, admin, wallet, and orchestration
- `unity/` for the game client

## 1. Main User Flows

### Room social

- users in the same room can text chat
- users in the same room can send emoji
- reconnecting user can load recent room messages

### Bot social

- if a bot is in the room, bot can also send short controlled chat replies

### Voice social

- users in the same room can join a temporary voice channel and talk live

### Friends

- User A can send friend request to User B from inside room
- User A can also send friend request from lobby by entering User B player ID
- once accepted, they become friends for future play
- if a friend comes online or starts playing Ludo, user can receive notification

Example:

- User A player ID: `12345678`
- User B player ID: `78945612`
- both play in same room
- User A opens User B profile card and taps `Send Friend Request`
- later User A can also search `78945612` from lobby and send request there

## 2. Product Modules

Build this as 4 clear modules:

### Module A: Room Chat

- text chat
- emoji reactions
- system messages

### Module B: Friends System

- friend requests
- add-by-ID
- friend list
- friend presence

### Module C: Voice Talk

- room-based voice using Agora
- join, leave, mute, unmute

### Module D: Bot AI Chat

- controlled bot replies
- event-based first
- AI-assisted later if needed

## 3. Recommended Release Phases

### Phase 1: Emoji Reactions

Deliver:

- room emoji tray
- predefined emoji catalog
- socket event for emoji
- cooldown and anti-spam

### Phase 2: Text Chat

Deliver:

- room text chat
- message history
- Laravel persistence
- reconnect-safe history
- moderation basics

### Phase 3: Friend Request System

Deliver:

- send request from room player card
- send request from lobby by player ID
- accept and reject request
- friend list

### Phase 4: Friend Presence and Notifications

Deliver:

- friend online state
- friend in lobby
- friend in match
- notification center or toast events

### Phase 5: Agora Voice Talk

Deliver:

- Agora project setup
- Laravel token issue
- Unity voice controls
- room voice join and leave

### Phase 6: Bot AI Chat

Deliver:

- rule-based bot messages
- optional AI-assisted bot short replies
- moderation and throttling

## 4. Architecture Decision

### Room identity

Reuse the existing Ludo room identity already managed by:

- `node/sockets/ludoRoomSocket.js`
- Laravel `game_rooms`
- Unity `LudoV2MatchmakingBridge`

### Important rule

- chat and emoji should stay inside the same room identity
- friends system should stay app-level and separate from room state
- voice should use separate RTC transport but same room mapping
- bot AI should be generated server-side, not in Unity

## 5. Data Model Plan

### Chat table: `game_room_messages`

Recommended columns:

- `id`
- `message_uuid`
- `game_room_id`
- `game_match_id` nullable
- `user_id` nullable
- `seat_no` nullable
- `sender_type` enum: `human`, `bot`, `system`
- `message_type` enum: `text`, `system`
- `content`
- `status` enum: `visible`, `flagged`, `deleted`
- `meta` json nullable
- timestamps

### Friend request table: `friend_requests`

- `id`
- `request_uuid`
- `sender_user_id`
- `receiver_user_id`
- `status` enum: `pending`, `accepted`, `rejected`, `cancelled`, `expired`
- `source` enum: `room`, `lobby_search`, `profile`
- `source_room_uuid` nullable
- timestamps

### Friend relation table: `user_friends`

- `id`
- `user_id`
- `friend_user_id`
- `status` enum: `active`, `blocked`, `removed`
- timestamps

### Presence table: `user_presence_snapshots` optional

- `id`
- `user_id`
- `presence_state` enum: `offline`, `online`, `lobby`, `in_match`
- `game_slug` nullable
- `room_uuid` nullable
- `last_seen_at`

### Voice metadata table: `game_room_voice_sessions`

- `id`
- `game_room_id`
- `provider` enum: `agora`
- `channel_name`
- `status` enum: `active`, `closed`
- `meta` json
- `started_at`
- `ended_at`

## 6. Realtime and API Contract

### Chat socket events

Client to server:

- `ludo.chat.send`
- `ludo.chat.history`
- `ludo.chat.emoji`

Server to client:

- `ludo.chat.message`
- `ludo.chat.history`
- `ludo.chat.emoji`
- `ludo.chat.system`
- `ludo.chat.error`

### Friend events

Client to server:

- `social.friend.request.send`
- `social.friend.request.respond`

Server to client:

- `social.friend.request.received`
- `social.friend.request.updated`
- `social.friend.online`
- `social.friend.playing`

### Friend REST APIs

- `POST /api/v1/friends/request`
- `POST /api/v1/friends/request/by-player-id`
- `POST /api/v1/friends/request/{requestUuid}/accept`
- `POST /api/v1/friends/request/{requestUuid}/reject`
- `GET /api/v1/friends`
- `GET /api/v1/friends/requests`
- `GET /api/v1/users/search-by-player-id/{playerId}`

### Voice REST APIs

- `POST /api/v1/ludo/rooms/{roomUuid}/voice/join`
- `POST /api/v1/ludo/rooms/{roomUuid}/voice/leave`

Voice join response should include:

- `app_id`
- `channel_name`
- `rtc_token`
- `agora_uid`
- `expires_at`

## 7. Repo Ownership

### Node

Node should handle:

- room chat broadcast
- emoji broadcast
- bot event triggers
- room membership verification
- room presence signal to Laravel

Recommended files:

- `node/sockets/ludoRoomSocket.js`
- `node/services/ludoRoomChatService.js`
- `node/services/ludoBotChatService.js`

### Laravel

Laravel should handle:

- canonical storage
- friend graph
- notification creation
- message history
- Agora token issue
- admin moderation

Recommended services:

- `App\Services\Chat\LudoRoomChatService`
- `App\Services\Social\FriendService`
- `App\Services\Social\FriendPresenceService`
- `App\Services\Voice\AgoraVoiceService`
- `App\Services\AI\LudoBotChatService`

### Unity

Unity should handle:

- chat drawer UI
- emoji tray
- room profile popup
- friend request buttons
- lobby add-by-ID form
- voice controls
- notification UI

Primary integration point:

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoV2MatchmakingBridge.cs`

Recommended new scripts:

- `LudoRoomChatController`
- `LudoEmojiTrayController`
- `LudoFriendRequestController`
- `LudoFriendSearchController`
- `LudoVoiceController`
- `LudoSocialNotificationController`

## 8. Bot AI Chat Plan

### Strong recommendation

Do not begin with fully free-form AI chat.

Best rollout:

1. event-driven templates
2. context-aware canned variation
3. optional short AI replies later

### Bot chat triggers

- player joined
- emoji sent
- capture happened
- win-pressure moment
- timeout
- match start
- match end

### Bot chat rules

- short replies only
- playful, non-toxic tone
- no abuse
- no off-topic conversation
- no sensitive advice
- max one bot message every 10 to 15 seconds

## 9. Agora Voice Talk Plan

### Why Agora fits

For this project, Agora is a practical choice because:

- low-latency RTC
- Unity support
- room-based voice model works well
- token-based secure access is supported

### Recommended room channel strategy

Use one temporary Agora channel per active room:

- `ludo_room_{roomUuid}`
- `ludo_private_{roomUuid}`
- `ludo_tournament_{roomUuid}`

### Required Agora values

Server side:

- `App ID`
- `App Certificate`

Client side:

- `App ID`
- `RTC token`
- `channel name`
- `uid`

Important:

- keep `App Certificate` server-side only
- never ship `App Certificate` in Unity

### Agora registration and setup steps

As of April 3, 2026, Agora's official token-authentication docs recommend server-issued token flow for secure channel joins.

Recommended setup:

1. Create account in Agora Console.
2. Log in and create a new project for Ludo voice.
3. Copy the project `App ID`.
4. Keep `App Certificate` enabled for token generation.
5. Build Laravel token endpoint for room voice join.
6. Unity requests token from Laravel backend, not directly from Agora.
7. Unity joins voice channel using:
   - `App ID`
   - `channel_name`
   - `agora_uid`
   - `rtc_token`
8. On expiry warning, Unity requests a fresh token and renews it.

### What keys we need

Mandatory:

- Agora `App ID`
- Agora `App Certificate`

Generated per join:

- `RTC token`
- `channel_name`
- `agora_uid`

Not required for basic room voice:

- extra cloud-service credentials are not needed for minimum RTC voice join flow

## 10. Friend Request and Notification Plan

### In-room friend request

Every human player profile card should show:

- avatar
- display name
- player ID
- `Send Friend Request` if allowed

Rules:

- cannot friend self
- cannot send duplicate pending request
- if already friends, show `Friends`

### Lobby add-by-ID

Add a simple lobby search flow:

- enter player ID
- fetch user summary
- show `Send Friend Request`

### Presence states

Recommended states:

- `offline`
- `online`
- `lobby`
- `in_room`
- `in_match`

### Notification examples

- `Your friend Rahul is online`
- `Your friend Rahul started playing Ludo`
- `Your friend Rahul entered the lobby`

To avoid spam:

- debounce events
- user preference toggle later
- last-notified tracking

## 11. Security and Abuse Controls

### Chat

- profanity filter
- rate limits
- duplicate message suppression
- transcript storage

### Friends

- request-rate limits
- search-by-ID rate limits
- block feature later

### Voice

- secure room-membership validation before token issue
- short token expiry
- no certificate exposure

### Bot AI

- safe prompt
- post-generation filter
- fallback templates
- cooldown

## 12. Detailed Delivery Checklist

### Phase 1 Checklist: Emoji

- add emoji event constants
- add Node broadcast handler
- add Unity emoji tray
- add seat animation and cooldown

### Phase 2 Checklist: Text Chat

- create `game_room_messages`
- create internal message create API
- create history API
- add Node chat handlers
- add Unity chat panel and unread badge

### Phase 3 Checklist: Friends

- create `friend_requests`
- create `user_friends`
- create add-by-ID lookup
- add room profile friend action
- add accept and reject flow

### Phase 4 Checklist: Presence

- define presence update flow
- create notification table or event source
- add friend online and playing notification UI

### Phase 5 Checklist: Agora Voice

- create Agora project
- configure App ID and App Certificate
- build Laravel token generator
- add Unity Agora SDK integration
- add join, leave, mute, unmute UI

### Phase 6 Checklist: Bot AI Chat

- build rule-based bot response engine
- add bot chat sender pipeline
- add moderation guard
- optionally add AI-assisted short replies later

## 13. Best Actual Build Order for This Repo

Recommended engineering order:

1. Emoji
2. Text chat
3. Friend request backend
4. Friend request Unity UI
5. Presence and notifications
6. Agora token backend
7. Unity Agora voice integration
8. Bot rule-based chat
9. Optional AI-assisted bot replies

## 14. Official Agora Notes Used

Planning note based on official Agora docs checked on April 3, 2026:

- Agora token-authentication guidance recommends server-issued tokens and renewing them before expiry
- Agora Unity voice quickstart material supports the standard RTC flow of getting token and joining channel from Unity

Official references:

- Token authentication: `https://docs.agora.io/en/voice-calling/token-authentication/authentication-workflow`
- Unity voice quickstart: `https://docs.agora.io/en/voice-calling/get-started/get-started-sdk?platform=unity`

Inference from those official docs:

- Laravel should act as the token server
- room voice channels should be short-lived
- Unity should never hold the App Certificate

## 15. Final Recommendation

The safest and most maintainable rollout is:

1. first emoji and text chat
2. then friends and friend search by ID
3. then friend online/play notifications
4. then Agora voice
5. then bot AI chat

This order gives faster progress, easier QA, and lower risk.
