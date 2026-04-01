# Ludo Test Report - 01 Apr 2026

## Scope

Is report me backend/runtime level par ye flows verify kiye gaye:

- normal classic Ludo 2-player fee table flow
- normal classic Ludo 4-player fee table flow
- 4-player tournament simulation
- 16-player tournament simulation

Important:

- Unity GUI par real manual touch/click play yahan se perform nahi kiya gaya
- dice animation, pawn tap UX, aur multi-device visual sync ka final signoff abhi manual QA se hi hoga
- backend room join, match start, completion, payout, aur tournament progression verify kiya gaya

## Gameplay QA Logs Added

Unity gameplay flow me targeted QA logs add kiye gaye hain:

- [SocketNumberEventReceiverOffline.cs](D:/Live-Code/games/unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/SocketNumberEventReceiverOffline.cs)
- [LudoNumberGsNewOffline.cs](D:/Live-Code/games/unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Playing/LudoNumberGsNewOffline.cs)
- [CoockieMovementOffline.cs](D:/Live-Code/games/unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Playing/CoockieMovementOffline.cs)

New console log prefix:

- `[LUDO-QA]`

Expected logged events:

- turn start
- move request
- move complete
- cut event
- killed token reset
- turn change
- battle result summary

## Test 1: Classic Ludo 2 Player Fee Table

### Setup

- mode: classic cash room
- max players: 2
- fee: `₹100`
- users:
  - `simuser1`
  - `simuser2`

### Observed Result

- both users same room me join hue
- room status initially `waiting`
- total players `2`
- real players `2`
- seats:
  - seat 1: `simuser1`
  - seat 2: `simuser2`
- room uuid:
  - `4ced566b-d17c-44de-a99e-e3109575bc2b`

### Match Result

- match created and completed
- match uuid:
  - `65099694-4e80-40d5-ab8f-3bf1b4176c0a`
- room status final:
  - `completed`
- prize pool:
  - `₹200`

### Status

- pass

## Test 2: Classic Ludo 4 Player Fee Table

### Setup

- mode: classic cash room
- max players: 4
- fee: `₹500`
- users:
  - `simuser3`
  - `simuser4`
  - `simuser5`
  - `simuser6`

### Observed Result

- all 4 users same room me join hue
- room status initially `waiting`
- total players `4`
- real players `4`
- seats:
  - seat 1: `simuser3`
  - seat 2: `simuser4`
  - seat 3: `simuser5`
  - seat 4: `simuser6`
- room uuid:
  - `ec07e011-70b6-408b-8c20-a8f8bb11bafe`

### Match Result

- match created and completed
- match uuid:
  - `3cc5bac8-e497-485d-b53d-f08e76bc835e`
- room status final:
  - `completed`
- prize pool:
  - `₹2000`

### Status

- pass

## Test 3: 4 Player Tournament

### Command

```powershell
php artisan tournament:simulate --players=4 --real-users=4 --per-match=4 --fee=100 --creator-user=abc4943161
```

### Result

- tournament id: `27`
- owner: `abc4943161`
- players: `4`
- match size: `4`
- status: `completed`
- completed matches: `1`
- pending matches: `0`

### Match Summary

- round 1 match 1:
  - `simuser1(score:55)`
  - `simuser4(score:71)` winner
  - `simuser2(score:36)`
  - `simuser3(score:15)`

### Final Rank

- 1st: `simuser4`
- 2nd: `simuser1`
- 3rd: `simuser2`
- 4th: `simuser3`

### Prize Payout

- 1st: `₹192`
- 2nd: `₹96`
- 3rd: `₹32`

### Validation

- all matches completed
- prize pool fully paid out
- tournament marked completed

### Status

- pass

## Test 4: 16 Player Tournament

### Command

```powershell
php artisan tournament:simulate --players=16 --real-users=8 --per-match=4 --fee=50 --creator-user=abc4943161
```

### Result

- tournament id: `26`
- owner: `abc4943161`
- players: `16`
- real users: `8`
- bots: `8`
- match size: `4`
- status: `completed`
- total matches: `5`
- pending matches: `0`

### Final Rank

- 1st: `simuser1`
- 2nd: `simuser2`
- 3rd: `simuser3`

### Prize Payout

- 1st: `₹384`
- 2nd: `₹192`
- 3rd: `₹64`

### Validation

- all matches completed
- prize pool fully paid out
- tournament status completed

### Status

- pass

## Findings

### Good

- classic fee-table room creation working
- 2P and 4P cash room matching working
- match start and completion working
- room completed state working
- tournament registration to completion flow working
- prize payout and final tournament completion working

### Gap

- real Unity GUI manual match not yet executed here
- pawn-level live observation report still manual-device QA se hi banega
- `tournaments:smoke-runtime` helper command current schema se outdated hai aur alag se fix karna chahiye

## Recommended Next Manual QA

Ab next real-device test me ye document use karo:

- [ludo-qa-checklist-hinglish.md](D:/Live-Code/games/backend_laravel/docs/ludo-qa-checklist-hinglish.md)

Specially run:

- one full 4-player real match with screen recording
- one tournament room claim match with 4 real players
- one reconnect scenario
- one cut-heavy scenario

## Final Conclusion

Backend/runtime level par current Ludo aur tournament engine stable lag raha hai for:

- fee-table matching
- room fill
- match completion
- tournament completion
- payout settlement

Manual final gameplay signoff ab `[LUDO-QA]` logs aur real-device checklist ke saath karna recommended hai.
