'use strict';
/**
 * Spec 01 — 2-Player Full Game
 *
 * Validates:
 *   ✓ Both clients connect and receive ludo.room.starting
 *   ✓ Server fires first turn after ~5.5 s
 *   ✓ All TURN_STARTED events are received by both clients
 *   ✓ Token positions are identical on all clients after every move
 *   ✓ RESULT event fires, winner is non-null, placements contain both seats
 *   ✓ Both clients receive the same winner
 */

const { test, expect } = require('@playwright/test');
const { createRoom, teardownRoom, playFullGame } = require('../helpers/testRoom');
const {
  assertTokensSynchronized,
  assertWinnerSynchronized,
  assertPlacements,
} = require('../helpers/assertions');

test.describe('2-Player Game', () => {
  let clients, roomId;

  test.beforeEach(async () => {
    ({ clients, roomId } = await createRoom({ maxPlayers: 2 }));
  });

  test.afterEach(async () => {
    await teardownRoom(clients);
  });

  test('both clients receive ludo.room.starting with correct seat count', async () => {
    for (const c of clients) {
      const snap = c.lastReceived('ludo.room.starting');
      expect(snap, `client uid=${c.userId} missing room.starting`).not.toBeNull();
      expect(snap.seats?.length).toBe(2);
      expect(snap.seats.every(s => s.playerType === 'human')).toBe(true);
    }
  });

  test('server fires ludo.game.turn_started for seat 0 first', async () => {
    const turns = await Promise.all(
      clients.map(c => c.waitFor('ludo.game.turn_started', null, 12_000))
    );
    for (const t of turns) {
      expect(t.seat_index).toBe(0);
      expect(typeof t.turn_nonce).toBe('string');
      expect(t.turn_nonce.length).toBeGreaterThan(0);
    }
  });

  test('all token_moved events are synchronized across both clients', async () => {
    // Play 10 moves and verify token snapshots match between clients
    let moveCount = 0;

    // Prime: wait for first turn
    await clients[0].waitFor('ludo.game.turn_started', null, 12_000);

    while (moveCount < 10) {
      const turnData = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
      const si2      = turnData.seat_index;
      const actor    = clients.find(c => c.seatIndex === si2) ?? clients[si2];
      if (!actor) break;

      // Register listeners BEFORE emitting to avoid race condition
      const dicePromise   = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si2, 8_000);
      const moved0Promise = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si2, 8_000);

      actor.turnNonce = turnData.turn_nonce ?? actor.turnNonce;
      actor.rollDice();
      const dice = await dicePromise;

      if (dice.has_moves) {
        actor.rollNonce = dice.roll_nonce ?? actor.rollNonce;
        actor.moveToken(dice.legal_tokens[0]);
      }

      // Collect TOKEN_MOVED sequentially — register clients[1] after clients[0] resolves
      // to avoid consuming stale queued events on clients[1]
      const moved0 = await moved0Promise;
      const moved1 = await clients[1].waitFor('ludo.game.token_moved', d => d.seat_index === si2, 8_000);

      assertTokensSynchronized([moved0, moved1], `move #${moveCount}`);
      if (moved0.is_win) break;
      moveCount++;
    }
  });

  test('full game completes with valid result and synchronized winner', async () => {
    // Play full game — playFullGame returns clients[0]'s result
    const r0 = await playFullGame(clients, { maxPlayers: 2 });

    // clients[1] receives the same broadcast; it's in eventLog (via onAny) even if queue consumed it
    const r1 = clients[1].lastReceived('ludo.game.result')
      ?? await clients[1].waitFor('ludo.game.result', null, 5_000);

    assertWinnerSynchronized([r0, r1], '2-player result');
    assertPlacements(r0.placements, [1, 2], '2-player placements');
    expect(r0.cancelled).toBe(false);
    expect(r0.winner?.seat_no).toBeGreaterThanOrEqual(1);
  });
});
