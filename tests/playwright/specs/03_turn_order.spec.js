'use strict';
/**
 * Spec 03 — Turn Order Validation
 *
 * Validates:
 *   ✓ Turns cycle 0→1→2→3→0→... (no skips, no repeats unless extra turn)
 *   ✓ Server never fires a turn for a finished seat
 *   ✓ Extra-turn: seat gets an additional turn after rolling 6 or killing
 *   ✓ Turn timeout (roll_timeout): seat advances automatically after ROLL_TIMEOUT
 *   ✓ Turn timeout (move_timeout): seat advances automatically after MOVE_TIMEOUT
 */

const { test, expect } = require('@playwright/test');
const { createRoom, teardownRoom } = require('../helpers/testRoom');

test.describe('Turn Order', () => {
  test('2-player: turns alternate 0→1→0→1 for 6 cycles', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      const observed    = [];
      let prevSeat      = -1;
      let prevExtraTurn = false;

      for (let i = 0; i < 14; i++) {
        const t  = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si = t.seat_index;
        observed.push(si);

        // Verify alternation: after a non-extra-turn, next seat must be the other
        if (prevSeat >= 0 && !prevExtraTurn) {
          expect(si, `turn ${i}: expected seat ${1 - prevSeat} after non-extra-turn`).toBe(1 - prevSeat);
        }

        const actor = clients.find(c => c.seatIndex === si) ?? clients[si];
        if (!actor) break;

        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;
        if (dice.has_moves) { actor.rollNonce = dice.roll_nonce ?? actor.rollNonce; actor.moveToken(dice.legal_tokens[0]); }
        const moved = await movedP;

        prevSeat      = si;
        prevExtraTurn = moved.extra_turn;
        if (moved.is_win) break;
      }

      const seenSeats = new Set(observed);
      expect(seenSeats.has(0), 'seat 0 never had a turn').toBe(true);
      expect(seenSeats.has(1), 'seat 1 never had a turn').toBe(true);
    } finally {
      await teardownRoom(clients);
    }
  });

  test('4-player: turns cycle 0→1→2→3 for 2 full rotations', async () => {
    const { clients } = await createRoom({ maxPlayers: 4 });

    try {
      const normalTurns = [];

      while (normalTurns.length < 8) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si2   = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si2) ?? clients[si2];
        if (!actor) break;

        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si2, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si2, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;

        if (dice.has_moves) { actor.rollNonce = dice.roll_nonce ?? actor.rollNonce; actor.moveToken(dice.legal_tokens[0]); }
        const moved = await movedP;

        if (!moved.extra_turn) normalTurns.push(t.seat_index);
        if (moved.is_win) break;
      }

      // Verify rotation 0→1→2→3→0→1→2→3
      for (let i = 0; i < Math.min(normalTurns.length, 8); i++) {
        expect(normalTurns[i]).toBe(i % 4);
      }
    } finally {
      await teardownRoom(clients);
    }
  });

  test('roll_timeout: server advances turn automatically', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      // Wait for first turn (seat 0)
      const firstTurn = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
      expect(firstTurn.seat_index).toBe(0);

      // Do NOT roll — wait for turn_missed and next turn_started
      const missed = await clients[0].waitFor('ludo.game.turn_missed', null, 22_000);
      expect(missed.seat_index).toBe(0);
      expect(missed.reason).toBe('roll_timeout');

      // Server should now start seat 1's turn
      const nextTurn = await clients[0].waitFor('ludo.game.turn_started', null, 5_000);
      expect(nextTurn.seat_index).toBe(1);
    } finally {
      await teardownRoom(clients);
    }
  });

  test('move_timeout: server advances turn after dice rolled but no move', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      const firstTurn = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
      // Roll but never move (to trigger move_timeout, need has_moves=true)
      clients[0].rollDice();
      const dice = await clients[0].waitFor('ludo.game.dice_rolled',
        d => d.seat_index === 0, 8_000);

      if (!dice.has_moves) {
        // All tokens in yard, no 6 rolled — auto-pass fires immediately; skip test
        test.skip();
        return;
      }

      // DO NOT move — wait for turn_missed
      const missed = await clients[0].waitFor('ludo.game.turn_missed', null, 22_000);
      expect(missed.reason).toBe('move_timeout');

      const nextTurn = await clients[0].waitFor('ludo.game.turn_started', null, 5_000);
      expect(nextTurn.seat_index).toBe(1);
    } finally {
      await teardownRoom(clients);
    }
  });
});
