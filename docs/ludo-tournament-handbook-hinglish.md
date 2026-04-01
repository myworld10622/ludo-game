# Ludo Tournament Handbook (Hinglish)

## 1. Project Overview

Ye project 3 major parts me split hai:

- `backend_laravel`
  Laravel API + admin panel + user panel + tournament engine + reports + support/tickets
- `node`
  Socket / realtime layer jo Ludo room events aur live gameplay connectivity me use hota hai
- `unity`
  Player-facing mobile game app jahan se login, wallet, tournament browse, register, aur play flow hota hai

Simple flow:

1. User Unity app me login karta hai
2. Unity Laravel API se tournament list aur user data fetch karti hai
3. Tournament join/register Laravel me hota hai
4. Jab match/table assign hoti hai to Unity socket/node flow se room join karti hai
5. Result backend me settle hota hai
6. Admin aur user dono panels me reports show hoti hain

---

## 2. Current Main Modules

### 2.1 User/Auth

- signup by email/mobile supported
- email verification required nahi hai
- login possible via:
  - user id / user_code
  - username
  - email
  - mobile

### 2.2 Wallet

- tournament register karte time entry fee wallet se deduct hoti hai
- cancel allowed phase me refund ho sakta hai
- winner payout tournament completion ke baad wallet me credit hota hai

### 2.3 Tournament

- public aur private tournaments
- knockout format active hai
- 2-player aur 4-player match size supported
- admin-created aur user-created tournaments
- user-created tournament admin approval ke through ja sakta hai
- play slots support hai
- reports user panel aur admin panel dono me available hain

### 2.4 Admin Controls

- approve
- reject with reason
- edit tournament before approval
- reports
- match monitoring
- manual winner set
- user panel permissions
- support/ticket chat with user

### 2.5 User Panel

- own dashboard
- own tournaments
- own match monitor
- support chat
- tournament reports

---

## 3. Current Tournament Lifecycle

Current Laravel tournament statuses:

- `draft`
- `registration_open`
- `registration_closed`
- `in_progress`
- `completed`
- `cancelled`

Meaning:

- `draft`
  tournament bana hua hai but live registration ke liye open nahi hua
- `registration_open`
  user register kar sakta hai
- `registration_closed`
  new registration band, existing players next stage ke liye wait / claim kar sakte hain
- `in_progress`
  matches chal rahe hain
- `completed`
  tournament finish, winners/payout/report final
- `cancelled`
  tournament cancel

Scheduler automation:

- `draft -> registration_open`
  jab approved ho aur registration start time aa jaye
- `registration_open -> registration_closed`
  jab registration end time cross ho jaye
- no-show users ko slot miss hone par disqualify kiya ja sakta hai

Code reference:

- `backend_laravel/app/Models/Tournament.php`
- `backend_laravel/app/Services/Tournament/TournamentStatusAutomationService.php`

---

## 4. Tournament Creation Rules

### 4.1 Admin Tournament

Admin tournament create kar sakta hai with advanced options:

- name
- type
- format
- bracket mode
- entry fee
- max players
- players per match
- platform fee
- dates
- description
- terms
- prize split
- play slots
- bot-related options

### 4.2 User Tournament

User ke liye intentionally restricted flow hai:

- user tournament create kar sakta hai
- user apna tournament self-approve nahi kar sakta
- user fake registrations add nahi kar sakta
- user force live nahi kar sakta
- user bot option direct use nahi karta
- user mostly real tournament setup karta hai

User-created tournament admin queue me review hota hai.

Admin kya kar sakta hai:

- detail review
- edit
- approve
- reject with reason
- support thread me user ko message

---

## 5. Tournament Submission Review

Admin ko review page par ye details dikhni chahiye / currently available hain:

- tournament name
- type
- format
- bracket mode
- entry fee
- max players
- players per match
- platform fee %
- bot allowed
- max bot %
- registration start / end
- tournament start
- invite code / password
- description
- terms & conditions
- prize split
- play slots

Iska purpose:

- admin check kare user ne kya submit kiya
- incorrect fee / timing / prize / content ho to edit kare
- then approve ya reject kare

---

## 6. Tournament Join / Register Flow

### 6.1 Unity Tournament List

Unity tournament panel me current approved public tournaments show hote hain:

- `registration_open`
- `registration_closed`
- `in_progress`

### 6.2 Registration Open Tournament

Current Unity behavior:

- direct table par nahi jata
- pehle details popup open hota hai
- popup me show hota hai:
  - status
  - players
  - entry fee
  - prize pool
  - registration close time
  - start time
  - play slots
  - wallet balance

If user `Register` click karta hai:

1. backend registration API hit hoti hai
2. wallet balance check hota hai
3. entry fee deduct hoti hai
4. registration record create hota hai
5. Unity local wallet update hoti hai

### 6.3 Already Registered but Tournament Not Live Yet

User ko detail popup me indicate hota hai:

- you are already registered
- wait for tournament to go live

### 6.4 Tournament In Progress

If user registered hai aur tournament live/in progress hai:

- Unity `Play Now` allow karti hai
- claim room / match flow trigger hota hai

### 6.5 Full Tournament

If tournament full hai:

- new registration blocked
- user details dekh sakta hai
- register button disabled / unavailable

Code references:

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Managers/LudoTournamentPanelOffline.cs`
- `backend_laravel/app/Http/Controllers/Api/V1/TournamentRegistrationController.php`

---

## 7. Wallet Rules

Current real behavior:

- registration time par wallet se entry fee deduct hoti hai
- duplicate registration idempotent hai, same registration return hoti hai
- insufficient balance par registration fail hota hai
- cancel registration ke case me eligible phase me refund hota hai
- tournament complete hone par winners ke wallet me prize credit hota hai
- platform fee transaction bhi record hoti hai

Important:

- tournament register API wallet deduction same transaction me karti hai
- isliye successful register ka matlab entry fee already deduct ho chuki hai

---

## 8. Bot Rules

### 8.1 Public Normal Ludo Queue

Normal non-tournament Ludo public queue me:

- bots allowed ho sakte hain
- bot fill after timeout ka logic available hai
- room me minimum real players meet ho jaye to remaining seats bots se fill ho sakti hain

Bot decision rule:

- agar room full real players se ho gaya -> start
- agar bots allowed hain aur minimum real player count meet ho gaya -> bots fill karke start
- warna wait

Code references:

- `backend_laravel/app/Services/Match/LudoBotSeatPolicy.php`
- `backend_laravel/app/Services/Match/LudoMatchmakingService.php`
- `backend_laravel/app/Services/Match/LudoRoomLifecycleService.php`

### 8.2 Tournament Bot Rules

Tournament side me do alag concepts hain:

1. tournament registration level bots
2. live Ludo room fill bots

Current observed implementation:

- admin tournament me tournament registration level bots add kar sakta hai
- max bot percentage rule hai
- user panel se bots add nahi hote
- current tournament room provisioning service provisioned Ludo rooms ko `allow_bots = false` ke saath create kar rahi hai

Important practical meaning:

- even if tournament model me `bot_allowed` true ho
- current provisioned tournament Ludo room code real-human seats hi use kar raha hai
- room creation time par tournament room me live bot auto-fill active nahi hai

Ye bahut important condition hai.

Code references:

- `backend_laravel/app/Http/Controllers/Api/V1/TournamentRegistrationController.php`
- `backend_laravel/app/Services/Tournament/TournamentLudoRoomProvisionService.php`

---

## 9. Real User Join Conditions

User real player ke roop me tournament join tab kar sakta hai jab:

- tournament approved ho
- tournament `registration_open` me ho
- tournament full na ho
- wallet me sufficient balance ho
- user already refunded registration state me na ho

User tournament room / table claim tab kar sakta hai jab:

- user registered ho
- active slot allowed ho, agar play slots defined hain
- claim request valid tournament / entry ke liye ho

If play slots defined hain aur active slot nahi chal raha:

- claim room reject hota hai
- backend `Tournament can only be played during scheduled play slots.` return karta hai

---

## 10. Play Slot Rules

Play slots ka purpose:

- users ko bata diya jaye ki kis time online rehna hai
- table fill aur play coordination better ho
- random late play avoid ho

Current supported behavior:

- tournament create/edit form me 1 se 5 slots define ho sakte hain
- slot detail report pages par visible hai
- Unity tournament popup me visible hai
- claim room ke waqt active slot validation hoti hai

### 10.1 If User Does Not Play In Slot

Current scheduler logic:

- scheduler every minute run ho sakta hai
- slot end hone ke baad wo registered / checked_in players ko inspect karta hai
- jin logon ne us slot me check-in/claim nahi kiya hota
- unka registration `disqualified` ho sakta hai

Registration status update:

- `registered` ya `checked_in`
- but missed slot
- then `disqualified`

### 10.2 Check-in Tracking

Claim room successful hone par registration me save hota hai:

- `last_checked_in_at`
- `last_checked_in_slot_index`

Iska matlab:

- user slot me aaya ya nahi
- kaunse slot me aaya

Code references:

- `backend_laravel/app/Models/Tournament.php`
- `backend_laravel/app/Models/TournamentRegistration.php`
- `backend_laravel/app/Services/Tournament/TournamentStatusAutomationService.php`
- `backend_laravel/app/Http/Controllers/Api/V1/TournamentController.php`

---

## 11. Tournament Match / Table Behavior

### 11.1 Current Provisioning Logic

Tournament room claim flow current code me generally pre-seeded bracket style follow karta hai.

Observed behavior:

- tournament entries / registrations se bracket generate hota hai
- match entries assign hote hain
- room provisioning service round-wise room create karti hai
- provisioned room me all seeded human entries mapped hote hain
- tournament room me `allow_bots = false`

### 11.2 Important Consequence

Current backend ke hisaab se room creation ka decision sirf “is match me kaunse seeded entries hain” par based hai, “abhi exactly kitne users online hain” par nahi.

Iska matlab:

- agar 4-player match ke 4 seeded participants hain, room provision ho sakta hai
- chahe us moment par app me sirf 1 ya 2 users actually online hon
- missing players ke liye current room provisioning live bot fill nahi kar rahi

Isliye practical match success online attendance par depend karega, but room provisioning usse pehle ho sakti hai.

---

## 12. Scenario Explanations

### 12.1 Scenario: Registration Open, User Detail Dekhta Hai

- user tournament card click karta hai
- details popup open hota hai
- user fee, prize, slots, time check karta hai
- agar wallet sufficient hai to register kar sakta hai

### 12.2 Scenario: User Register Karta Hai

- wallet deduction instantly
- registration create
- user registered state me chala jata hai
- Unity wallet update hoti hai

### 12.3 Scenario: User Registered Hai, Tournament Abhi Start Nahi Hua

- user wait karega
- details me registered state dekh sakta hai
- live hone par play option aayega

### 12.4 Scenario: Play Slot Hai, User Slot Par Online Nahi Aaya

- user room claim nahi karega
- slot end hone ke baad scheduler usko disqualify kar sakta hai
- tournament se effectively out ho sakta hai

### 12.5 Scenario: Bots Allowed Nahi Hain

- tournament room me current code already bots disable kar raha hai
- missing real players ki jagah live room auto-bot fill nahi hoti
- user attendance important ho jati hai

### 12.6 Scenario: 4 Player Tournament, Sirf 1 User Online

Current code ke hisaab se:

- bracket/provisioning attendance se pehle ho sakti hai
- room pre-created ho sakta hai if match entries exist
- but actual real gameplay sabke online hone par dependent rahega
- current tournament room provisioning auto bot fill nahi karti
- absent users slot miss karein to disqualification risk hai

### 12.7 Scenario: 4 Player Tournament, Sirf 2 Users Online

Current code ke hisaab se:

- same as above
- tournament-specific live bot fill active nahi hai
- match room assignment exist ho sakta hai
- absent players ka no-show outcome scheduler / later match handling par depend karega

### 12.8 Scenario: Tournament Full Ho Gaya

- new user register nahi kar sakta
- existing participants tournament wait / live stage ke liye continue karte hain

### 12.9 Scenario: Tournament Completed

- final match complete
- winners ranked
- prize payout real users ko credit
- reports user/admin panel me visible

---

## 13. User Panel Features

Current user panel me major features:

- dashboard
- live / running tournament highlights
- owned tournament list
- create tournament
- tournament reports
- match monitor
- support chat / ticket
- mobile responsive layout

User panel restrictions:

- self-approve nahi
- force live nahi
- fake registration nahi
- bot control nahi
- sirf own tournaments / own reports

---

## 14. Admin Panel Features

Current admin panel me major features:

- colorful dashboard
- pending approval alerts
- recent tournament reports
- full tournament list
- review details
- edit before approval
- approve
- reject with reason
- support tickets/chat
- user-specific permission matrix
- reports
- match monitor
- manual winner override

Admin super-scope:

- admin sab user tournaments dekh sakta hai
- user panel sirf apna data dekhta hai

---

## 15. Reports Available

User aur admin dono side detailed tournament report pages available hain:

- overview
- winners
- registrations
- round-wise matches
- financials
- export actions
  - excel/csv
  - print
  - browser save as PDF

Admin side extra:

- pending approval queue
- review details before approve
- edit tournament form

---

## 16. Unity Tournament UX Summary

Current Unity UX:

- tournament lobby list
- status-aware button labels
- registration-open tournament -> details popup
- in-progress + registered -> play now
- slot details popup me visible
- wallet balance popup me visible
- register success par wallet update
- popup design cleaned and improved

Important Unity behavior:

- registration-open tournament direct table par nahi jata
- pehle user ko details and register decision diya jata hai

---

## 17. Confirmed Tests Run

### 17.1 Working Tests Run Successfully

Current schema-compatible simulation commands successfully run kiye gaye:

- `php artisan tournament:simulate --players=4 --real-users=4 --per-match=4 --fee=100 --creator-user=abc4943161`
- `php artisan tournament:simulate --players=8 --real-users=8 --per-match=2 --fee=50 --creator-user=abc4943161`

Confirmed from these runs:

- tournament create hua
- owner user assign hua
- players register hue
- bracket generate hui
- matches simulate hue
- tournament `completed` hua
- prizes real users ko paid hui
- wallet balances update hui

Observed sample preserved tournament ids:

- `#17` 4-player test
- `#18` 8-player test

### 17.2 Scheduler Command Run

Run:

- `php artisan tournaments:advance-statuses`

Result:

- scheduler command successfully execute hua
- registration close transition count show hua

### 17.3 Not Fully Runtime-Executed in This Pass

Ye parts is pass me code-review verified hue, but full interactive live run nahi hua:

- Unity full Play Mode execution
- browser click-through on every panel page
- node socket live multiplayer attendance edge cases
- exact no-show slot disqualification live DB scenario

Reason:

- `artisan tinker` available nahi tha for quick ad-hoc DB scenario
- Unity editor automation yahan se execute nahi hui

---

## 18. Issues / Gaps Found During Review

### 18.1 Outdated Smoke Test Command

Command:

- `php artisan tournaments:smoke-runtime`

Current project schema ke saath fail hua because:

- command old tournament schema / statuses use kar raha hai
- current production-facing tournament schema different hai

Meaning:

- ye smoke test utility currently outdated hai
- production behavior ka trusted test path currently `tournament:simulate` zyada suitable hai

### 18.2 Scheduler Output Gap

Service no-show disqualification count return karti hai, but command output pehle usko print nahi kar raha tha.

Ye ab improve kar diya gaya hai:

- `missed-slot disqualified` count CLI output me show hoga

### 18.3 Tournament Bot Intent vs Room Reality

Tournament model me bot-related fields exist karte hain, but current tournament Ludo room provisioning:

- `allow_bots = false`
- live room auto bot fill disable

Isliye business expectation aur actual room behavior ko clear rakhna zaroori hai.

If requirement hai:

- “agar real users kam ho to bots fill karke match start ho”

to tournament room provisioning me additional implementation chahiye hogi.

---

## 19. Practical Recommendation

Agar aap real-player-first tournament chalana chahte ho, current system ka safer interpretation ye hai:

- user ko slots clearly dikhaye jaye
- slot miss karne par disqualification rahe
- tournament room me bots default off rahein
- admin special cases manually monitor kare

Lekin agar aap chahte ho:

- 4-player table me 1 ya 2 real users aayein aur baaki bots se table start ho jaye

to ye abhi fully active behavior nahi lag raha; iske liye dedicated tournament-bot-start policy implement karni hogi.

---

## 20. Important File Map

### Laravel

- `backend_laravel/app/Models/Tournament.php`
- `backend_laravel/app/Models/TournamentRegistration.php`
- `backend_laravel/app/Http/Controllers/Api/V1/TournamentRegistrationController.php`
- `backend_laravel/app/Http/Controllers/Api/V1/TournamentController.php`
- `backend_laravel/app/Services/Tournament/TournamentStatusAutomationService.php`
- `backend_laravel/app/Services/Tournament/TournamentLudoExecutionService.php`
- `backend_laravel/app/Services/Tournament/TournamentLudoRoomProvisionService.php`
- `backend_laravel/app/Services/Tournament/TournamentLudoMatchLinkService.php`
- `backend_laravel/app/Services/Match/LudoBotSeatPolicy.php`
- `backend_laravel/app/Services/Match/LudoMatchmakingService.php`
- `backend_laravel/app/Services/Match/LudoRoomLifecycleService.php`

### Unity

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Managers/LudoTournamentPanelOffline.cs`
- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Managers/DashBoardManagerOffline.cs`
- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoV2MatchmakingBridge.cs`

### Docs

- `backend_laravel/docs/tournament-system-spec.md`
- `backend_laravel/docs/ludo-tournament-handbook-hinglish.md`

---

## 21. Final Summary

Current project me major pieces kaafi strong state me hain:

- auth works
- wallet register flow works
- tournament creation/review/reporting works
- Unity tournament register/detail flow works
- admin and user panels strong hain
- play slot concept implemented hai

Sabse important audit point ye hai:

- public normal Ludo bot logic aur tournament room bot logic same nahi hai
- tournament me real attendance / no-show / slot discipline abhi main deciding factor hai
- agar tournament auto-bot-fill chahiye to usko alag explicitly build karna padega

Ye handbook review ke liye bana hai. Iske basis par next step me hum chahein to ek separate `Issue Audit Checklist` bhi bana sakte hain.
