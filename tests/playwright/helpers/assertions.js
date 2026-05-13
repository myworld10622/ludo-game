'use strict';
/**
 * Domain-specific assertion helpers for Ludo game state.
 * All functions throw if the assertion fails (Playwright expect-style).
 */

const { expect } = require('@playwright/test');

// ── Board constants (mirrors server) ─────────────────────────────────────────
const TOKEN_HOME_POS    = 56;
const BOARD_RING_SIZE   = 52;
const SAFE_SQUARES_ABS  = new Set([0, 8, 13, 21, 26, 34, 39, 47]);
const PLAYER_STARTS     = { 2: [0, 26], 3: [0, 13, 26], 4: [0, 13, 26, 39] };

function getPlayerStarts(maxPlayers) {
  return PLAYER_STARTS[maxPlayers] ?? PLAYER_STARTS[2];
}

function absPos(relPos, seatIndex, maxPlayers) {
  if (relPos < 0 || relPos > 50) return -1;
  const starts = getPlayerStarts(maxPlayers);
  return (starts[seatIndex] + relPos) % BOARD_RING_SIZE;
}

// ── Assertions ────────────────────────────────────────────────────────────────

/**
 * Assert that all clients received identical token positions for a given move.
 * @param {Array} tokenMovedEvents   One TOKEN_MOVED event from each client
 */
function assertTokensSynchronized(tokenMovedEvents, label = '') {
  expect(tokenMovedEvents.length, `${label}: must have events from all clients`).toBeGreaterThan(1);
  const canonical = JSON.stringify(tokenMovedEvents[0].tokens);
  for (let i = 1; i < tokenMovedEvents.length; i++) {
    expect(
      JSON.stringify(tokenMovedEvents[i].tokens),
      `${label}: client ${i} token snapshot differs from client 0`
    ).toBe(canonical);
  }
}

/**
 * Assert that a token at relPos is on a safe square (cannot be killed).
 */
function assertSafeSquare(relPos, seatIndex, maxPlayers, label = '') {
  const abs = absPos(relPos, seatIndex, maxPlayers);
  expect(SAFE_SQUARES_ABS.has(abs),
    `${label}: relPos=${relPos} seat=${seatIndex} abs=${abs} — expected safe square`
  ).toBe(true);
}

/**
 * Assert that a token at relPos is NOT on a safe square (can be killed).
 */
function assertNotSafeSquare(relPos, seatIndex, maxPlayers, label = '') {
  const abs = absPos(relPos, seatIndex, maxPlayers);
  expect(SAFE_SQUARES_ABS.has(abs),
    `${label}: relPos=${relPos} seat=${seatIndex} abs=${abs} — expected non-safe square`
  ).toBe(false);
}

/**
 * Assert token positions match expected map.
 * @param {number[][]} tokens     gs.tokens array from server
 * @param {object}     expected   { seatIndex: [pos0, pos1, pos2, pos3], ... }
 */
function assertTokenPositions(tokens, expected, label = '') {
  for (const [seatStr, expectedPositions] of Object.entries(expected)) {
    const si = parseInt(seatStr, 10);
    expect(tokens[si], `${label}: tokens[${si}] missing`).toBeTruthy();
    for (let ti = 0; ti < expectedPositions.length; ti++) {
      if (expectedPositions[ti] !== undefined) {
        expect(tokens[si][ti],
          `${label}: tokens[${si}][${ti}] expected ${expectedPositions[ti]} got ${tokens[si][ti]}`
        ).toBe(expectedPositions[ti]);
      }
    }
  }
}

/**
 * Assert that a RESULT event names the correct winner seat.
 */
function assertWinner(resultEvent, expectedSeatNo, label = '') {
  expect(resultEvent, `${label}: no RESULT event`).not.toBeNull();
  expect(resultEvent.cancelled, `${label}: match was cancelled`).toBe(false);
  expect(resultEvent.winner?.seat_no,
    `${label}: winner seat_no expected ${expectedSeatNo}`
  ).toBe(expectedSeatNo);
}

/**
 * Assert that all clients received the same winner in their RESULT events.
 */
function assertWinnerSynchronized(resultEvents, label = '') {
  expect(resultEvents.length,
    `${label}: not all clients received RESULT`
  ).toBeGreaterThan(1);
  const canonical = resultEvents[0]?.winner?.seat_no;
  for (let i = 1; i < resultEvents.length; i++) {
    expect(resultEvents[i]?.winner?.seat_no,
      `${label}: client ${i} winner differs from client 0`
    ).toBe(canonical);
  }
}

/**
 * Assert correct turn-order rotation.
 * @param {number[]} observedSeats   seat_index values from TURN_STARTED events
 * @param {number}   maxPlayers
 * @param {number}   minCycles       minimum full rotation cycles to verify
 */
function assertTurnOrder(observedSeats, maxPlayers, minCycles = 1, label = '') {
  const cycles = Math.floor(observedSeats.length / maxPlayers);
  expect(cycles, `${label}: fewer cycles than expected`).toBeGreaterThanOrEqual(minCycles);
  for (let i = 0; i < cycles * maxPlayers - 1; i++) {
    const expected = i % maxPlayers;
    expect(observedSeats[i],
      `${label}: turn ${i} should be seat ${expected}`
    ).toBe(expected);
  }
}

/**
 * Assert that a killed token is back at position -1 in the token snapshot.
 */
function assertTokenKilled(tokens, killedSeat, killedToken, label = '') {
  expect(tokens[killedSeat]?.[killedToken],
    `${label}: killed token [seat=${killedSeat}][ti=${killedToken}] should be -1`
  ).toBe(-1);
}

/**
 * Assert the three-sixes rule fired: moving token went back to -1.
 */
function assertThreeSixesForfeit(tokenMovedEvent, seatIndex, tokenIndex, label = '') {
  expect(tokenMovedEvent.tokens[seatIndex]?.[tokenIndex],
    `${label}: three-sixes forfeit — token should be -1`
  ).toBe(-1);
}

/**
 * Assert reconnect board state matches expected positions.
 */
function assertReconnectSync(gameStateEvent, expectedTokens, label = '') {
  expect(gameStateEvent, `${label}: no GAME_STATE event received`).not.toBeNull();
  for (const [seatStr, expected] of Object.entries(expectedTokens)) {
    const si = parseInt(seatStr, 10);
    expect(
      JSON.stringify(gameStateEvent.tokens[si]),
      `${label}: reconnect tokens[${si}] mismatch`
    ).toBe(JSON.stringify(expected));
  }
}

/**
 * Assert all placement rankings are consistent and contain all seat numbers.
 */
function assertPlacements(placements, expectedSeatNos, label = '') {
  const seatNos = placements.map(p => p.seat_no).sort((a, b) => a - b);
  expect(
    JSON.stringify(seatNos),
    `${label}: placement seat_nos mismatch`
  ).toBe(JSON.stringify([...expectedSeatNos].sort((a, b) => a - b)));
  const winner = placements.find(p => p.is_winner);
  expect(winner, `${label}: no winner in placements`).not.toBeUndefined();
  expect(winner.finish_position, `${label}: winner should be finish_position=1`).toBe(1);
}

module.exports = {
  assertTokensSynchronized,
  assertSafeSquare,
  assertNotSafeSquare,
  assertTokenPositions,
  assertWinner,
  assertWinnerSynchronized,
  assertTurnOrder,
  assertTokenKilled,
  assertThreeSixesForfeit,
  assertReconnectSync,
  assertPlacements,
  // re-export constants for specs
  TOKEN_HOME_POS,
  SAFE_SQUARES_ABS,
  getPlayerStarts,
  absPos,
};
