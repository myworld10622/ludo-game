# Ludo Large Tournament Architecture

## Goal

Define the recommended production architecture for real-money or large-scale Ludo tournaments that support:

- `2-player` and `4-player` match formats
- `1000+` and `10000+` total entries
- multiple tournament entries per user
- multiple concurrent tournaments per game
- automatic round progression
- absent-player bot fill
- Unity + Node + Laravel integration

This document is the recommended standard for future implementation.

## Recommended Standard

### Primary competition unit

The primary competitor is a `tournament entry`, not a `user`.

That means:

- one user can own many entries in the same tournament
- each entry is treated as an independent bracket participant
- entries from the same user can face each other
- every entry has its own progression, elimination, ranking, and payout state

Example:

- `Player A` buys `10` entries in `Gold Tournament`
- `Player A` buys `2` entries in `Silver Tournament`

The system must treat those `12` entries as `12` separate competitors.

### Tournament formats

Each tournament must declare:

- `match_size`
  - `2`
  - `4`
- `advance_count`
  - recommended default: `1`
- `bracket_type`
  - recommended: `single_elimination`

Recommended production rule:

- `2-player`: top `1` advances
- `4-player`: top `1` advances

This keeps bracket math clean and scalable.

## Why This Standard

For large tournaments, the cleanest operational model is:

- entry-based knockout bracket
- fixed match size per tournament
- fixed advance count per match
- round progression only after full current-round completion

This avoids mixed semantics and keeps backend, socket, and client flows deterministic.

## Supported Tournament Shapes

### 2-player tournaments

Use bracket sizes that are powers of `2`.

Examples:

- `2`
- `4`
- `8`
- `16`
- `32`
- `64`
- `128`
- `256`
- `512`
- `1024`
- `2048`
- `4096`
- `8192`
- `16384`

### 4-player tournaments

Use bracket sizes that are powers of `4`.

Examples:

- `4`
- `16`
- `64`
- `256`
- `1024`
- `4096`
- `16384`

## Non-Power Bracket Sizes

Real tournaments like `500`, `1000`, or `10000` entries usually do not match exact bracket sizes.

Recommended standard:

- map real entries into the next valid bracket size
- fill remaining bracket slots using `byes`

Examples:

- `500 entries`, `2-player`
  - next valid bracket size = `512`
  - `12 byes`

- `1000 entries`, `4-player`
  - next valid bracket size = `1024`
  - `24 byes`

- `10000 entries`, `4-player`
  - next valid bracket size = `16384`
  - `6384 byes`

This is standard tournament bracket behavior and is strongly recommended over ad hoc pairing rules.

## Core Product Rules

### Entry rules

Each tournament must define:

- `allow_multiple_entries`
- `max_entries_per_user`
- `min_total_entries`
- `max_total_entries`
- `entry_fee`
- `currency`
- `ticket_prefix`

Each successful join creates:

- one `tournament entry`
- one `ticket number`
- one wallet hold or reserve record

### Same user in multiple tournaments

One user may participate in many tournaments at the same time.

Example:

- `Gold Tournament`
  - `1000 entries`
  - `4-player`
  - `Player A` has `10` entries
- `Silver Tournament`
  - `500 entries`
  - `2-player`
  - `Player A` has `2` entries

This is fully valid.

### Same user with many entries in one tournament

One user may also have many active entries inside the same tournament.

Recommended rule:

- every entry remains independent
- no entry-merging by user
- no special protection from self-collision in bracket unless explicitly configured

## Tournament Lifecycle

### Tournament statuses

- `draft`
- `published`
- `entry_open`
- `entry_locked`
- `seeding`
- `running`
- `completed`
- `cancelled`
- `settlement_pending`

### Entry statuses

- `joined`
- `checked_in`
- `seeded`
- `running`
- `eliminated`
- `advanced`
- `winner`
- `refunded`
- `cancelled`

### Match statuses

- `queued`
- `assigned`
- `running`
- `completed`
- `cancelled`
- `bye`

## Real Flow

### 1. Tournament creation

Admin creates tournament with:

- game = `ludo`
- match size = `2` or `4`
- advance count = `1`
- total slot target
- entry fee
- multiple entry rules
- start time
- bot rules
- prize rules

### 2. Entry phase

Users buy entries.

Each entry gets:

- `entry_uuid`
- `ticket_no`
- `user_id`
- `tournament_id`
- `entry_status = joined`

### 3. Entry lock

At `entry_close_at`:

- no new joins
- entries freeze
- if below minimum entries:
  - cancel tournament
  - refund all valid entries

### 4. Bracket generation

At seeding time:

- compute bracket size
- assign byes if needed
- create round 1 matches

### 5. Match room provisioning

For each active match:

- create game room
- map entries into seats
- expose claim-room API per entry

### 6. Match execution

Each entry claims its assigned room.

If one or more required players do not connect:

- wait until timeout
- replace absent seats with bots if bot fill is enabled

### 7. Match result settlement

After match complete:

- final ranks are stored per entry
- winners are marked
- losers are eliminated
- current match is marked completed

### 8. Round completion

Only after every match in the current round is resolved:

- collect advancing entries
- create next round matches
- provision next round rooms

### 9. Final completion

When last round completes:

- final winner is declared
- leaderboard is finalized
- payout settlement runs
- tournament status becomes `completed`

## Bot Policy

Recommended standard:

- bots are `seat fillers`, not first-class tournament entries
- bots are only created to unblock a specific match when real players do not connect in time

Recommended behavior:

- original entry remains the competitor record
- bot is only runtime execution help for that seat
- settlement still applies to the original entry

This lets large tournaments continue without stalling on no-shows.

## Bye Policy

Recommended standard:

- bye is a bracket-level auto-advance
- bye is not a bot
- bye means the entry advances without playing that round

Examples:

- `2-player`, `500 entries`
  - `12` byes
- `4-player`, `1000 entries`
  - `24` byes

Byes should be visible in bracket metadata and auditable.

## Scheduling Policy

Large tournaments require explicit scheduling rules.

Recommended standard:

- rounds are processed in batches
- next round opens only after previous round fully resolves

For users with many entries:

- a user may have multiple active entries in the same round
- entries can claim independently
- room claim windows should be short and deterministic

Recommended operational rule:

- do not guarantee one live room at a time per user
- allow overlapping entry responsibilities
- use bot fill when user cannot attend one of the simultaneous matches

This is simpler and scales better than trying to serialize all matches per user.

## Prize Standard

Prize calculation should be driven by final entry ranking.

Recommended:

- store final placement per entry
- support rank ranges
- support fixed payout or percentage payout

Examples:

- rank `1` = fixed amount
- rank `2` = fixed amount
- rank `3-10` = prize split

Optional:

- aggregate a per-user summary for UI
- keep entry-level settlement as the source of truth

## Recommended Data Model

### Core tournament tables

- `tournaments`
- `tournament_entries`
- `tournament_prizes`
- `tournament_entry_results`

### Recommended bracket tables

- `tournament_matches`
- `tournament_match_entries`

### Optional bracket helper metadata

- `tournament_rounds`
- `tournament_bracket_slots`

## Strong Recommendation For Implementation

Use explicit `TournamentMatch` and `TournamentMatchEntry` as the primary scalable execution model.

Why:

- easier to support `2-player` and `4-player`
- easier to support byes
- easier to support large brackets
- easier to audit rounds and rooms
- easier to support remap, replay, and partial retries

## Why Current MatchLink Model Is Not Enough Long-Term

Current `TournamentMatchLink` is workable for the current smaller prototype, but it becomes fragile for:

- `1000+` entries
- complex round progression
- bye handling
- re-seeding edge cases
- audit/history per round
- large bracket administration

Recommended direction:

- keep `TournamentMatchLink` only as a temporary compatibility layer
- move production logic toward `TournamentMatch` + `TournamentMatchEntry`

## Example: Gold Tournament

- tournament name = `Gold Tournament`
- total entries = `1000`
- match size = `4`
- advance count = `1`
- bracket size = `1024`
- byes = `24`

Player A buys `10` entries.

Behavior:

- all `10` entries are placed independently
- some entries may receive byes
- some entries may be seeded into round 1 matches
- each winning entry can continue independently
- some of Player A's entries may meet each other in later rounds

## Example: Silver Tournament

- tournament name = `Silver Tournament`
- total entries = `500`
- match size = `2`
- advance count = `1`
- bracket size = `512`
- byes = `12`

Player A buys `2` entries.

Behavior:

- both entries are separate
- either or both may receive byes
- both may progress independently

## Unity / Node / Laravel Runtime Standard

### Laravel responsibilities

- tournament lifecycle
- entry validation
- bracket generation
- room claim authority
- round progression
- settlement
- payout

### Node responsibilities

- room runtime
- seat presence
- bot fill
- match start
- match result emission

### Unity responsibilities

- tournament listing UI
- entry join action
- claim-room per active entry
- wait state
- gameplay
- result handling
- automatic next-round reclaim for winning entries

## Current Codebase Gap Summary

### Good parts already present

- tournament creation and entry foundation exists
- multiple entries per user are supported at the data level
- current Ludo round progression prototype works for small multi-round cases
- bot fill now works for absent seats
- Unity next-round reclaim prototype exists

### Major gaps for production large tournaments

#### 1. Bracket engine is not explicit enough

Current logic is still centered around `TournamentMatchLink` and room-provision-per-round behavior.

For real scale, we need:

- explicit `TournamentMatch`
- explicit `TournamentMatchEntry`
- bracket-size calculation
- bye generation
- persistent round history

#### 2. Bye support is missing

Current implementation does not support bracket byes as a first-class concept.

#### 3. Match size is not fully tournament-driven

Current Ludo room provisioning still assumes behavior closer to current prototype.

We need:

- tournament-level `match_size`
- tournament-level `advance_count`
- room creation based on those settings

#### 4. Multiple active entries per user need stronger scheduling policy

Current system can function, but large-scale operational behavior is not formally codified.

#### 5. Room reprovisioning and round replay need explicit idempotency

Some recent fixes were needed because room reprovisioning touched completed links.

Production system should have stricter safeguards.

#### 6. Final per-round audit trail is incomplete

`TournamentEntryResult` currently behaves more like final/latest state than full round-by-round history.

## Recommended Next Implementation Steps

### Phase A

- add `match_size` and `advance_count` as explicit tournament configuration fields
- add bracket-size and bye calculation service

### Phase B

- move Ludo progression to `TournamentMatch` + `TournamentMatchEntry`
- keep `TournamentMatchLink` only as compatibility or migration bridge

### Phase C

- implement first-class bye handling
- implement round generation from winners and byes

### Phase D

- align Node room provisioning to `TournamentMatch`
- align Unity claim-room flow to active `TournamentMatchEntry`

### Phase E

- add admin bracket inspection
- add round history and user-facing tournament progress screens

## Final Recommendation

For real tournaments with:

- `1000`
- `10000`
- multiple entries per user
- both `2-player` and `4-player` modes

the production path should be:

- entry-based bracket system
- explicit tournament match entities
- explicit bye support
- tournament-configurable `match_size`
- tournament-configurable `advance_count`
- round-complete then next-round-seed model

This is the recommended standard to implement from this point onward.
