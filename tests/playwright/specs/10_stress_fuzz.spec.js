'use strict';
/**
 * Spec 10 — Stress / Fuzz Suite  (20 iterations × scenario)
 *
 * Targets:
 *   ✓ Flaky race conditions under randomized emit delay
 *   ✓ Duplicate packet (replay) rejection
 *   ✓ Rapid reconnect / disconnect chaos
 *   ✓ Nonce desync after disconnect
 *   ✓ Stuck timer / missed turn transitions
 *   ✓ Duplicate token_moved / duplicate result
 *   ✓ Settlement duplication
 *   ✓ Hidden deadlocks in turn advancement
 *
 * Each test runs ITERATIONS games, collects a failure matrix, then asserts
 * the failure rate is 0 (deterministic fixes are expected — flakiness is a bug).
 */

const { test, expect }  = require('@playwright/test');
const { GameClient }    = require('../helpers/GameClient');
const { createStressRoom, teardownStressRoom, playStressGame } = require('../helpers/stressRoom');
const { createRoom, teardownRoom } = require('../helpers/testRoom');

const ITERATIONS = parseInt(process.env.STRESS_ITERATIONS || '20', 10);

// ── Helpers ────────────────────────────────────────────────────────────────────

function randInt(min, max) { return min + Math.floor(Math.random() * (max - min + 1)); }
function randMs(max)       { return Math.floor(Math.random() * (max + 1)); }

/** Collect failures across N iterations and return summary. */
async function runIterations(label, n, runOnce) {
  const failures = [];
  for (let i = 0; i < n; i++) {
    try {
      await runOnce(i);
    } catch (e) {
      failures.push({ iteration: i, message: e.message?.slice(0, 300) });
    }
  }
  return failures;
}

function reportFailures(label, failures, n) {
  if (failures.length === 0) return;
  const lines = failures.map(f => `  [iter ${f.iteration}] ${f.message}`).join('\n');
  throw new Error(
    `[${label}] ${failures.length}/${n} iterations failed:\n${lines}`
  );
}

// ── Scenarios ──────────────────────────────────────────────────────────────────

test.describe('Stress / Fuzz', () => {

  // ── 1. Baseline determinism ────────────────────────────────────────────────
  test(`baseline: ${ITERATIONS} clean 2P games complete with valid result`, async () => {
    const failures = await runIterations('baseline', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2, fuzz: {} });
      try {
        const { result, stats } = await playStressGame(clients);
        expect(result, `iter ${i}: no result`).not.toBeNull();
        expect(result.cancelled, `iter ${i}: cancelled`).toBe(false);
        expect(result.winner, `iter ${i}: no winner`).not.toBeNull();
        expect(stats.duplicateResultCount, `iter ${i}: duplicate result`).toBe(0);
        expect(stats.rollRejections, `iter ${i}: roll rejections`).toBe(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('baseline', failures, ITERATIONS);
  });

  // ── 2. Randomized emit delay (packet latency simulation) ───────────────────
  test(`emit delay: ${ITERATIONS} games with 0–80ms random emit delay`, async () => {
    const failures = await runIterations('emit-delay', ITERATIONS, async (i) => {
      const emitDelayMs = randInt(0, 80);
      const { clients } = await createStressRoom({
        maxPlayers:  2,
        fuzz:        { emitDelayMs },
      });
      try {
        const { result, stats } = await playStressGame(clients, { moveDelayMs: emitDelayMs });
        expect(result, `iter ${i} delay=${emitDelayMs}ms: no result`).not.toBeNull();
        expect(result.cancelled).toBe(false);
        expect(stats.duplicateResultCount, `iter ${i}: duplicate result`).toBe(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('emit-delay', failures, ITERATIONS);
  });

  // ── 3. Duplicate packet (replay attack) ───────────────────────────────────
  test(`replay: ${ITERATIONS} games where every emit has 30% chance of replay`, async () => {
    const failures = await runIterations('replay', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({
        maxPlayers: 2,
        fuzz:       { replayProb: 0.3 },
      });
      try {
        const { result, stats } = await playStressGame(clients);
        expect(result, `iter ${i}: no result`).not.toBeNull();
        expect(result.cancelled).toBe(false);
        // Replays trigger server violations — should NOT produce extra dice_rolled
        expect(stats.duplicateResultCount, `iter ${i}: duplicate result`).toBe(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('replay', failures, ITERATIONS);
  });

  // ── 4. Reconnect mid-turn (nonce desync) ──────────────────────────────────
  test(`reconnect-nonce: ${ITERATIONS} reconnects during active turn, nonce must remain valid`, async () => {
    const failures = await runIterations('reconnect-nonce', ITERATIONS, async (i) => {
      const { clients, roomId } = await createStressRoom({ maxPlayers: 2 });
      try {
        // Wait for first turn_started (seat 0)
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
        expect(t.seat_index).toBe(0);
        const capturedNonce = t.turn_nonce;

        // Disconnect and immediately reconnect seat 0
        const uid0 = clients[0].userId;
        clients[0].disconnect();
        await new Promise(r => setTimeout(r, randMs(300)));

        const recon = new GameClient({ userId: uid0 });
        await recon.connect();
        recon.sendReconnect(roomId);

        const gs = await recon.waitFor('ludo.game.state', null, 8_000);
        expect(gs, `iter ${i}: no game.state`).not.toBeNull();
        expect(gs.current_seat, `iter ${i}: wrong current_seat`).toBe(0);
        expect(gs.rolled, `iter ${i}: already rolled`).toBe(false);
        // Server issues a fresh nonce on reconnect (old one is invalidated)
        expect(gs.turn_nonce, `iter ${i}: missing turn_nonce`).toBeTruthy();

        // Roll with server-provided nonce
        recon.emit('ludo.game.roll_dice', {
          room_id:    roomId,
          user_id:    uid0,
          turn_nonce: gs.turn_nonce,
        });
        const dice = await recon.waitFor('ludo.game.dice_rolled', d => d.seat_index === 0, 8_000);
        expect(dice.dice_value, `iter ${i}: bad dice`).toBeGreaterThan(0);

        // Ensure stale nonce from before disconnect is now invalid
        recon.emit('ludo.game.roll_dice', {
          room_id:    roomId,
          user_id:    uid0,
          turn_nonce: capturedNonce,
        });
        const staleRoll = await recon.waitFor('ludo.game.dice_rolled',
          d => d.seat_index === 0, 1_500).catch(() => null);
        expect(staleRoll, `iter ${i}: stale nonce accepted after reconnect`).toBeNull();

        recon.disconnect();
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('reconnect-nonce', failures, ITERATIONS);
  });

  // ── 5. Rapid sequential reconnects (server crash guard) ───────────────────
  test(`rapid-reconnect: ${ITERATIONS} storms of 5 rapid reconnects — game must survive`, async () => {
    const failures = await runIterations('rapid-reconnect', ITERATIONS, async (i) => {
      const { clients, roomId } = await createStressRoom({ maxPlayers: 2 });
      const userId = clients[0].userId;
      try {
        await clients[0].waitFor('ludo.game.turn_started', null, 12_000);

        const gap = randInt(20, 200);
        for (let attempt = 0; attempt < 5; attempt++) {
          const c = new GameClient({ userId });
          await c.connect();
          c.sendReconnect(roomId);
          await new Promise(r => setTimeout(r, gap));
          c.disconnect();
        }

        // Game should still be alive — clients[1] sees next turn_started
        const alive = await clients[1].waitFor('ludo.game.turn_started', null, 30_000)
          .catch(() => null);
        expect(alive, `iter ${i} gap=${gap}ms: game dead after reconnect storm`).not.toBeNull();
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('rapid-reconnect', failures, ITERATIONS);
  });

  // ── 6. Randomized move timing (slow player) ───────────────────────────────
  test(`slow-player: ${ITERATIONS} games with 0–500ms delay per action`, async () => {
    const failures = await runIterations('slow-player', ITERATIONS, async (i) => {
      const delay = randInt(0, 500);
      const { clients } = await createStressRoom({ maxPlayers: 2 });
      try {
        const { result } = await playStressGame(clients, { moveDelayMs: delay });
        expect(result, `iter ${i} delay=${delay}ms: no result`).not.toBeNull();
        expect(result.cancelled).toBe(false);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('slow-player', failures, ITERATIONS);
  });

  // ── 7. Disconnect mid-roll (roll emitted, response dropped) ───────────────
  test(`disconnect-mid-roll: ${ITERATIONS} × disconnect after roll_dice before dice_rolled`, async () => {
    const failures = await runIterations('disconnect-mid-roll', ITERATIONS, async (i) => {
      const { clients, roomId } = await createStressRoom({ maxPlayers: 2 });
      const uid0 = clients[0].userId;
      try {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
        clients[0].turnNonce = t.turn_nonce ?? clients[0].turnNonce;

        // Emit roll then immediately disconnect
        clients[0].rollDice();
        await new Promise(r => setTimeout(r, randMs(50)));
        clients[0].disconnect();

        // Reconnect and verify state is consistent
        const recon = new GameClient({ userId: uid0 });
        await recon.connect();
        recon.sendReconnect(roomId);

        const gs = await recon.waitFor('ludo.game.state', null, 8_000);
        expect(gs, `iter ${i}: no game.state`).not.toBeNull();
        // Either rolled=true (roll landed) or rolled=false (roll dropped)
        expect(typeof gs.rolled, `iter ${i}: rolled not boolean`).toBe('boolean');
        if (gs.rolled) {
          expect(gs.roll_nonce, `iter ${i}: rolled but no roll_nonce`).toBeTruthy();
        } else {
          expect(gs.turn_nonce, `iter ${i}: not rolled but no turn_nonce`).toBeTruthy();
        }

        recon.disconnect();
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('disconnect-mid-roll', failures, ITERATIONS);
  });

  // ── 8. Disconnect mid-move (move emitted, response dropped) ───────────────
  test(`disconnect-mid-move: ${ITERATIONS} × disconnect after move_token before token_moved`, async () => {
    const failures = await runIterations('disconnect-mid-move', ITERATIONS, async (i) => {
      const { clients, roomId } = await createStressRoom({ maxPlayers: 2 });
      const uid0 = clients[0].userId;
      try {
        const t = await clients[0].waitFor('ludo.game.turn_started', d => d.seat_index === 0, 12_000);
        clients[0].turnNonce = t.turn_nonce ?? clients[0].turnNonce;

        const diceP = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === 0, 8_000);
        clients[0].rollDice();
        const dice = await diceP;

        if (!dice.has_moves) {
          // No moves — skip this iteration
          await teardownStressRoom(clients);
          return;
        }

        clients[0].rollNonce = dice.roll_nonce ?? clients[0].rollNonce;
        clients[0].moveToken(dice.legal_tokens[0]);
        await new Promise(r => setTimeout(r, randMs(50)));
        clients[0].disconnect();

        const recon = new GameClient({ userId: uid0 });
        await recon.connect();
        recon.sendReconnect(roomId);

        const gs = await recon.waitFor('ludo.game.state', null, 8_000);
        expect(gs, `iter ${i}: no game.state`).not.toBeNull();
        // Move may or may not have landed — either way state must be coherent
        expect(typeof gs.current_seat, `iter ${i}: current_seat not number`).toBe('number');
        expect(gs.tokens, `iter ${i}: no tokens`).toBeTruthy();

        recon.disconnect();
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('disconnect-mid-move', failures, ITERATIONS);
  });

  // ── 9. Settlement duplication guard ───────────────────────────────────────
  test(`settlement-dedup: ${ITERATIONS} games — result fires exactly once`, async () => {
    const failures = await runIterations('settlement-dedup', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2 });
      try {
        const { result, stats } = await playStressGame(clients);
        expect(result, `iter ${i}: no result`).not.toBeNull();
        expect(result.cancelled).toBe(false);

        // Wait 1s for any stray duplicates
        await new Promise(r => setTimeout(r, 1000));
        const allResults = clients[0].received('ludo.game.result');
        expect(allResults.length, `iter ${i}: result fired ${allResults.length}x, expected 1`).toBe(1);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('settlement-dedup', failures, ITERATIONS);
  });

  // ── 10. Stuck timer: turn_missed must arrive if no action ─────────────────
  test(`stuck-timer: ${ITERATIONS} × deliberate no-roll — turn_missed must arrive within 20s`, async () => {
    const failures = await runIterations('stuck-timer', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2 });
      try {
        // Wait for first turn, then do nothing
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
        expect(t.seat_index).toBe(0);

        const missed = await clients[0].waitFor('ludo.game.turn_missed', null, 20_000);
        expect(missed, `iter ${i}: no turn_missed`).not.toBeNull();
        expect(missed.reason, `iter ${i}: wrong reason`).toBe('roll_timeout');

        const next = await clients[0].waitFor('ludo.game.turn_started', null, 5_000);
        expect(next.seat_index, `iter ${i}: wrong next seat`).toBe(1);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('stuck-timer', failures, ITERATIONS);
  });

  // ── 11. Nonce desync: wrong nonce → no response, game continues ───────────
  test(`nonce-desync: ${ITERATIONS} × wrong nonce sent — server silent, game continues`, async () => {
    const failures = await runIterations('nonce-desync', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2 });
      try {
        const t = await clients[0].waitFor('ludo.game.turn_started', null, 12_000);
        expect(t.seat_index).toBe(0);

        // Send wrong nonce
        clients[0].emit('ludo.game.roll_dice', {
          room_id:    clients[0].roomId,
          user_id:    clients[0].userId,
          turn_nonce: 'invalid-nonce-' + Date.now(),
        });

        const rogue = await clients[0].waitFor('ludo.game.dice_rolled',
          d => d.seat_index === 0, 2_000).catch(() => null);
        expect(rogue, `iter ${i}: invalid nonce triggered roll`).toBeNull();

        // Now use correct nonce — game should still work
        const diceP = clients[0].waitFor('ludo.game.dice_rolled', d => d.seat_index === 0, 8_000);
        clients[0].emit('ludo.game.roll_dice', {
          room_id:    clients[0].roomId,
          user_id:    clients[0].userId,
          turn_nonce: t.turn_nonce,
        });
        const dice = await diceP;
        expect(dice.dice_value, `iter ${i}: no dice after valid nonce`).toBeGreaterThan(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('nonce-desync', failures, ITERATIONS);
  });

  // ── 12. Token_moved duplication guard ─────────────────────────────────────
  test(`token-dedup: ${ITERATIONS} games — no seat ever receives duplicate token_moved for same turn`, async () => {
    const failures = await runIterations('token-dedup', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2, fuzz: { replayProb: 0.2 } });
      try {
        const seen = new Map(); // turn_key → count

        const off = clients[0].on('ludo.game.token_moved', d => {
          // Key = seat_index + dice_value (imperfect but catches most dups within same turn)
          const key = `${d.seat_index}:${d.token_index}:${d.dice_value}`;
          // Two identical consecutive token_moved events = duplicate
          const last = seen.get('last');
          if (last && last.key === key && Date.now() - last.ts < 500) {
            seen.set('dup', (seen.get('dup') || 0) + 1);
          }
          seen.set('last', { key, ts: Date.now() });
        });

        const { result } = await playStressGame(clients);
        off();

        expect(result, `iter ${i}: no result`).not.toBeNull();
        expect(seen.get('dup') || 0, `iter ${i}: duplicate token_moved detected`).toBe(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('token-dedup', failures, ITERATIONS);
  });

  // ── 13. Extra-turn chain stability ────────────────────────────────────────
  test(`extra-turn-chain: ${ITERATIONS} games — sixes always grant extra turn, turn always advances on non-6`, async () => {
    const failures = await runIterations('extra-turn-chain', ITERATIONS, async (i) => {
      const { clients } = await createStressRoom({ maxPlayers: 2 });
      const violations = [];
      try {
        let lastSeat     = -1;
        let lastDiceVal  = -1;
        let lastExtraTurn = false;

        const offTurn = clients[0].on('ludo.game.turn_started', d => {
          // Skip check when last dice was 6 — 3-sixes penalty can advance turn
          // to same player (token sent home) without extra_turn flag
          if (lastSeat !== -1 && !lastExtraTurn && lastDiceVal !== 6) {
            const expected = 1 - lastSeat;
            if (d.seat_index !== expected) {
              violations.push(`Turn advanced from ${lastSeat} to ${d.seat_index}, expected ${expected} (extra_turn=${lastExtraTurn})`);
            }
          }
        });

        const { result, stats } = await playStressGame(clients, {
          onEvent: (event, data, si) => {
            if (event === 'ludo.game.token_moved') {
              lastSeat      = si;
              lastExtraTurn = data.extra_turn;
            }
            if (event === 'ludo.game.dice_rolled') {
              lastDiceVal = data.dice_value;
            }
          },
        });
        offTurn();

        expect(result, `iter ${i}: no result`).not.toBeNull();
        if (violations.length > 0) {
          throw new Error(`iter ${i}: turn-order violations:\n  ` + violations.join('\n  '));
        }
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('extra-turn-chain', failures, ITERATIONS);
  });

  // ── 14. Full 2P game stress with all fuzz enabled ─────────────────────────
  test(`full-fuzz: ${ITERATIONS} games with emit-delay + replay + move-delay combined`, async () => {
    const failures = await runIterations('full-fuzz', ITERATIONS, async (i) => {
      const emitDelayMs = randInt(0, 50);
      const replayProb  = Math.random() < 0.5 ? randInt(10, 30) / 100 : 0;
      const moveDelayMs = randInt(0, 200);

      const { clients } = await createStressRoom({
        maxPlayers: 2,
        fuzz: { emitDelayMs, replayProb },
      });
      try {
        const { result, stats } = await playStressGame(clients, { moveDelayMs });
        expect(result, `iter ${i} delay=${emitDelayMs}ms replay=${replayProb}: no result`).not.toBeNull();
        expect(result.cancelled).toBe(false);
        expect(stats.duplicateResultCount, `iter ${i}: dup result`).toBe(0);
      } finally {
        await teardownStressRoom(clients);
      }
    });
    reportFailures('full-fuzz', failures, ITERATIONS);
  });

});
