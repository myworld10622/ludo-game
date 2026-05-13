'use strict';
/**
 * BotOrchestrator — create N bots, connect them to a room, and run a full game.
 *
 * CLI usage:
 *   node bots/BotOrchestrator.js [options]
 *
 *   --players 2|4         number of players (default: 2)
 *   --games N             number of games to run (default: 1)
 *   --strategy S          random|optimal (default: optimal)
 *   --server URL          server URL (default: env LUDO_SERVER_URL)
 *   --verbose             print every turn
 *   --report              print summary stats at end
 *
 * Programmatic usage:
 *   const { BotOrchestrator } = require('./BotOrchestrator');
 *   const orch = new BotOrchestrator({ maxPlayers: 4, strategy: 'optimal' });
 *   const stats = await orch.runGames(3);
 */

const { v4: uuidv4 }  = require('uuid');
const { GameClient }  = require('../playwright/helpers/GameClient');
const { GameBot }     = require('./GameBot');
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });

const BASE_USER_ID  = parseInt(process.env.TEST_USER_1_ID || '10001', 10);
const SERVER_URL    = process.env.LUDO_SERVER_URL || 'http://localhost:3002';

class BotOrchestrator {
  /**
   * @param {object} opts
   * @param {number}  [opts.maxPlayers=2]
   * @param {string}  [opts.strategy='optimal']
   * @param {string}  [opts.serverUrl]
   * @param {boolean} [opts.verbose=false]
   */
  constructor({ maxPlayers = 2, strategy = 'optimal', serverUrl, verbose = false } = {}) {
    this.maxPlayers = maxPlayers;
    this.strategy   = strategy;
    this.serverUrl  = serverUrl || SERVER_URL;
    this.verbose    = verbose;
    this.stats      = { gamesPlayed: 0, wins: {}, avgTurns: 0, totalTurns: 0, errors: 0 };
  }

  log(...args) { if (this.verbose) console.log('[Orchestrator]', ...args); }

  /**
   * Run N complete games sequentially and return aggregate stats.
   */
  async runGames(n = 1) {
    for (let i = 0; i < n; i++) {
      console.log(`\n── Game ${i + 1}/${n} (${this.maxPlayers}p, ${this.strategy}) ──`);
      try {
        await this._runOneGame();
      } catch (err) {
        console.error(`Game ${i + 1} error:`, err.message);
        this.stats.errors++;
      }
    }

    this._printStats();
    return this.stats;
  }

  async _runOneGame() {
    const roomUuid = uuidv4();
    const clients  = [];
    const bots     = [];

    // Create clients
    for (let i = 0; i < this.maxPlayers; i++) {
      const c = new GameClient({
        userId:    BASE_USER_ID + i,
        name:      `Bot_${i + 1}`,
        serverUrl: this.serverUrl,
      });
      await c.connect();
      clients.push(c);

      const bot = new GameBot({
        client:   c,
        strategy: this.strategy,
        verbose:  this.verbose,
        onTurn:   (si, dv, ti) => this.log(`  seat${si} dice=${dv} token=${ti}`),
      });
      bots.push(bot);
    }

    // Join room
    clients[0].joinQueue({ roomUuid, maxPlayers: this.maxPlayers });
    for (let i = 1; i < this.maxPlayers; i++) {
      clients[i].joinQueue({ roomUuid, maxPlayers: this.maxPlayers });
    }

    // Wait for all to receive starting
    await Promise.all(clients.map(c =>
      c.waitFor('ludo.room.starting', null, 15_000)
    ));
    this.log(`Room ${roomUuid} started.`);

    // Run all bots concurrently
    const results = await Promise.allSettled(bots.map(b => b.play()));

    // Collect stats
    const successResults = results
      .filter(r => r.status === 'fulfilled' && r.value)
      .map(r => r.value);

    if (successResults.length > 0) {
      const result = successResults[0];
      const winnerSeat = result?.winner?.seat_no;
      if (winnerSeat != null) {
        this.stats.wins[winnerSeat] = (this.stats.wins[winnerSeat] ?? 0) + 1;
      }
      const avgTurns = bots.reduce((s, b) => s + b.turnCount, 0) / bots.length;
      this.stats.totalTurns += avgTurns;
      this.stats.gamesPlayed++;

      console.log(`  Winner: seat ${winnerSeat} | Avg turns: ${avgTurns.toFixed(1)}`);
    }

    // Cleanup
    for (const c of clients) { try { c.disconnect(); } catch { /* ignore */ } }
  }

  _printStats() {
    if (this.stats.gamesPlayed === 0) return;
    console.log('\n═══ BotOrchestrator Summary ═══');
    console.log(`Games played : ${this.stats.gamesPlayed}`);
    console.log(`Errors       : ${this.stats.errors}`);
    console.log(`Avg turns/game: ${(this.stats.totalTurns / this.stats.gamesPlayed).toFixed(1)}`);
    console.log('Wins by seat :');
    const total = this.stats.gamesPlayed;
    for (const [seat, wins] of Object.entries(this.stats.wins)) {
      const pct = ((wins / total) * 100).toFixed(1);
      console.log(`  Seat ${seat}: ${wins} (${pct}%)`);
    }
  }
}

// ── CLI entrypoint ────────────────────────────────────────────────────────────

if (require.main === module) {
  const args = process.argv.slice(2);
  function getArg(flag, def) {
    const i = args.indexOf(flag);
    return i >= 0 ? args[i + 1] : def;
  }

  const maxPlayers = parseInt(getArg('--players', '2'), 10);
  const games      = parseInt(getArg('--games',   '1'), 10);
  const strategy   = getArg('--strategy', 'optimal');
  const server     = getArg('--server', SERVER_URL);
  const verbose    = args.includes('--verbose');

  const orch = new BotOrchestrator({ maxPlayers, strategy, serverUrl: server, verbose });
  orch.runGames(games).then(() => process.exit(0)).catch(err => {
    console.error(err);
    process.exit(1);
  });
}

module.exports = { BotOrchestrator };
