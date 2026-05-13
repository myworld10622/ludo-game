'use strict';
/**
 * Spec 09 — Settlement
 *
 * Validates:
 *   ✓ Server auto-settles when a player wins (all tokens home)
 *   ✓ RESULT event contains correct winner, placements, cancelled=false
 *   ✓ Placements are ordered: winner is finish_position=1
 *   ✓ All seats appear in placements exactly once
 *   ✓ Settlement fires only once (no duplicate RESULT events)
 *   ✓ Both/all clients receive the same RESULT payload
 *   ✓ Client cannot trigger settlement manually in server-driven mode
 *   ✓ Winner token positions: all tokens at TOKEN_HOME_POS (56)
 */

const { test, expect } = require('@playwright/test');
const { createRoom, teardownRoom, playFullGame } = require('../helpers/testRoom');
const {
  assertWinner,
  assertWinnerSynchronized,
  assertPlacements,
  TOKEN_HOME_POS,
} = require('../helpers/assertions');

test.describe('Settlement', () => {
  test('2-player: RESULT fires automatically with valid placements', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      const r0 = await playFullGame(clients, { maxPlayers: 2 });
      const r1 = clients[1].lastReceived('ludo.game.result')
        ?? await clients[1].waitFor('ludo.game.result', null, 5_000);
      const results = [r0, r1];

      // Both clients must have received RESULT
      for (const r of results) {
        expect(r).not.toBeNull();
        expect(r.cancelled).toBe(false);
        expect(r.winner).not.toBeNull();
        expect(r.winner.seat_no).toBeGreaterThanOrEqual(1);
        expect(r.winner.seat_no).toBeLessThanOrEqual(2);
      }

      assertPlacements(results[0].placements, [1, 2], '2p settlement');
      assertWinnerSynchronized(results, '2p settlement');
    } finally {
      await teardownRoom(clients);
    }
  });

  test('RESULT is emitted exactly once', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      await playFullGame(clients, { maxPlayers: 2 });

      // Wait 3s for any stray duplicates
      await new Promise(r => setTimeout(r, 3000));

      const resultEvents = clients[0].received('ludo.game.result');
      expect(resultEvents.length,
        'RESULT must fire exactly once'
      ).toBe(1);
    } finally {
      await teardownRoom(clients);
    }
  });

  test('winner has all 4 tokens at position 56 (TOKEN_HOME_POS)', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      const result = await playFullGame(clients, { maxPlayers: 2 });

      // The last TOKEN_MOVED event should show the winner's tokens all at 56
      const lastMoved = clients[0].lastReceived('ludo.game.token_moved');
      expect(lastMoved.is_win).toBe(true);

      const winnerSeat = lastMoved.seat_index;
      const winnerTokens = lastMoved.tokens[winnerSeat];
      for (let ti = 0; ti < 4; ti++) {
        expect(winnerTokens[ti],
          `Winner token[${ti}] should be at ${TOKEN_HOME_POS}`
        ).toBe(TOKEN_HOME_POS);
      }
    } finally {
      await teardownRoom(clients);
    }
  });

  test('placements are distinct finish_positions and winner is position 1', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      await playFullGame(clients, { maxPlayers: 2 });

      const result = clients[0].lastReceived('ludo.game.result');
      expect(result).not.toBeNull();

      const positions = result.placements.map(p => p.finish_position).sort();
      expect(positions).toEqual([1, 2]);

      const winner = result.placements.find(p => p.is_winner);
      expect(winner).not.toBeUndefined();
      expect(winner.finish_position).toBe(1);
      expect(winner.result).toBe('win');
    } finally {
      await teardownRoom(clients);
    }
  });

  test('client cannot manually trigger settlement in server-driven mode', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      await clients[0].waitFor('ludo.game.turn_started', null, 12_000);

      // Fake settlement attempt
      clients[0].emit('ludo.match.complete', JSON.stringify({
        room_id: clients[0].roomId,
        winner:  { seat_no: 1, user_id: clients[0].userId },
        placements: [
          { seat_no: 1, finish_position: 1, is_winner: true, score: 100 },
          { seat_no: 2, finish_position: 2, is_winner: false, score: 0 },
        ],
      }));

      const earlyResult = await clients[0].waitFor('ludo.game.result', null, 2_000)
        .catch(() => null);
      expect(earlyResult,
        'Server must block client settlement in server-driven mode'
      ).toBeNull();
    } finally {
      await teardownRoom(clients);
    }
  });

  test('4-player game: result has 4 placements with correct seat numbers', async () => {
    const { clients } = await createRoom({ maxPlayers: 4 });

    try {
      const r0 = await playFullGame(clients, { maxPlayers: 4 });
      const results = [r0, ...await Promise.all(clients.slice(1).map(c =>
        Promise.resolve(c.lastReceived('ludo.game.result'))
          .then(r => r ?? c.waitFor('ludo.game.result', null, 5_000))
      ))];
      assertWinnerSynchronized(results, '4p settlement');
      assertPlacements(results[0].placements, [1, 2, 3, 4], '4p settlement');

      const positions = results[0].placements.map(p => p.finish_position).sort();
      expect(positions).toEqual([1, 2, 3, 4]);
    } finally {
      await teardownRoom(clients);
    }
  });
});
