# Tournament Implementation Checklist

## Phase 1: Spec and Schema

- [x] Tournament feature specification written
- [x] Tournament implementation checklist written
- [ ] Tournament migrations added
- [ ] Tournament models added
- [ ] Tournament lifecycle service added

## Phase 2: Admin Foundation

- [ ] Admin tournament listing
- [ ] Admin create tournament
- [ ] Admin update tournament
- [ ] Admin publish / unpublish
- [ ] Admin cancel tournament
- [ ] Admin manage prize rules

## Phase 3: User Entry Flows

- [ ] User tournament listing by game
- [ ] User tournament detail endpoint
- [ ] User join tournament
- [ ] Multiple entries per user
- [ ] Ticket generation
- [ ] Wallet hold per entry
- [ ] Join validation against timing and caps

## Phase 4: Execution Foundation

- [ ] Tournament lock scheduler
- [ ] Tournament seeding service
- [ ] Tournament match linking
- [ ] Game adapter contract
- [ ] Ludo adapter foundation
- [ ] Teen Patti adapter foundation

## Phase 5: Results and Settlement

- [ ] Tournament result ingestion
- [ ] Tournament entry ranking
- [ ] Prize payout calculation
- [ ] Refund on cancel
- [ ] Winner payout wallet credits
- [ ] Tournament completion flow

## Phase 6: Admin and Reporting

- [ ] Entry inspection screen
- [ ] Result inspection screen
- [ ] Wallet audit links
- [ ] Tournament leaderboard API
- [ ] My tournament entries API

## Test Checklist

- [ ] One game, one tournament
- [ ] Same game, multiple concurrent tournaments
- [ ] Multiple entries by one user
- [ ] Entry lock after join close time
- [ ] Cancel tournament refunds entries
- [ ] Completed tournament pays winners
- [ ] Ludo tournament entries link to game matches
