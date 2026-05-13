'use strict';
/**
 * GameClient — thin wrapper around socket.io-client.
 *
 * Provides:
 *   • Promise-based waitFor(event, predicate?, timeout?)
 *   • Action helpers: joinQueue, rollDice, moveToken, reconnect
 *   • Event log for post-hoc assertions
 *   • Automatic nonce tracking (turn_nonce / roll_nonce)
 */

const { io }  = require('socket.io-client');
require('dotenv').config({ path: require('path').resolve(__dirname, '../../.env') });

const SERVER_URL    = process.env.LUDO_SERVER_URL || 'http://localhost:3002';
const NAMESPACE     = process.env.LUDO_NAMESPACE  || '/ludo_v2';
const DEFAULT_TO    = parseInt(process.env.TURN_TIMEOUT || '20000', 10);

class GameClient {
  /**
   * @param {object} opts
   * @param {number} opts.userId      Numeric user id
   * @param {string} [opts.name]      Display name
   * @param {string} [opts.serverUrl]
   * @param {string} [opts.namespace]
   */
  constructor({ userId, name, serverUrl, namespace } = {}) {
    this.userId    = userId;
    this.name      = name || `Player_${userId}`;
    this.serverUrl = serverUrl || SERVER_URL;
    this.namespace = namespace || NAMESPACE;

    this.socket      = null;
    this.roomId      = null;
    this.seatIndex   = null;   // server seat index (0-based)
    this.turnNonce   = null;
    this.rollNonce   = null;
    this.eventLog    = [];     // [{event, data, ts}]
    this._handlers   = new Map();
    this._queues     = new Map(); // event → [data, ...] — unhandled arrivals
    this._waiters    = new Map(); // event → [{test, resolve, reject, timer}, ...]
  }

  // ── Connection ─────────────────────────────────────────────────────────────

  connect() {
    return new Promise((resolve, reject) => {
      // socket.io-client v4: pass base URL + namespace as second arg (path= is socket.io path, not namespace)
      // Correct form: io('http://host:port/namespace')
      const url = this.serverUrl.replace(/\/$/, '') + this.namespace;
      this.socket = io(url, {
        transports:   ['websocket', 'polling'],
        reconnection: false,
        timeout:      8000,
        forceNew:     true,
        path:         '/socket.io',
      });

      this.socket.on('connect', () => {
        this._log('__connected', { id: this.socket.id });
        resolve(this);
      });

      this.socket.on('connect_error', (err) => reject(err));

      // Log every inbound event
      const orig = this.socket.onevent?.bind(this.socket);
      this.socket.onAny((event, data) => {
        this._log(event, data);
        this._dispatch(event, data);
      });

      // Auto-capture nonces
      this.socket.on('ludo.game.turn_started', (d) => {
        if (d?.seat_index === this.seatIndex) {
          this.turnNonce = d.turn_nonce ?? null;
          this.rollNonce = null;
        }
      });
      this.socket.on('ludo.game.dice_rolled', (d) => {
        if (d?.seat_index === this.seatIndex) {
          this.rollNonce = d.roll_nonce ?? null;
          this.turnNonce = null;
        }
      });
      this.socket.on('ludo.game.state', (d) => {
        if (d?.turn_nonce) this.turnNonce = d.turn_nonce;
        if (d?.roll_nonce) this.rollNonce = d.roll_nonce;
      });
      this.socket.on('ludo.room.waiting', (d) => {
        if (d?.room_id) this.roomId = d.room_id;
        this._updateSeatIndex(d);
      });
      this.socket.on('ludo.room.starting', (d) => {
        if (d?.room_id) this.roomId = d.room_id;
        this._updateSeatIndex(d);
      });
      this.socket.on('ludo.game.snapshot', (d) => {
        if (d?.room_id) this.roomId = d.room_id;
        this._updateSeatIndex(d);
      });
    });
  }

  _updateSeatIndex(snapshot) {
    if (!snapshot?.seats) return;
    const seat = snapshot.seats.find(s =>
      String(s?.userId ?? s?.user_id ?? '') === String(this.userId)
    );
    if (seat) this.seatIndex = Math.max(0, (seat.seatNo ?? seat.seat_no ?? 1) - 1);
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
    }
  }

  isConnected() {
    return this.socket?.connected ?? false;
  }

  // ── Event helpers ──────────────────────────────────────────────────────────

  _log(event, data) {
    this.eventLog.push({ event, data, ts: Date.now() });
  }

  _dispatch(event, data) {
    // Legacy handlers (used by auto-capture nonces etc.)
    const handlers = this._handlers.get(event);
    if (handlers) handlers.forEach(h => h(data));

    // FIFO queue: deliver to first matching waiter, or enqueue for future waitFor
    const waiters = this._waiters.get(event);
    if (waiters && waiters.length > 0) {
      for (let i = 0; i < waiters.length; i++) {
        const w = waiters[i];
        let matched = false;
        try { matched = w.test(data); } catch (e) {
          waiters.splice(i, 1);
          clearTimeout(w.timer);
          w.reject(e);
          return;
        }
        if (matched) {
          waiters.splice(i, 1);
          clearTimeout(w.timer);
          w.resolve(data);
          return;
        }
      }
    }
    // No waiter matched — enqueue for a future waitFor call
    if (!this._queues.has(event)) this._queues.set(event, []);
    this._queues.get(event).push(data);
  }

  on(event, handler) {
    if (!this._handlers.has(event)) this._handlers.set(event, new Set());
    this._handlers.get(event).add(handler);
    return () => this._handlers.get(event).delete(handler);
  }

  /**
   * Wait for the next emission of `event` that satisfies predicate.
   * @param {string}   event
   * @param {Function} [predicate]  (data) => bool — defaults to () => true
   * @param {number}   [timeout]    ms
   * @returns {Promise<any>}
   */
  waitFor(event, predicate, timeout = DEFAULT_TO) {
    const test = (typeof predicate === 'function') ? predicate : () => true;

    // Check the FIFO queue for already-arrived but unhandled events (handles same-batch arrivals).
    const queue = this._queues.get(event);
    if (queue && queue.length > 0) {
      for (let i = 0; i < queue.length; i++) {
        let matched = false;
        try { matched = test(queue[i]); } catch (e) { return Promise.reject(e); }
        if (matched) {
          const data = queue.splice(i, 1)[0];
          return Promise.resolve(data);
        }
      }
    }

    // Not in queue — register as a waiter for future delivery
    return new Promise((resolve, reject) => {
      const entry = {
        test, resolve, reject,
        timer: setTimeout(() => {
          const ws = this._waiters.get(event);
          if (ws) { const idx = ws.indexOf(entry); if (idx >= 0) ws.splice(idx, 1); }
          const queueState = [...(this._queues.get(event) || [])].map(d => JSON.stringify(d)).join(' | ');
          const last10 = this.eventLog.slice(-10).map(e => `${e.event}(${JSON.stringify(e.data)?.slice(0,60)})`).join('\n  ');
          reject(new Error(
            `[GameClient uid=${this.userId}] Timeout ${timeout}ms waiting for "${event}".\n` +
            `  Queue[${event}]: ${queueState || 'empty'}\n` +
            `  Last 10 events:\n  ${last10}`
          ));
        }, timeout),
      };
      if (!this._waiters.has(event)) this._waiters.set(event, []);
      this._waiters.get(event).push(entry);
    });
  }

  /** Collect all events of `event` that arrive within `durationMs`. */
  collectFor(event, durationMs) {
    const collected = [];
    const off = this.on(event, (d) => collected.push(d));
    return new Promise(resolve => setTimeout(() => { off(); resolve(collected); }, durationMs));
  }

  // ── Actions ────────────────────────────────────────────────────────────────

  emit(event, data) {
    if (!this.socket?.connected) throw new Error(`[GameClient uid=${this.userId}] Not connected`);
    const payload = typeof data === 'string' ? data : JSON.stringify(data);
    this.socket.emit(event, payload);
  }

  /**
   * Join the ludo_v2 queue.
   * @param {object} opts
   * @param {string}  opts.roomUuid
   * @param {string}  [opts.roomType='public']
   * @param {number}  [opts.maxPlayers=2]
   * @param {number}  [opts.entryFee=0]
   * @param {boolean} [opts.allowBots=false]
   */
  joinQueue({ roomUuid, roomType = 'public', maxPlayers = 2, entryFee = 0, allowBots = false } = {}) {
    this.emit('ludo.queue.join', {
      userId:      this.userId,
      displayName: this.name,
      roomUuid,
      roomType,
      playMode:    entryFee > 0 ? 'cash' : 'practice',
      gameMode:    'CLASSIC',
      maxPlayers,
      entryFee,
      allowBots,
    });
  }

  rollDice() {
    this.emit('ludo.game.roll_dice', {
      room_id:    this.roomId,
      user_id:    this.userId,
      turn_nonce: this.turnNonce ?? '',
    });
  }

  moveToken(tokenIndex) {
    this.emit('ludo.game.move_token', {
      room_id:     this.roomId,
      user_id:     this.userId,
      token_index: tokenIndex,
      roll_nonce:  this.rollNonce ?? '',
    });
  }

  sendReconnect(roomId) {
    this.emit('ludo.session.reconnect', {
      room_id: roomId || this.roomId,
      user_id: this.userId,
    });
  }

  leaveRoom() {
    this.emit('ludo.room.leave', { room_id: this.roomId });
  }

  // ── Convenience ────────────────────────────────────────────────────────────

  /** Returns all events of a given name from the log. */
  received(event) {
    return this.eventLog.filter(e => e.event === event).map(e => e.data);
  }

  lastReceived(event) {
    const all = this.received(event);
    return all[all.length - 1] ?? null;
  }

  clearLog() {
    this.eventLog = [];
    this._queues.clear();
  }

  /** Human-readable event timeline for debugging. */
  timeline() {
    const start = this.eventLog[0]?.ts ?? 0;
    return this.eventLog
      .map(e => `+${(e.ts - start).toString().padStart(6)}ms  ${e.event}`)
      .join('\n');
  }
}

module.exports = { GameClient };
