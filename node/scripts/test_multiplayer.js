'use strict';
/**
 * Multiplayer test — 2 simulated players join a private table,
 * server-driven game starts, and we verify full turn cycle.
 *
 * Run on server:
 *   node /www/wwwroot/socket.roxludo.com/scripts/test_multiplayer.js
 */

const { io } = require('socket.io-client');

const SERVER_URL  = 'http://localhost:4010';
const NAMESPACE   = '/ludo_v2';
const ROOM_UUID   = 'test-room-' + Date.now();
const MAX_PLAYERS = 2;

const USER_A = { id: 4, name: "TestUserA" };
const USER_B = { id: 5, name: "TestUserB" };

let passed = 0;
let failed = 0;
const errors = [];

function ok(label)         { console.log(`  ✅ ${label}`); passed++; }
function fail(label, info) { console.error(`  ❌ ${label}${info ? ' — ' + info : ''}`); failed++; errors.push(label); }
function info(label)       { console.log(`  ℹ️  ${label}`); }
function sleep(ms)         { return new Promise(r => setTimeout(r, ms)); }

function connect() {
  return io(SERVER_URL + NAMESPACE, { transports: ['websocket'], reconnection: false, timeout: 8000 });
}

function waitEvent(socket, event, ms = 10000) {
  return new Promise((resolve, reject) => {
    const t = setTimeout(() => reject(new Error(`Timeout on "${event}" after ${ms}ms`)), ms);
    socket.once(event, d => { clearTimeout(t); resolve(d); });
  });
}

function raceEvent(socket, event, ms = 2000) {
  // Resolves with data if event fires within ms, otherwise null (no fail)
  return new Promise(resolve => {
    const t = setTimeout(() => resolve(null), ms);
    socket.once(event, d => { clearTimeout(t); resolve(d); });
  });
}

function joinQueue(sock, user) {
  sock.emit('ludo.queue.join', JSON.stringify({
    userId:     user.id,
    displayName: user.name,
    roomUuid:   ROOM_UUID,
    roomType:   'private',
    playMode:   'practice',
    gameMode:   'CLASSIC',
    maxPlayers: MAX_PLAYERS,
    entryFee:   0,
    allowBots:  false,
  }));
}

async function run() {
  console.log('\n══════════════════════════════════════════════════');
  console.log('  Ludo V2 — Server-Driven Multiplayer Test');
  console.log('══════════════════════════════════════════════════\n');

  // ── 1. Connect both sockets ──────────────────────────────────────────────
  const sockA = connect();
  const sockB = connect();

  await Promise.all([
    waitEvent(sockA, 'connect', 5000),
    waitEvent(sockB, 'connect', 5000),
  ]).catch(e => { fail('Socket connect', e.message); process.exit(1); });
  ok('Both sockets connected to ' + SERVER_URL + NAMESPACE);

  // ── 2. Player A joins first ──────────────────────────────────────────────
  const waitA = waitEvent(sockA, 'ludo.room.waiting', 6000);
  joinQueue(sockA, USER_A);

  const roomWaitA = await waitA.catch(() => null);
  if (roomWaitA) ok('Player A received ludo.room.waiting');
  else           { fail('Player A did not receive ludo.room.waiting'); return done(sockA, sockB); }

  info(`Room after A joined: seats=${JSON.stringify((roomWaitA.seats||[]).map(s=>({seatNo:s.seatNo,userId:s.userId})))}`);

  // ── 3. Player B joins ────────────────────────────────────────────────────
  const waitB       = waitEvent(sockB, 'ludo.room.waiting', 6000);
  const startingA   = waitEvent(sockA, 'ludo.room.starting', 8000);
  const startingB   = waitEvent(sockB, 'ludo.room.starting', 8000);

  await sleep(200);
  joinQueue(sockB, USER_B);

  const roomWaitB = await waitB.catch(() => null);
  if (roomWaitB) ok('Player B received ludo.room.waiting');
  else           fail('Player B did not receive ludo.room.waiting');

  // ── 4. Both get ludo.room.starting ──────────────────────────────────────
  const [startA, startB] = await Promise.all([
    startingA.catch(() => null),
    startingB.catch(() => null),
  ]);
  if (startA && startB) ok('Both received ludo.room.starting');
  else                  { fail('ludo.room.starting not received by both', `A=${!!startA} B=${!!startB}`); return done(sockA, sockB); }

  const seats    = startA.seats || [];
  const seatAObj = seats.find(s => s.userId == USER_A.id);
  const seatBObj = seats.find(s => s.userId == USER_B.id);
  if (!seatAObj || !seatBObj) { fail('Could not find both users in seats', JSON.stringify(seats)); return done(sockA, sockB); }
  ok(`Seats assigned: A=seatNo${seatAObj.seatNo}  B=seatNo${seatBObj.seatNo}`);

  const allHuman = seats.every(s => s.playerType === 'human');
  if (allHuman) ok('All seats are human → server-driven mode should activate');
  else          fail('Not all seats human, server-driven mode will NOT activate');

  // seat index 0 = seatNo 1 goes first
  const firstSeatNo = 1;
  const firstUser   = seats.find(s => s.seatNo === firstSeatNo)?.userId == USER_A.id ? USER_A : USER_B;
  const secondUser  = firstUser === USER_A ? USER_B : USER_A;
  const firstSock   = firstUser === USER_A ? sockA : sockB;
  const secondSock  = firstUser === USER_A ? sockB : sockA;
  info(`First turn → seatNo=1 → ${firstUser.name} (userId=${firstUser.id})`);

  // ── 5. Wait for first turn_started (~5.5s) ──────────────────────────────
  info('Waiting for ludo.game.turn_started (up to 9s)...');
  const [turn1A, turn1B] = await Promise.all([
    waitEvent(sockA, 'ludo.game.turn_started', 9000).catch(() => null),
    waitEvent(sockB, 'ludo.game.turn_started', 9000).catch(() => null),
  ]);
  if (turn1A && turn1B) ok('Both received ludo.game.turn_started');
  else                  { fail('turn_started not received', `A=${!!turn1A} B=${!!turn1B}`); return done(sockA, sockB); }

  if (turn1A.seat_index === 0) ok(`turn_started seat_index=0 ✓ (seatNo 1 is first)`);
  else                         fail(`turn_started seat_index=${turn1A.seat_index}, expected 0`);

  // ── 6. Active player rolls dice ──────────────────────────────────────────
  info(`${firstUser.name} rolling dice...`);
  firstSock.emit('ludo.game.roll_dice', JSON.stringify({
    room_id: ROOM_UUID,
    user_id: firstUser.id,
  }));

  const [dice1A, dice1B] = await Promise.all([
    waitEvent(sockA, 'ludo.game.dice_rolled', 5000).catch(() => null),
    waitEvent(sockB, 'ludo.game.dice_rolled', 5000).catch(() => null),
  ]);
  if (dice1A && dice1B) ok('Both received ludo.game.dice_rolled');
  else                  { fail('dice_rolled not received', `A=${!!dice1A} B=${!!dice1B}`); return done(sockA, sockB); }

  const diceVal = dice1A.dice_value;
  if (diceVal >= 1 && diceVal <= 6) ok(`dice_value=${diceVal} (valid 1–6)`);
  else                               fail(`dice_value=${diceVal} invalid`);

  if (dice1A.seat_index === 0) ok('dice_rolled seat_index=0 ✓');
  else                         fail(`dice_rolled seat_index=${dice1A.seat_index}, expected 0`);

  if (dice1A.dice_value === dice1B.dice_value) ok(`Both got same dice value=${diceVal} (synchronized ✓)`);
  else                                         fail(`Dice mismatch A=${dice1A.dice_value} B=${dice1B.dice_value}`);

  // ── 7. Wrong player tries to roll (must be rejected) ────────────────────
  info(`${secondUser.name} tries to roll (not their turn) — must be rejected...`);
  secondSock.emit('ludo.game.roll_dice', JSON.stringify({
    room_id: ROOM_UUID,
    user_id: secondUser.id,
  }));
  const wrongRoll = await raceEvent(sockA, 'ludo.game.dice_rolled', 1500);
  if (!wrongRoll) ok('Out-of-turn roll rejected ✓');
  else            fail('Out-of-turn roll was NOT rejected (server accepted it)');

  // ── 8. Active player moves token ────────────────────────────────────────
  const extraTurn = diceVal === 6;
  info(`${firstUser.name} moving token 0 (extra_turn=${extraTurn})...`);
  firstSock.emit('ludo.game.move_token', JSON.stringify({
    room_id:     ROOM_UUID,
    user_id:     firstUser.id,
    token_index: 0,
    extra_turn:  extraTurn,
    is_win:      false,
  }));

  const [mov1A, mov1B] = await Promise.all([
    waitEvent(sockA, 'ludo.game.token_moved', 5000).catch(() => null),
    waitEvent(sockB, 'ludo.game.token_moved', 5000).catch(() => null),
  ]);
  if (mov1A && mov1B) ok('Both received ludo.game.token_moved');
  else                { fail('token_moved not received', `A=${!!mov1A} B=${!!mov1B}`); return done(sockA, sockB); }

  if (mov1A.seat_index === 0 && mov1A.token_index === 0) ok(`token_moved seat=0 token=0 ✓`);
  else fail(`token_moved mismatch seat=${mov1A.seat_index} token=${mov1A.token_index}`);

  if (mov1A.dice_value === diceVal) ok(`token_moved includes correct dice_value=${diceVal}`);
  else fail(`token_moved dice_value=${mov1A.dice_value}, expected ${diceVal}`);

  if (JSON.stringify(mov1A) === JSON.stringify(mov1B)) ok('Both got identical token_moved payload ✓');
  else fail(`Payload mismatch A=${JSON.stringify(mov1A)} B=${JSON.stringify(mov1B)}`);

  // ── 9. Turn advance (if no extra turn) ──────────────────────────────────
  if (!mov1A.extra_turn) {
    info('No extra turn → waiting for next turn_started (seat should advance to 1)...');
    const [turn2A, turn2B] = await Promise.all([
      waitEvent(sockA, 'ludo.game.turn_started', 5000).catch(() => null),
      waitEvent(sockB, 'ludo.game.turn_started', 5000).catch(() => null),
    ]);
    if (turn2A && turn2B) ok('Both received second turn_started ✓');
    else                  { fail('Second turn_started not received', `A=${!!turn2A} B=${!!turn2B}`); return done(sockA, sockB); }

    if (turn2A.seat_index === 1) ok('Turn advanced → seat_index=1 ✓ (player 2\'s turn)');
    else                         fail(`Expected seat_index=1, got ${turn2A.seat_index}`);

    // ── 10. Player 2 rolls ─────────────────────────────────────────────────
    info(`${secondUser.name} rolling dice (their turn now)...`);
    secondSock.emit('ludo.game.roll_dice', JSON.stringify({
      room_id: ROOM_UUID,
      user_id: secondUser.id,
    }));

    const [dice2A, dice2B] = await Promise.all([
      waitEvent(sockA, 'ludo.game.dice_rolled', 5000).catch(() => null),
      waitEvent(sockB, 'ludo.game.dice_rolled', 5000).catch(() => null),
    ]);
    if (dice2A && dice2B) ok(`Both received Player 2 dice_rolled (value=${dice2A?.dice_value}) ✓`);
    else                  fail('Player 2 dice_rolled not received', `A=${!!dice2A} B=${!!dice2B}`);

    if (dice2A?.seat_index === 1) ok('dice_rolled seat_index=1 ✓ (player 2)');
    else                          fail(`seat_index=${dice2A?.seat_index}, expected 1`);

    // ── 11. Player 1 tries to roll out of turn ────────────────────────────
    info(`${firstUser.name} tries to roll (not their turn) — must be rejected...`);
    firstSock.emit('ludo.game.roll_dice', JSON.stringify({
      room_id: ROOM_UUID,
      user_id: firstUser.id,
    }));
    const wrongRoll2 = await raceEvent(sockA, 'ludo.game.dice_rolled', 1500);
    if (!wrongRoll2) ok('Out-of-turn roll (player 1 during player 2 turn) rejected ✓');
    else             fail('Out-of-turn roll was NOT rejected');
  } else {
    info('Extra turn → same player goes again (dice=6 or capture). Turn advance check skipped.');
  }

  // ── 12. Timeout test: let a turn expire ──────────────────────────────────
  info('\nTurn-miss timeout test: neither player rolls (wait up to 20s)...');
  const [miss1, miss2] = await Promise.all([
    waitEvent(sockA, 'ludo.game.turn_missed', 20000).catch(() => null),
    waitEvent(sockB, 'ludo.game.turn_missed', 20000).catch(() => null),
  ]);
  if (miss1 && miss2) ok(`Both received turn_missed (reason="${miss1.reason}") ✓ — timeout working`);
  else                fail('turn_missed not received within 20s — timeout may not be working');

  done(sockA, sockB);
}

function done(sockA, sockB) {
  console.log('\n══════════════════════════════════════════════════');
  if (failed === 0)
    console.log(`  🎉  ALL ${passed} TESTS PASSED`);
  else {
    console.log(`  📊  ${passed} passed  |  ${failed} FAILED`);
    console.log('  Failed tests:');
    errors.forEach(e => console.log(`    • ${e}`));
  }
  console.log('══════════════════════════════════════════════════\n');
  try { sockA.disconnect(); sockB.disconnect(); } catch(_) {}
  process.exit(failed > 0 ? 1 : 0);
}

run().catch(err => {
  console.error('\n💥 Unhandled error:', err.message, err.stack);
  process.exit(1);
});
