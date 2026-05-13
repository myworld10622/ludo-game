'use strict';
/**
 * stressRoom — like testRoom but creates FuzzClients with configurable chaos.
 */

const { v4: uuidv4 } = require('uuid');
const { FuzzClient }  = require('./FuzzClient');
require('dotenv').config({ path: require('path').resolve(__dirname, '../../.env') });

const BASE_USER_ID = parseInt(process.env.TEST_USER_1_ID || '10001', 10);

/**
 * @param {object} opts
 * @param {number}  opts.maxPlayers
 * @param {object}  [opts.fuzz]       passed to FuzzClient
 * @param {string}  [opts.serverUrl]
 */
async function createStressRoom({ maxPlayers = 2, fuzz = {}, serverUrl } = {}) {
  const roomUuid = uuidv4();
  const clients  = [];

  for (let i = 0; i < maxPlayers; i++) {
    const c = new FuzzClient({
      userId: BASE_USER_ID + i,
      name:   `Stress_${i + 1}`,
      serverUrl,
      fuzz,
    });
    await c.connect();
    clients.push(c);
  }

  clients[0].joinQueue({ roomUuid, maxPlayers, allowBots: false });
  for (let i = 1; i < maxPlayers; i++) {
    clients[i].joinQueue({ roomUuid, maxPlayers, allowBots: false });
  }

  const starts = await Promise.all(
    clients.map(c => c.waitFor('ludo.room.starting', null, 15_000))
  );
  const roomId = starts[0]?.room_id ?? roomUuid;
  clients.forEach(c => { if (!c.roomId) c.roomId = roomId; });

  return { clients, roomId };
}

async function teardownStressRoom(clients) {
  for (const c of clients) {
    try { c.disconnect(); } catch { /* ignore */ }
  }
}

/**
 * Play a full game with optional fuzz timing injected between turns.
 *
 * @param {FuzzClient[]} clients
 * @param {object}       [opts]
 * @param {number}       [opts.maxTurns]
 * @param {number}       [opts.moveDelayMs]   extra sleep before each move (0–N ms random)
 * @param {Function}     [opts.onEvent]       (event, data, seatIndex) spy callback
 * @returns {{ result, turns, tokenMovedCount, duplicateResultCount }}
 */
async function playStressGame(clients, {
  maxTurns     = 600,
  moveDelayMs  = 0,
  onEvent      = null,
} = {}) {
  const stats = {
    turns:               0,
    tokenMovedCount:     0,
    duplicateResultCount: 0,
    extraTurns:          0,
    missedTurns:         0,
    rollRejections:      0,
    moveRejections:      0,
    resultEvents:        [],
  };

  // Spy on ludo.error to count rejected actions
  clients[0].on('ludo.error', d => {
    if (d?.reason?.includes('roll')) stats.rollRejections++;
    if (d?.reason?.includes('move')) stats.moveRejections++;
    if (onEvent) onEvent('ludo.error', d);
  });
  clients[0].on('ludo.game.turn_missed', d => {
    stats.missedTurns++;
    if (onEvent) onEvent('ludo.game.turn_missed', d);
  });

  function findActor(si) {
    return clients.find(c => c.seatIndex === si) ?? clients[si] ?? null;
  }

  let lastResult = null;

  while (stats.turns < maxTurns) {
    const turnData = await clients[0].waitFor('ludo.game.turn_started', null, 25_000);
    const si = turnData.seat_index;
    stats.turns++;

    const actor = findActor(si);
    if (!actor) {
      await clients[0].waitFor('ludo.game.token_moved', d => d.seat_index === si, 20_000);
      continue;
    }

    // Optional fuzz delay before roll
    if (moveDelayMs > 0) {
      await new Promise(r => setTimeout(r, Math.floor(Math.random() * moveDelayMs)));
    }

    const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 12_000);
    const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 12_000);

    actor.turnNonce = turnData.turn_nonce ?? actor.turnNonce;
    actor.rollDice();

    let diceData;
    try {
      diceData = await diceP;
    } catch (e) {
      // Roll timed out — server may have missed it; continue
      stats.rollRejections++;
      continue;
    }

    if (onEvent) onEvent('ludo.game.dice_rolled', diceData, si);

    // Optional fuzz delay before move
    if (moveDelayMs > 0) {
      await new Promise(r => setTimeout(r, Math.floor(Math.random() * moveDelayMs)));
    }

    if (diceData.has_moves) {
      actor.rollNonce = diceData.roll_nonce ?? actor.rollNonce;
      actor.moveToken(diceData.legal_tokens[0]);
    }

    let movedData;
    try {
      movedData = await movedP;
    } catch (e) {
      stats.moveRejections++;
      continue;
    }

    stats.tokenMovedCount++;
    if (movedData.extra_turn) stats.extraTurns++;
    if (onEvent) onEvent('ludo.game.token_moved', movedData, si);

    // Check for result (broadcast alongside or after is_win token_moved)
    const r = clients[0].lastReceived('ludo.game.result');
    if (r) {
      stats.resultEvents.push(r);
      if (stats.resultEvents.length > 1) stats.duplicateResultCount++;
      lastResult = r;
      break;
    }
    if (movedData.is_win) {
      try {
        lastResult = await clients[0].waitFor('ludo.game.result', null, 10_000);
        stats.resultEvents.push(lastResult);
      } catch { /* may already be in log */ }
      break;
    }
  }

  return { result: lastResult, stats };
}

module.exports = { createStressRoom, teardownStressRoom, playStressGame };
