# Unity API Contract

Base URL: `/api/v1`

All Unity-facing APIs return the same envelope:

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {},
  "errors": null
}
```

Validation or domain failures return:

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "field": ["Reason"]
  }
}
```

## Auth

### POST `/auth/register`

Creates a user and returns a Sanctum token plus user profile.

Example success:

```json
{
  "success": true,
  "message": "Registration completed successfully.",
  "data": {
    "token": "1|plain-text-token",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "uuid": "user-uuid",
      "username": "demo_player",
      "email": "demo@example.com",
      "mobile": "9999999999",
      "referral_code": "ABCD1234",
      "is_active": true,
      "is_banned": false,
      "last_login_at": null,
      "created_at": "2026-03-12T12:00:00Z",
      "profile": {
        "first_name": "Demo",
        "last_name": "Player",
        "date_of_birth": null,
        "gender": null,
        "country_code": "IN",
        "state": null,
        "city": null,
        "avatar_url": null,
        "language": "en",
        "preferences": null
      }
    }
  },
  "errors": null
}
```

### POST `/auth/login`

Request fields:
- `identity`
- `password`
- `device_name` optional

Response shape is the same as register.

### POST `/auth/logout`

Requires bearer token.

### GET `/me`
### GET `/me/profile`

Returns the authenticated user resource.

## App Config

### GET `/app-config`

Returns app bootstrap data used by Unity startup.

Example success:

```json
{
  "success": true,
  "message": "App configuration fetched successfully.",
  "data": {
    "app_version": "1.0.0",
    "enabled_games": [
      {
        "code": "ludo",
        "name": "Ludo",
        "slug": "ludo",
        "launch_type": "node_room"
      }
    ],
    "maintenance": {
      "api_enabled": false,
      "gameplay_enabled": false
    },
    "features": {
      "tournaments_enabled": true
    },
    "tournament_feature_availability": true
  },
  "errors": null
}
```

## Games

### GET `/games`

Returns visible and active games only.

Game resource:

```json
{
  "id": 1,
  "code": "ludo",
  "name": "Ludo",
  "slug": "ludo",
  "description": "Core multiplayer board game.",
  "is_active": true,
  "is_visible": true,
  "tournaments_enabled": true,
  "sort_order": 1,
  "launch_type": "node_room",
  "client_route": "ludo",
  "socket_namespace": "/ludo",
  "icon_url": null,
  "banner_url": null,
  "metadata": {
    "seeded": true
  },
  "published_at": "2026-03-12T12:00:00Z"
}
```

## Home

### GET `/home`

Returns home screen bootstrap data.

Example `data`:

```json
{
  "visible_games": [],
  "wallet_summary": {
    "balance": "0.0000",
    "locked_balance": "0.0000",
    "currency": "INR"
  },
  "shortcuts": {
    "deposit_enabled": true,
    "withdraw_enabled": true,
    "history_enabled": true,
    "rewards_enabled": true,
    "support_enabled": true
  }
}
```

## Wallet

### GET `/wallet`

Requires bearer token.

Wallet resource:

```json
{
  "id": 1,
  "wallet_type": "cash",
  "currency": "INR",
  "balance": "500.0000",
  "locked_balance": "0.0000",
  "is_active": true,
  "last_transaction_at": "2026-03-12T12:00:00Z"
}
```

### GET `/wallet/history`

Requires bearer token.

Wallet transaction resource:

```json
{
  "id": 11,
  "transaction_uuid": "txn-uuid",
  "type": "debit",
  "direction": "debit",
  "status": "completed",
  "reference_type": "App\\Models\\Tournament",
  "reference_id": 5,
  "opening_balance": "500.0000",
  "amount": "50.0000",
  "closing_balance": "450.0000",
  "currency": "INR",
  "description": "Tournament entry fee",
  "processed_at": "2026-03-12T12:00:00Z",
  "game": {
    "id": 1,
    "code": "ludo",
    "name": "Ludo"
  },
  "tournament": {
    "id": 5,
    "name": "Ludo Daily Cup",
    "code": "LUDO-DAILY-001"
  },
  "meta": {
    "entry_no": 1
  }
}
```

## Tournaments

### GET `/tournaments`

Public list endpoint. Supports optional `game_id` and `per_page`.

### GET `/tournaments/{tournament}`

Tournament resource:

```json
{
  "id": 5,
  "game": {
    "id": 1,
    "code": "ludo",
    "name": "Ludo",
    "slug": "ludo",
    "description": "Core multiplayer board game.",
    "is_active": true,
    "is_visible": true,
    "tournaments_enabled": true,
    "sort_order": 1,
    "launch_type": "node_room",
    "client_route": "ludo",
    "socket_namespace": "/ludo",
    "icon_url": null,
    "banner_url": null,
    "metadata": {
      "seeded": true
    },
    "published_at": "2026-03-12T12:00:00Z"
  },
  "code": "LUDO-DAILY-001",
  "name": "Ludo Daily Cup",
  "slug": "ludo-daily-cup",
  "status": "published",
  "visibility": "public",
  "tournament_type": "knockout",
  "entry_fee": "50.0000",
  "max_entries_per_user": 2,
  "max_total_entries": 128,
  "registration_starts_at": "2026-03-12T10:00:00Z",
  "registration_ends_at": "2026-03-12T11:55:00Z",
  "starts_at": "2026-03-12T12:00:00Z",
  "ends_at": null,
  "prize_pool": "1000.0000",
  "currency": "INR",
  "prize_slabs": [
    {
      "id": 1,
      "rank_from": 1,
      "rank_to": 1,
      "prize_type": "cash",
      "prize_amount": "500.0000",
      "currency": "INR"
    }
  ],
  "settings": null,
  "metadata": null
}
```

### POST `/tournaments/{tournament}/join`

Requires bearer token.

Returns a tournament entry resource.

### GET `/tournaments/me/entries`

Requires bearer token.

### GET `/tournaments/{tournament}/leaderboard`

Returns tournament entry resources ordered by rank.

Tournament entry resource:

```json
{
  "id": 21,
  "entry_uuid": "entry-uuid",
  "entry_no": 1,
  "status": "registered",
  "final_rank": null,
  "entry_fee": "50.0000",
  "prize_amount": "0.0000",
  "checked_in_at": null,
  "eliminated_at": null,
  "user": {
    "id": 7,
    "username": "demo_player"
  },
  "tournament": {}
}
```

## Health

### GET `/health`

Simple envelope-wrapped health check.
