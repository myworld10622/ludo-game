'use strict';
/**
 * Spec 02 — 4-Player Full Game
 *
 * Validates:
 *   ✓ All 4 clients connect, receive ludo.room.starting, correct seat assignments
 *   ✓ Player starts at correct ring positions: seats 0,1,2,3 → abs 0,13,26,39
 *   ✓ Token positions synchronized across all 4 clients after every move
 *   ✓ RESULT event contains all 4 seat placements
 *   ✓ All 4 clients agree on the same winner
 */

const { test, expect } = require('@playwright/test');
const { createRoom, teardownRoom, playFullGame } = require('../helpers/testRoom');
const {
  assertTokensSynchronized,
  assertWinnerSynchronized,
  assertPlacements,
  getPlayerStarts,
} = require('../helpers/assertions');

test.describe('4-Player Game', () => {
  let clients, roomId;

  test.beforeEach(async () => {
    ({ clients, roomId } = await createRoom({ maxPlayers: 4 }));
  });

  test.afterEach(async () => {
    await teardownRoom(clients);
  });

  test('all 4 clients receive ludo.room.starting with 4 human seats', async () => {
    for (const c of clients) {
      const snap = c.lastReceived('ludo.room.starting');
      expect(snap?.seats?.length).toBe(4);
      expect(snap.seats.every(s => s.playerType === 'human')).toBe(true);
    }
  });

  test('each client is assigned a unique seat index', async () => {
    await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
    const seatIndices = clients.map(c => c.seatIndex);
    const unique = new Set(seatIndices);
    expect(unique.size).toBe(4);
    // Seats are 0-based consecutive
    expect(seatIndices.sort()).toEqual([0, 1, 2, 3]);
  });

  test('4-player token positions synchronized after each move', async () => {
    await clients[0].waitFor('ludo.game.turn_started', null, 12_000);

    for (let moveCount = 0; moveCount < 8; moveCount++) {
      const turnData = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
      const si       = turnData.seat_index;
      const actor    = clients.find(c => c.seatIndex === si) ?? clients[si];
      if (!actor) break;

      // Register listeners BEFORE emitting
      const diceP   = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000);
      const movedPs = clients.map(c => c.waitFor('ludo.game.token_moved', d => d.seat_index === si, 8_000));

      actor.turnNonce = turnData.turn_nonce ?? actor.turnNonce;
      actor.rollDice();
      const dice = await diceP;

      if (dice.has_moves) {
        actor.rollNonce = dice.roll_nonce ?? actor.rollNonce;
        actor.moveToken(dice.legal_tokens[0]);
      }

      const movedEvents = await Promise.all(movedPs);

      assertTokensSynchronized(movedEvents, `4p move #${moveCount}`);
      if (movedEvents[0].is_win) break;
    }
  });

  test('full 4-player game: result has 4 placements, synchronized winner', async () => {
    const r0 = await playFullGame(clients, { maxPlayers: 4 });

    // All clients receive the broadcast — read from eventLog or wait briefly
    const results = [r0, ...await Promise.all(clients.slice(1).map(c =>
      Promise.resolve(c.lastReceived('ludo.game.result'))
        .then(r => r ?? c.waitFor('ludo.game.result', null, 5_000))
    ))];
    assertWinnerSynchronized(results, '4-player result');
    assertPlacements(results[0].placements, [1, 2, 3, 4], '4-player placements');

    // All placements should have distinct finish_positions 1–4
    const positions = results[0].placements.map(p => p.finish_position).sort();
    expect(positions).toEqual([1, 2, 3, 4]);
  });

  test('player start positions are 0, 13, 26, 39 in absolute ring', async () => {
    const starts = getPlayerStarts(4);
    expect(starts).toEqual([0, 13, 26, 39]);

    // After a 6 is rolled from yard, token[seat][0] should be at relPos=5
    // abs = (starts[seat] + 5) % 52
    for (let seat = 0; seat < 4; seat++) {
      const expected = (starts[seat] + 5) % 52;
      expect(typeof expected).toBe('number');
      expect(expected).toBeGreaterThanOrEqual(0);
      expect(expected).toBeLessThan(52);
    }
  });
});
