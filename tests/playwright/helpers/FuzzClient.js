'use strict';
/**
 * FuzzClient — GameClient wrapper that injects randomized latency and chaos.
 *
 * Wraps every emit() and waitFor() with configurable delays to surface:
 *   - race conditions between nonce issue and receipt
 *   - duplicate emits (packet replay)
 *   - out-of-order token_moved / turn_started delivery
 *   - reconnect during roll / move
 */

const { GameClient } = require('./GameClient');

class FuzzClient extends GameClient {
  /**
   * @param {object} opts
   * @param {object} [opts.fuzz]
   * @param {number} [opts.fuzz.emitDelayMs]     max random delay before each emit (default 0)
   * @param {number} [opts.fuzz.disconnectProb]  probability 0–1 of randomly disconnecting mid-turn
   * @param {number} [opts.fuzz.replayProb]      probability 0–1 of replaying last emit (duplicate packet)
   * @param {number} [opts.fuzz.seed]            not used directly — caller controls via Math.random override
   */
  constructor(opts = {}) {
    super(opts);
    const f = opts.fuzz || {};
    this._fuzz = {
      emitDelayMs:    f.emitDelayMs    ?? 0,
      disconnectProb: f.disconnectProb ?? 0,
      replayProb:     f.replayProb     ?? 0,
    };
    this._lastEmitEvent = null;
    this._lastEmitData  = null;
  }

  _randMs(max) { return Math.floor(Math.random() * (max + 1)); }

  // Override emit to inject delay + occasional replay
  emit(event, data) {
    const delay = this._fuzz.emitDelayMs > 0 ? this._randMs(this._fuzz.emitDelayMs) : 0;
    const doEmit = () => {
      if (!this.isConnected()) return;
      super.emit(event, data);
      // Replay: send same packet again (simulates duplicate)
      if (Math.random() < this._fuzz.replayProb) {
        setTimeout(() => {
          try { super.emit(event, data); } catch { /* ignore if disconnected */ }
        }, this._randMs(50));
      }
      this._lastEmitEvent = event;
      this._lastEmitData  = data;
    };
    if (delay > 0) {
      setTimeout(doEmit, delay);
    } else {
      doEmit();
    }
  }

  // Possibly inject a random disconnect before the waiter resolves
  async waitFor(event, predicate, timeout) {
    if (this._fuzz.disconnectProb > 0 && Math.random() < this._fuzz.disconnectProb) {
      const disconnectAfter = this._randMs(200);
      setTimeout(() => {
        if (this.isConnected()) this.disconnect();
      }, disconnectAfter);
    }
    return super.waitFor(event, predicate, timeout);
  }
}

module.exports = { FuzzClient };
