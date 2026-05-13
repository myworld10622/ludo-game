'use strict';
/**
 * testRoom helpers — create/join rooms and wait for game start.
 *
 * Usage:
 *   const { clients, roomId } = await createRoom({ maxPlayers: 2 });
 *   // ... run test ...
 *   await teardownRoom(clients);
 */

const { v4: uuidv4 } = require('uuid');
const { GameClient }  = require('./GameClient');
require('dotenv').config({ path: require('path').resolve(__dirname, '../../.env') });

const BASE_USER_ID = parseInt(process.env.TEST_USER_1_ID || '10001', 10);

/**
 * Create N GameClient instances, connect them, and join the same room.
 * Waits until all clients receive `ludo.room.starting`.
 *
 * @param {object} opts
 * @param {number}   opts.maxPlayers   2 or 4
 * @param {number}   [opts.entryFee]
 * @param {boolean}  [opts.allowBots]
 * @param {string}   [opts.serverUrl]
 * @returns {{ clients: GameClient[], roomId: string }}
 */
async function createRoom({ maxPlayers = 2, entryFee = 0, allowBots = false, serverUrl } = {}) {
  const roomUuid = uuidv4();
  const clients  = [];

  for (let i = 0; i < maxPlayers; i++) {
    const c = new GameClient({
      userId: BASE_USER_ID + i,
      name:   `TestPlayer_${i + 1}`,
      serverUrl,
    });
    await c.connect();
    clients.push(c);
  }

  // First client joins — creates the room
  clients[0].joinQueue({ roomUuid, maxPlayers, entryFee, allowBots });

  // Remaining clients join the same room
  for (let i = 1; i < maxPlayers; i++) {
    clients[i].joinQueue({ roomUuid, maxPlayers, entryFee, allowBots });
  }

  // Wait for all clients to receive starting
  const startingPromises = clients.map(c =>
    c.waitFor('ludo.room.starting', null, parseInt(process.env.GAME_START_TIMEOUT || '15000', 10))
  );
  const startingPayloads = await Promise.all(startingPromises);

  const roomId = startingPayloads[0]?.room_id ?? roomUuid;
  clients.forEach(c => { if (!c.roomId) c.roomId = roomId; });

  return { clients, roomId };
}

/**
 * Disconnect all clients cleanly.
 */
async function teardownRoom(clients) {
  for (const c of clients) {
    try { c.disconnect(); } catch { /* ignore */ }
  }
}

/**
 * Wait for all clients to receive `ludo.game.turn_started` for seat 0.
 * (Server fires first turn ~5.5s after starting.)
 */
async function waitForFirstTurn(clients) {
  return Promise.all(clients.map(c =>
    c.waitFor('ludo.game.turn_started', null, 12_000)
  ));
}

/**
 * Play a full game using bots on each seat.
 * Returns the RESULT event received by client[0].
 *
 * @param {GameClient[]} clients
 * @param {object}       [opts]
 * @param {number}       [opts.maxTurns=300]       safety limit
 * @param {number}       [opts.maxPlayers]
 * @param {Function}     [opts.onMove]   (seatIndex, tokenIndex, diceValue) callback
 */
async function playFullGame(clients, { maxTurns, maxPlayers, onMove } = {}) {
  maxPlayers = maxPlayers || clients.length;
  if (!maxTurns) maxTurns = maxPlayers >= 4 ? 800 : 400;
  let turnCount = 0;

  // Build seatIndex→client map.
  // Primary: match by seatIndex (set after room.starting).
  // Fallback: server seats are assigned in join order — client[i] → seat i.
  function findActor(serverSeat) {
    let c = clients.find(c => c.seatIndex === serverSeat);
    if (!c) c = clients[serverSeat]; // index fallback (all-human room: seat 0 = client 0)
    return c ?? null;
  }

  while (turnCount < maxTurns) {
    // Check if result already arrived (fires after N-1 players finish in multi-player)
    const earlyResult = clients[0].lastReceived('ludo.game.result');
    if (earlyResult) return earlyResult;

    // Race: turn_started vs result (result fires when game ends, no more turns follow)
    const turnData = await clients[0].waitFor('ludo.game.turn_started', null, 20_000)
      .catch(() => null);

    // Check result whether we got a turn or timed out
    const midResult = clients[0].lastReceived('ludo.game.result');
    if (midResult) return midResult;
    if (!turnData) throw new Error('playFullGame: timed out waiting for turn_started, no result received');

    const si = turnData.seat_index;

    const actor = findActor(si);
    if (!actor) {
      // Unexpected — wait passively for token_moved then continue
      await clients[0].waitFor('ludo.game.token_moved', d => d.seat_index === si, 20_000);
    } else {
      // Register listeners BEFORE emitting to avoid missing fast server responses
      const dicePromise  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 10_000);
      const movedPromise = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 15_000);

      actor.turnNonce = turnData.turn_nonce ?? actor.turnNonce;
      actor.rollDice();
      const diceData = await dicePromise;

      if (diceData.has_moves) {
        const tokenIdx = diceData.legal_tokens[0];
        actor.rollNonce = diceData.roll_nonce ?? actor.rollNonce;
        if (onMove) onMove(si, tokenIdx, diceData.dice_value);
        actor.moveToken(tokenIdx);
      }

      await movedPromise;
    }

    // Check result (fires after all-but-one players finish)
    const result = clients[0].lastReceived('ludo.game.result');
    if (result) return result;

    turnCount++;
  }

  throw new Error(`playFullGame: safety limit ${maxTurns} turns reached without a winner`);
}

module.exports = { createRoom, teardownRoom, waitForFirstTurn, playFullGame };
