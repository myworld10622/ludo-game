# Tournament System Specification

## Goal

Build a game-wise tournament platform that supports:

- separate tournaments per game, such as Ludo and Teen Patti
- multiple concurrent tournaments for the same game
- ticket-based joins
- multiple entries by the same user into the same tournament
- configurable prize distribution
- scheduled start, join lock, and completion flow
- admin control over tournament lifecycle
- wallet hold, capture, refund, and payout support

## Core Principles

- Tournament core must be game-agnostic.
- Match execution must be game-specific.
- Wallet actions must be auditable.
- Tournament state transitions must be explicit and idempotent.
- Multiple entries by the same user must be tracked entry-by-entry, not user-by-user.

## Supported Concepts

### Game-wise tournaments

Each tournament belongs to one game:

- `ludo`
- `teen-patti`
- future games can be added later

### Multiple concurrent tournaments

The system must allow:

- many active Ludo tournaments at the same time
- many active Teen Patti tournaments at the same time
- independent prize pools, timings, and entry rules

### Multiple entries per user

A user can join the same tournament multiple times if the tournament allows re-entry.

Each join creates a separate tournament entry with its own:

- entry number
- ticket number
- wallet transaction references
- progression state

## Tournament Status Lifecycle

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

## Tournament Timing Rules

Each tournament needs:

- `entry_open_at`
- `entry_close_at`
- `start_at`
- optional `end_at`
- optional `late_join_grace_seconds`

Rules:

- users can join only between `entry_open_at` and `entry_close_at`
- by default, no joins after `entry_close_at`
- tournament moves to `seeding` at or just after entry lock
- tournament moves to `running` when game-specific match creation begins

## Ticket and Entry Rules

Each tournament has:

- `entry_fee`
- `currency`
- `ticket_prefix`
- `max_entries_per_user`
- `max_total_entries`
- `min_total_entries`
- `allow_multiple_entries`

Each successful entry gets:

- `entry_no`
- `ticket_no`

Ticket example:

- `LUDO-MAR14-000001`

## Prize Rules

Each tournament supports configurable prizes by rank.

Examples:

- rank 1: fixed amount
- rank 2: fixed amount
- rank 3: fixed amount
- rank 1-10: percentage split

Each prize rule needs:

- `rank_from`
- `rank_to`
- `prize_type`
- `prize_amount`
- `prize_percent`
- `meta`

## Wallet Rules

### Join

On join:

- create wallet hold for the entry fee
- one hold per tournament entry

### Start

On tournament confirmation:

- capture held entry fees, or keep them in reserved state until rules require capture

### Cancel

On cancellation:

- refund all held or captured entries according to cancellation policy

### Complete

On completion:

- credit winners based on prize rules
- store payout transaction references

## Admin Features

Admin must be able to:

- create tournament
- select game
- configure entry fee
- configure ticket prefix
- configure multiple entry rules
- configure entry windows
- configure start time
- configure min and max entries
- configure prize distribution
- publish and unpublish
- lock entries
- cancel tournament
- force seeding
- inspect entries
- inspect payouts and refunds

## User Features

Users must be able to:

- list tournaments by game
- see tournament details
- join tournament
- join multiple times if allowed
- view all their entries
- view leaderboard and results
- see start time, join close time, and prize structure

## Execution Model

Tournament core decides:

- who can join
- how many entries exist
- when entry closes
- when tournament starts
- prize structure
- settlement

Game adapter decides:

- how entries are grouped into matches
- how winners advance
- how eliminations work
- how final ranking is produced

### Ludo adapter

Ludo tournaments should support:

- grouping entries into 2-player or 4-player matches
- bot policy configurable per tournament
- round progression or points leaderboard depending on tournament type

### Teen Patti adapter

Teen Patti tournaments should support:

- table assignment
- advancement rules
- final leaderboard generation

## Recommended First Release

Phase 1 release should support:

- admin tournament CRUD
- game-wise tournaments
- multiple concurrent tournaments
- multiple entries per user
- ticket numbers
- fixed prize distribution
- entry windows
- wallet hold and refund
- Ludo tournament entry and match-link foundation

## Data Model

### tournaments

Core tournament definition.

### tournament_prizes

Prize rows by rank range.

### tournament_entries

One row per join attempt / accepted ticket.

### tournament_match_links

Links tournament entries to underlying game matches or room UUIDs.

### tournament_entry_results

Stores final ranking, payouts, and elimination details per entry.

## Validation Rules

- `entry_close_at` must be before or equal to `start_at`
- `max_entries_per_user` must be at least 1
- if `allow_multiple_entries` is false, `max_entries_per_user` must be 1
- `min_total_entries` cannot exceed `max_total_entries`
- prize rows must not overlap

## Operational Checklist

### Admin create checklist

- game selected
- tournament type selected
- entry window configured
- start time configured
- re-entry rules configured
- prize rules added
- status set to `published`

### Runtime checklist

- entry opens at correct time
- entries lock at correct time
- seeding starts correctly
- underlying matches created
- entry states advance correctly
- ranking stored correctly
- payouts created correctly

### Settlement checklist

- cancelled tournament refunds all entries
- completed tournament credits winners
- audit links exist for every hold/capture/refund/payout

## Implementation Phases

### Phase 1

- schema
- models
- rules service
- documentation

### Phase 2

- admin CRUD
- prize configuration
- publish / cancel / lock flows

### Phase 3

- user join APIs
- multiple entries
- ticket generation
- wallet holds

### Phase 4

- game adapter integration
- match linking
- tournament result recording
- payouts

### Phase 5

- leaderboard
- reporting
- reconciliation
