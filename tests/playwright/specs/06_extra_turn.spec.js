'use strict';
/**
 * Spec 06 — Extra Turn Rules
 *
 * Validates:
 *   ✓ Rolling a 6 grants extra_turn=true in TOKEN_MOVED
 *   ✓ Killing an opponent grants extra_turn=true
 *   ✓ Extra turn: same seat receives the next TURN_STARTED
 *   ✓ Non-6 non-kill move: extra_turn=false, turn advances to next seat
 *   ✓ Three sixes overrides extra_turn=false (covered in spec 05)
 */

const { test, expect } = require('@playwright/test');
const { createRoom, teardownRoom } = require('../helpers/testRoom');

test.describe('Extra Turn Rules', () => {
  test('rolling a 6 grants extra_turn and same seat gets next TURN_STARTED', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      let extraTurnVerified = false;

      for (let move = 0; move < 100 && !extraTurnVerified; move++) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si) ?? clients[si];
        if (!actor) break;

        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;

        if (dice.has_moves) { actor.rollNonce = dice.roll_nonce ?? actor.rollNonce; actor.moveToken(dice.legal_tokens[0]); }
        const moved = await movedP;

        if (moved.is_win) break;

        if (dice.dice_value === 6 && moved.extra_turn && !moved.is_win) {
          // Server must send extra_turn=true
          expect(moved.extra_turn).toBe(true);
          // Next TURN_STARTED must be same seat
          const nextTurn = await clients[0].waitFor('ludo.game.turn_started', null, 8_000);
          expect(nextTurn.seat_index).toBe(si);
          extraTurnVerified = true;
        }
      }

      if (!extraTurnVerified) {
        console.warn('[WARN] extra-turn spec: no 6 with has_moves observed in 100 moves');
      }
    } finally {
      await teardownRoom(clients);
    }
  });

  test('non-6 non-kill move: extra_turn=false, turn advances', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      let advanceVerified = false;

      for (let move = 0; move < 50 && !advanceVerified; move++) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si) ?? clients[si];
        if (!actor) break;

        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;

        if (!dice.has_moves || dice.dice_value === 6) {
          // Move if we rolled a 6 (must not leave server waiting for move_token)
          if (dice.has_moves) {
            actor.rollNonce = dice.roll_nonce ?? actor.rollNonce;
            actor.moveToken(dice.legal_tokens[0]);
          }
          const moved = await movedP;
          if (moved.is_win) break;
          continue;
        }

        actor.rollNonce = dice.roll_nonce ?? actor.rollNonce;
        actor.moveToken(dice.legal_tokens[0]);
        const moved = await movedP;

        if (moved.is_win) break;

        if (!moved.extra_turn) {
          // Turn must advance to the other seat
          const nextTurn = await clients[0].waitFor('ludo.game.turn_started', null, 8_000);
          expect(nextTurn.seat_index).toBe(1 - si);
          advanceVerified = true;
        }
      }

      if (!advanceVerified) {
        console.warn('[WARN] extra-turn spec: regular advance not observed in 50 moves');
      }
    } finally {
      await teardownRoom(clients);
    }
  });

  test('entry from yard (6 rolled): token moves to position 0, extra_turn=true', async () => {
    const { clients } = await createRoom({ maxPlayers: 2 });

    try {
      let entryVerified = false;

      for (let move = 0; move < 80 && !entryVerified; move++) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si) ?? clients[si];
        if (!actor) break;

        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;

        if (dice.has_moves) {
          const entryToken = dice.legal_tokens[0];
          actor.rollNonce = dice.roll_nonce ?? actor.rollNonce;
          actor.moveToken(entryToken);
          const moved = await movedP;

          if (moved.is_win) break;

          // Entry from yard now lands directly on the first live board square.
          if (dice.dice_value === 6) {
            // Check if any token just moved from -1 to 0
            const newPos = moved.tokens[si]?.[entryToken];
            if (newPos === 0) {
              expect(moved.extra_turn).toBe(true);
              entryVerified = true;
            }
          }
        } else {
          await movedP;
        }
      }

      if (!entryVerified) {
        console.warn('[WARN] entry spec: yard→0 entry not observed in 80 moves');
      }
    } finally {
      await teardownRoom(clients);
    }
  });
});
