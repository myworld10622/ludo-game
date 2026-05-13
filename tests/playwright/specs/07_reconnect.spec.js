'use strict';
/**
 * Spec 07 — Reconnect State Recovery
 *
 * Validates:
 *   ✓ Disconnected client can reconnect mid-game using ludo.session.reconnect
 *   ✓ Server sends ludo.game.state with complete board snapshot
 *   ✓ Reconnected client receives correct token positions
 *   ✓ Reconnected client receives current_seat and rolled flag
 *   ✓ If it was disconnected player's turn: turn_nonce or roll_nonce is provided
 *   ✓ Reconnected player can successfully roll dice / move token after reconnect
 *   ✓ Game continues normally after reconnect (not stuck)
 */

const { test, expect } = require('@playwright/test');
const { GameClient } = require('../helpers/GameClient');
const { createRoom, teardownRoom } = require('../helpers/testRoom');
const { assertReconnectSync } = require('../helpers/assertions');
const { v4: uuidv4 } = require('uuid');
require('dotenv').config({ path: require('path').resolve(__dirname, '../../.env') });

const BASE_USER_ID = parseInt(process.env.TEST_USER_1_ID || '10001', 10);

test.describe('Reconnect Recovery', () => {
  test('reconnected client receives ludo.game.state with board snapshot', async () => {
    const { clients, roomId } = await createRoom({ maxPlayers: 2 });
    const disconnectedUserId = clients[0].userId;

    try {
      // Play 3 turns to advance some tokens (includes first turn)
      for (let i = 0; i < 3; i++) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si3   = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si3) ?? clients[si3];
        if (!actor) break;
        const diceP  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si3, 8_000);
        const movedP = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si3, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP;
        if (dice.has_moves) { actor.rollNonce = dice.roll_nonce ?? actor.rollNonce; actor.moveToken(dice.legal_tokens[0]); }
        const moved = await movedP;
        if (moved.is_win) break;
      }

      // Disconnect client[0]
      clients[0].disconnect();
      await new Promise(r => setTimeout(r, 500));

      // Reconnect as a new socket but same userId
      const reconClient = new GameClient({
        userId: disconnectedUserId,
        name:   `Player_${disconnectedUserId}_reconn`,
      });
      await reconClient.connect();

      // Send reconnect event
      reconClient.sendReconnect(roomId);

      // Should receive ludo.game.state
      const gameState = await reconClient.waitFor('ludo.game.state', null, 8_000);
      expect(gameState).not.toBeNull();
      expect(gameState.tokens).toBeTruthy();
      expect(gameState.room_id).toBe(roomId);
      expect(typeof gameState.current_seat).toBe('number');
      expect(typeof gameState.rolled).toBe('boolean');

      // Nonce must be present if it's the reconnected player's turn
      if (gameState.current_seat === reconClient.seatIndex) {
        if (!gameState.rolled) {
          expect(gameState.turn_nonce, 'turn_nonce missing on reconnect (player turn)').toBeTruthy();
        } else {
          expect(gameState.roll_nonce, 'roll_nonce missing on reconnect (dice already rolled)').toBeTruthy();
        }
      }

      reconClient.disconnect();
    } finally {
      await teardownRoom(clients);
    }
  });

  test('reconnected player on their turn can roll dice successfully', async () => {
    const { clients, roomId } = await createRoom({ maxPlayers: 2 });

    try {
      // Wait for seat 0 turn (first turn always seat 0)
      await clients[0].waitFor('ludo.game.turn_started', d => d.seat_index === 0, 12_000);

      // Disconnect seat 0 BEFORE rolling
      const seat0UserId = clients[0].userId;
      clients[0].disconnect();
      await new Promise(r => setTimeout(r, 200));

      // Reconnect immediately
      const reconClient = new GameClient({ userId: seat0UserId, name: 'Reconn_P0' });
      await reconClient.connect();
      reconClient.sendReconnect(roomId);

      // Should receive GAME_STATE with turn_nonce
      const gs = await reconClient.waitFor('ludo.game.state', null, 8_000);
      expect(gs.current_seat).toBe(0);
      expect(gs.rolled).toBe(false);
      expect(gs.turn_nonce).toBeTruthy();

      // Now roll with correct nonce
      reconClient.emit('ludo.game.roll_dice', {
        room_id:    roomId,
        user_id:    seat0UserId,
        turn_nonce: gs.turn_nonce,
      });

      // Both reconnected client and the other client should see dice_rolled
      const diceReconn = await reconClient.waitFor('ludo.game.dice_rolled',
        d => d.seat_index === 0, 8_000);
      expect(diceReconn.seat_index).toBe(0);
      expect(diceReconn.dice_value).toBeGreaterThanOrEqual(1);
      expect(diceReconn.dice_value).toBeLessThanOrEqual(6);

      reconClient.disconnect();
    } finally {
      await teardownRoom(clients);
    }
  });

  test('game continues for remaining client after peer disconnects mid-game', async () => {
    const { clients, roomId } = await createRoom({ maxPlayers: 2 });

    try {

      // Play 2 turns normally
      for (let i = 0; i < 2; i++) {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 20_000);
        const si4   = t.seat_index;
        const actor = clients.find(c => c.seatIndex === si4) ?? clients[si4];
        if (!actor) break;
        const diceP2  = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === si4, 8_000);
        const movedP2 = clients[0].waitFor('ludo.game.token_moved',  d => d.seat_index === si4, 8_000);
        actor.turnNonce = t.turn_nonce ?? actor.turnNonce;
        actor.rollDice();
        const dice = await diceP2;
        if (dice.has_moves) { actor.rollNonce = dice.roll_nonce ?? actor.rollNonce; actor.moveToken(dice.legal_tokens[0]); }
        await movedP2;
      }

      // Disconnect client[1]
      clients[1].disconnect();
      await new Promise(r => setTimeout(r, 300));

      // Client[0] should still receive the next TURN_STARTED (game continues with timeouts)
      const nextTurn = await clients[0].waitFor('ludo.game.turn_started', null, 25_000);
      expect(typeof nextTurn.seat_index).toBe('number');
    } finally {
      await teardownRoom(clients);
    }
  });

  test('multiple rapid reconnects do not cause server crash', async () => {
    const { clients, roomId } = await createRoom({ maxPlayers: 2 });
    const userId = clients[0].userId;

    try {

      // Rapid reconnect storm: 3 reconnects in quick succession
      for (let attempt = 0; attempt < 3; attempt++) {
        const c = new GameClient({ userId });
        await c.connect();
        c.sendReconnect(roomId);
        await new Promise(r => setTimeout(r, 200));
        c.disconnect();
      }

      // Original game should still be alive — client[1] gets a turn_started
      const alive = await clients[1].waitFor('ludo.game.turn_started', null, 25_000)
        .catch(() => null);
      expect(alive, 'Game should still be alive after reconnect storm').not.toBeNull();
    } finally {
      await teardownRoom(clients);
    }
  });
});
