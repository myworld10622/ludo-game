'use strict';
/**
 * GameBot — autonomous Ludo player that drives a GameClient through a full game.
 *
 * Strategies:
 *   'random'   — picks any legal token at random
 *   'optimal'  — prefers: kill > entry > farthest-ahead > random
 *   'scripted' — caller provides a queue of (tokenIndex) instructions
 *
 * Usage:
 *   const bot = new GameBot({ client, strategy: 'optimal' });
 *   const result = await bot.play();   // resolves with ludo.game.result payload
 */

const { GameClient } = require('../playwright/helpers/GameClient');

const STRATEGIES = {
  random:   selectRandom,
  optimal:  selectOptimal,
  scripted: selectScripted,
};

// ── Strategy implementations ──────────────────────────────────────────────────

function selectRandom(legalTokens /*, tokens, seatIndex, diceValue, maxPlayers */) {
  return legalTokens[Math.floor(Math.random() * legalTokens.length)];
}

function selectOptimal(legalTokens, tokens, seatIndex, diceValue, maxPlayers) {
  if (legalTokens.length === 0) return null;
  if (legalTokens.length === 1) return legalTokens[0];

  const PLAYER_STARTS = { 2: [0, 26], 3: [0, 13, 26], 4: [0, 13, 26, 39] };
  const SAFE_ABS      = new Set([0, 8, 13, 21, 26, 34, 39, 47]);
  const starts        = PLAYER_STARTS[maxPlayers] ?? PLAYER_STARTS[2];
  const RING          = 52;
  const HOME_POS      = 56;

  const myStarts = starts[seatIndex];
  const oppStarts = starts.filter((_, i) => i !== seatIndex);

  function absPos(rel, seat) {
    if (rel < 0 || rel > 50) return -1;
    return (starts[seat] + rel) % RING;
  }

  function simulateNewPos(ti) {
    const old = tokens[seatIndex][ti];
    return Math.min(old + diceValue, HOME_POS);
  }

  function wouldKill(ti) {
    const newPos = simulateNewPos(ti);
    if (newPos < 0 || newPos > 50) return false;
    const myAbs = absPos(newPos, seatIndex);
    if (myAbs < 0 || SAFE_ABS.has(myAbs)) return false;
    return oppStarts.some((_, oppSeatOffset) => {
      const oppSeat = starts.indexOf(oppStarts[oppSeatOffset]);
      return (tokens[oppSeat] ?? []).some(op => {
        if (op < 0 || op > 50) return false;
        return absPos(op, oppSeat) === myAbs;
      });
    });
  }

  // Priority 1: kill
  const killers = legalTokens.filter(ti => wouldKill(ti));
  if (killers.length > 0) return killers[0];

  // Priority 2: entry from yard (bring token in)
  const entries = legalTokens.filter(ti => tokens[seatIndex][ti] === -1);
  if (entries.length > 0) return entries[0];

  // Priority 3: farthest-ahead token
  const sorted = [...legalTokens].sort((a, b) =>
    tokens[seatIndex][b] - tokens[seatIndex][a]
  );
  return sorted[0];
}

function selectScripted(legalTokens, tokens, seatIndex, diceValue, maxPlayers, scriptQueue) {
  if (scriptQueue && scriptQueue.length > 0) {
    const next = scriptQueue.shift();
    if (legalTokens.includes(next)) return next;
  }
  return selectRandom(legalTokens);
}

// ── GameBot class ─────────────────────────────────────────────────────────────

class GameBot {
  /**
   * @param {object} opts
   * @param {GameClient}  opts.client
   * @param {string}      [opts.strategy='optimal']  'random'|'optimal'|'scripted'
   * @param {number[]}    [opts.script]    token indices for scripted strategy
   * @param {number}      [opts.maxTurns=400]
   * @param {Function}    [opts.onTurn]    callback(seatIndex, diceValue, tokenIndex)
   * @param {boolean}     [opts.verbose]
   */
  constructor({ client, strategy = 'optimal', script = [], maxTurns = 400, onTurn, verbose = false }) {
    this.client    = client;
    this.strategy  = strategy;
    this.script    = [...script];
    this.maxTurns  = maxTurns;
    this.onTurn    = onTurn;
    this.verbose   = verbose;
    this.turnCount = 0;
    this.stopped   = false;
  }

  stop() { this.stopped = true; }

  log(...args) { if (this.verbose) console.log(`[Bot uid=${this.client.userId}]`, ...args); }

  /**
   * Play the game to completion.
   * @returns {Promise<object>} ludo.game.result payload
   */
  async play() {
    const c = this.client;

    // Wait for game to start
    const starting = await c.waitFor('ludo.room.starting', null, 15_000)
      .catch(() => null);

    // Get maxPlayers from snapshot
    const snap       = c.lastReceived('ludo.room.starting') ?? c.lastReceived('ludo.game.snapshot');
    const maxPlayers = snap?.seats?.length ?? 2;

    this.log(`Game started. maxPlayers=${maxPlayers} strategy=${this.strategy}`);

    // Wait for first turn
    await c.waitFor('ludo.game.turn_started', null, 12_000);

    while (!this.stopped && this.turnCount < this.maxTurns) {
      const turn = await c.waitFor('ludo.game.turn_started', null, 20_000)
        .catch(() => null);
      if (!turn) break;

      const si = turn.seat_index;

      if (si !== c.seatIndex) {
        // Not my turn — wait for the result of this turn passively
        await c.waitFor('ludo.game.token_moved', d => d.seat_index === si, 20_000)
          .catch(() => {});
        const result = c.lastReceived('ludo.game.result');
        if (result) return result;
        continue;
      }

      // My turn — roll
      this.log(`Turn ${this.turnCount}: rolling dice`);
      c.rollDice();

      const dice = await c.waitFor('ludo.game.dice_rolled', d => d.seat_index === si, 8_000)
        .catch(() => null);
      if (!dice) break;

      this.log(`  dice=${dice.dice_value} legal=${JSON.stringify(dice.legal_tokens)}`);

      if (dice.has_moves) {
        // Build current tokens snapshot from last TOKEN_MOVED event
        const lastMoved = c.lastReceived('ludo.game.token_moved');
        const tokens    = lastMoved?.tokens ?? Array(maxPlayers).fill(null).map(() => Array(4).fill(-1));

        const tokenIndex = this._selectToken(dice.legal_tokens, tokens, si, dice.dice_value, maxPlayers);
        this.log(`  moving token[${tokenIndex}]`);
        c.moveToken(tokenIndex);

        if (this.onTurn) this.onTurn(si, dice.dice_value, tokenIndex);
      }

      const moved = await c.waitFor('ludo.game.token_moved', d => d.seat_index === si, 8_000)
        .catch(() => null);
      if (!moved) break;

      if (moved.is_win) {
        this.log('Win! Waiting for result...');
        const result = await c.waitFor('ludo.game.result', null, 10_000).catch(() => null);
        return result;
      }

      const result = c.lastReceived('ludo.game.result');
      if (result) return result;

      this.turnCount++;
    }

    // Safety: wait for result that may have already arrived
    return c.lastReceived('ludo.game.result') ??
      await c.waitFor('ludo.game.result', null, 5_000).catch(() => null);
  }

  _selectToken(legalTokens, tokens, seatIndex, diceValue, maxPlayers) {
    const fn = STRATEGIES[this.strategy] ?? STRATEGIES.optimal;
    if (this.strategy === 'scripted') {
      return fn(legalTokens, tokens, seatIndex, diceValue, maxPlayers, this.script);
    }
    return fn(legalTokens, tokens, seatIndex, diceValue, maxPlayers);
  }
}

module.exports = { GameBot };
