# Ludo QA Checklist Hinglish

## Purpose

Yeh document manual QA aur supervised live testing ke liye bana hai. Iska goal hai:

- normal classic Ludo fee-table play verify karna
- 2 Player aur 4 Player rooms ka real flow test karna
- tournament registration se lekar match completion tak full flow verify karna
- dice, pawn movement, cut, safe zone, home entry, finish order aur stuck-state ko properly observe karna
- 4-player ek full match ko event-by-event record karna

## Important Note

Is checklist ka use real device testing ke liye hai. Backend simulation alag cheez hai. Yahan hum actual gameplay behavior check karenge:

- user join
- fee deduct
- room fill
- dice roll
- turn timer
- pawn move
- pawn cut
- extra turn
- home reach
- winner order
- disconnect / reconnect
- match stuck ya freeze

## Suggested Test Setup

Recommended:

- 4 real users
- 4 alag devices ya 4 separate test sessions
- stable internet
- same app version
- same backend + node server
- screen recording ON
- server logs open:
  - Laravel log
  - Node socket log
  - Unity console if testing in editor

Suggested test accounts:

- User A
- User B
- User C
- User D

Suggested seat mapping:

- Seat 1 = User A
- Seat 2 = User B
- Seat 3 = User C
- Seat 4 = User D

## Core Pass Criteria

Test pass tab mana jayega jab:

- fee-table join sahi ho
- wallet se correct fee deduct ya hold ho
- correct room fill ho
- match start ho bina stuck hue
- turn order logical ho
- dice roll aur pawn movement sync me ho
- cut event sahi trigger ho
- extra turn rule sahi chale
- home entry aur final target reach sahi ho
- finish rank sahi aaye
- result screen sahi dikhe
- match status backend me completed ho

## Section 1: Login And Lobby Smoke Check

Checklist:

- user login successfully karta hai
- homepage par sirf enabled game hi dikhta hai
- Ludo icon visible hai
- disabled games visible nahi hain
- classic Ludo open hota hai
- `2 Player`, `Tournament`, `4 Player` tabs proper color state me kaam karte hain
- admin hidden classic fee tables UI me visible nahi honi chahiye
- `Pass N Play` table agar design ke hisaab se allowed hai to visible hona chahiye

Expected:

- koi extra game flash nahi hona chahiye
- table list admin configuration ke hisaab se hi dikhni chahiye

## Section 2: Normal Fee Table QA

### 2 Player Table QA

Use any active fee table, example `₹100`.

Checklist:

- User A table join kare
- wallet se fee hold/deduct verify kare
- User B same fee table join kare
- room same hona chahiye
- 2 seats fill honi chahiye
- match auto start ya expected flow ke hisaab se start hona chahiye
- game complete hone ke baad:
  - winner correct
  - result screen correct
  - room status completed
  - wallet settlement correct

Observe:

- join ke baad player duplicate room me na jaye
- wrong fee room me na jaye
- start ke time hang na ho

### 4 Player Table QA

Use any active fee table, example `₹500`.

Checklist:

- User A join
- User B join
- User C join
- User D join
- same room me 4 seats fill honi chahiye
- match start hona chahiye
- dice aur token movement sab users ke liye consistent hona chahiye
- match complete hona chahiye bina freeze hue

Observe:

- kisi user ko blank board na mile
- seat duplication na ho
- kisi ek user ka turn skip ya repeat galat na ho
- token move ke baad board state sabme same rahe

## Section 3: Tournament QA

### Tournament Registration Flow

Checklist:

- tournament list open hoti hai
- `registration_open` tournament par details popup open hota hai
- popup me:
  - entry fee
  - prize pool
  - registration close
  - start time
  - play slots
  visible hone chahiye
- `Register` par click karne par fee deduct ho
- user successfully registered dikhe
- duplicate registration blocked ho if not allowed

### Tournament Waiting And Claim Flow

Checklist:

- registered user tournament detail me apni state dekh sake
- registration close ke baad correct state aaye
- `in_progress` me room claim flow chale
- user ko sahi room/tournament match mile
- non-registered user ko access na mile

### Tournament Match Flow

Checklist:

- seeded match me correct players hi aayen
- play slot ke andar join allowed ho
- play slot ke bahar expected restriction lage
- no-show policy expected behavior de
- result settle ho
- next round ke liye qualified player hi advance ho
- final tournament report me:
  - running matches
  - completed matches
  - winners
  - prize details
  sahi dikhen

## Section 4: 4-Player Manual Match Observation

Yeh sabse important section hai. Isko real full match me fill karna hai.

### Match Header

- Match Date:
- Match Time Start:
- Match Time End:
- Room UUID:
- Match UUID:
- Mode:
- Fee:
- Device Count:
- App Version:
- Server Build:

### Player Mapping

- Seat 1:
- Seat 2:
- Seat 3:
- Seat 4:

### Pawn Naming Rule

Har player ke 4 pawns ko manually naam do:

- Seat 1: A1, A2, A3, A4
- Seat 2: B1, B2, B3, B4
- Seat 3: C1, C2, C3, C4
- Seat 4: D1, D2, D3, D4

## Section 5: Turn By Turn Match Log Template

Har turn par yeh line fill karo:

| Turn No | Seat | Username | Dice | Pawn Moved | From | To | Cut Kiya? | Kisko Cut Kiya | Safe Zone? | Home Enter? | Extra Turn? | Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | 1 | User A | 6 | A1 | Base | Start | No | - | No | No | Yes | Pawn nikla |
| 2 | 1 | User A | 3 | A1 | Start | +3 | No | - | No | No | No | Normal move |

Notes me likhna:

- network lag
- animation delay
- wrong move suspicion
- pawn visual mismatch
- turn freeze
- duplicate dice
- timer issue

## Section 6: Pawn Summary Sheet

Har pawn ka end summary maintain karo:

| Pawn | Total Moves | Kitni Bar Cut Kiya | Kitni Bar Cut Hua | Safe Zone Visits | Home Reached? | Reach Order | Notes |
|---|---|---|---|---|---|---|---|
| A1 | 12 | 1 | 0 | 2 | Yes | 1 | Smooth |
| A2 | 9 | 0 | 1 | 1 | No | - | Mid-game cut |

Yeh 16 pawns ke liye fill karo:

- A1, A2, A3, A4
- B1, B2, B3, B4
- C1, C2, C3, C4
- D1, D2, D3, D4

## Section 7: Critical Gameplay Events To Record

Har match me specially in events ko note karo:

- pawn base se kab nikla
- 6 aane par extra turn mila ya nahi
- ek turn me multiple move options aaye to sahi selection hua ya nahi
- opponent pawn cut hua ya nahi
- cut ke baad target pawn base me gaya ya nahi
- safe cell par cut prevent hua ya nahi
- home stretch entry sahi hui ya nahi
- exact count se home entry rule sahi tha ya nahi
- koi pawn target/home tak pahucha to system ne sahi count kiya ya nahi
- player ke 4 pawns complete hone par final rank sahi assign hui ya nahi

## Section 8: Finish Order Tracking

Match end par yeh fill karo:

- 1st Finish:
- 2nd Finish:
- 3rd Finish:
- 4th Finish:

Aur har player ke liye:

- total pawns home reached:
- total cuts made:
- total times cut by others:
- final score shown:
- rank shown:

## Section 9: Bug Checklist For Stuck Or Wrong Behavior

Har match me yeh verify karo:

- board load me freeze?
- player join ke baad black/blank screen?
- wrong seat assignment?
- wrong username/avatar?
- dice roll ke baad no move?
- pawn tap karne par response nahi?
- wrong pawn auto move?
- cut hua but board par old pawn dikhta raha?
- same turn do baar?
- turn skip ho gaya?
- timer negative ya stuck?
- winner screen nahi aayi?
- result aaya but backend completed nahi hua?

## Section 10: Socket/Sync Behavior Checklist

4 real player test me especially note karo:

- sab users ko same dice value dikh rahi hai?
- sab users ko same pawn position dikh rahi hai?
- cut event sab screens par same moment par hua?
- kisi user ko delayed board state to nahi mili?
- reconnect ke baad board recover hua?
- finish order sab devices par same dikh rahi hai?

## Section 11: Recommended Real Test Runs

Run 1:

- 2 Player
- fee table cash
- fast smoke completion

Run 2:

- 4 Player
- fee table cash
- full manual recording

Run 3:

- tournament registration_open
- 4 players register
- slot ke andar play
- full report verify

Run 4:

- tournament no-show / delayed join scenario

Run 5:

- reconnect during active turn

## Section 12: Suggested Video Recording Method

Best method:

- ek main screen record poore match ki
- har device par short clip ya at least screenshots lo on:
  - join
  - room full
  - first move
  - first cut
  - first pawn home
  - last pawn home
  - result screen

## Section 13: Useful Technical Event Names

Codebase me yeh gameplay events relevant lage:

- `USER_TURN_START`
- `DICE_ANIMATION_STARTED`
- `MOVE_TOKEN`
- `TURN_MISSED`
- `BATTLE_FINISH`

Reference:

- [LudoNumberEventListOffline.cs](D:/Live-Code/games/unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoNumberEventListOffline.cs)

## Section 14: Final QA Signoff Format

Match QA Signoff:

- Test Type:
- Date:
- Tested By:
- Devices Used:
- Players:
- Match Completed:
- Rank Verified:
- Wallet Verified:
- Dice Verified:
- Pawn Cut Verified:
- Home Entry Verified:
- Sync Verified:
- Tournament Report Verified:
- Bugs Found:
- Severity:
- Pass / Fail:

## Quick Conclusion

Agar aapko game ka exact proper behavior samajhna hai, to sabse useful artifact yeh honge:

- full 4-player recorded match
- turn-by-turn sheet
- pawn summary sheet
- final result + backend report comparison

Is document ka use karke aap easily dekh paoge:

- kitni baar kisi pawn ne cut kiya
- kitni baar khud cut hua
- kaunsa pawn pehle target/home gaya
- kaun first, second, third, fourth finish hua
- kahin koi stuck, sync ya dice logic issue to nahi
