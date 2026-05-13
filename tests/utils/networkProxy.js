'use strict';
/**
 * networkProxy.js — TCP proxy that injects network conditions between
 * the test clients and the real Ludo server.
 *
 * Conditions supported:
 *   latency      — adds artificial delay to every packet (ms)
 *   jitter       — random additional delay per packet (ms)
 *   dropRate     — probability [0,1] that a packet is silently dropped
 *   duplicateRate— probability [0,1] that a packet is sent twice
 *   pause        — block all traffic for N ms
 *
 * Usage (standalone):
 *   node utils/networkProxy.js [--port 3099] [--target 3002] [--latency 200]
 *
 * Usage (programmatic):
 *   const { NetworkProxy } = require('./utils/networkProxy');
 *   const proxy = new NetworkProxy({ proxyPort: 3099, targetPort: 3002 });
 *   await proxy.start();
 *   proxy.setConditions({ latency: 200, jitter: 50 });
 *   // ... run tests via proxy port ...
 *   proxy.setConditions({});   // clear conditions
 *   await proxy.stop();
 *
 * This is a raw TCP proxy; it transparently forwards HTTP upgrade (WebSocket)
 * traffic so socket.io-client connections pointed at proxyPort behave identically
 * to direct connections, just with the configured conditions applied.
 */

const net  = require('net');
const http = require('http');

require('dotenv').config({ path: require('path').resolve(__dirname, '../.env') });

const DEFAULT_PROXY_PORT  = parseInt(process.env.PROXY_PORT || '3099', 10);
const DEFAULT_TARGET_PORT = 3002;
const DEFAULT_TARGET_HOST = '127.0.0.1';

class NetworkProxy {
  /**
   * @param {object} opts
   * @param {number}  [opts.proxyPort=3099]
   * @param {number}  [opts.targetPort=3002]
   * @param {string}  [opts.targetHost='127.0.0.1']
   * @param {boolean} [opts.verbose=false]
   */
  constructor({ proxyPort, targetPort, targetHost, verbose } = {}) {
    this.proxyPort  = proxyPort  || DEFAULT_PROXY_PORT;
    this.targetPort = targetPort || DEFAULT_TARGET_PORT;
    this.targetHost = targetHost || DEFAULT_TARGET_HOST;
    this.verbose    = verbose    || false;

    /** @type {{ latency?: number, jitter?: number, dropRate?: number, duplicateRate?: number, paused?: boolean }} */
    this.conditions = {};

    this._server     = null;
    this._sockets    = new Set();
    this._pauseTimer = null;
  }

  // ── Public API ─────────────────────────────────────────────────────────────

  /**
   * Start the proxy server.
   * @returns {Promise<void>}
   */
  start() {
    return new Promise((resolve, reject) => {
      this._server = net.createServer(clientSocket => {
        this._handleClient(clientSocket);
      });

      this._server.on('error', reject);
      this._server.listen(this.proxyPort, '0.0.0.0', () => {
        console.log(`[NetworkProxy] Listening on :${this.proxyPort} → :${this.targetPort}`);
        resolve();
      });
    });
  }

  /**
   * Stop the proxy and close all connections.
   */
  stop() {
    return new Promise((resolve) => {
      for (const s of this._sockets) {
        try { s.destroy(); } catch { /* ignore */ }
      }
      this._sockets.clear();
      if (this._server) {
        this._server.close(() => resolve());
      } else {
        resolve();
      }
    });
  }

  /**
   * Update network conditions on the fly.
   * @param {object} cond
   * @param {number}  [cond.latency]       fixed delay in ms
   * @param {number}  [cond.jitter]        random additional ms per packet
   * @param {number}  [cond.dropRate]      0.0–1.0
   * @param {number}  [cond.duplicateRate] 0.0–1.0
   */
  setConditions(cond = {}) {
    this.conditions = { ...cond };
    if (this.verbose) console.log('[NetworkProxy] Conditions:', this.conditions);
  }

  /**
   * Pause all traffic for `durationMs` milliseconds.
   */
  pause(durationMs) {
    this.conditions.paused = true;
    if (this._pauseTimer) clearTimeout(this._pauseTimer);
    this._pauseTimer = setTimeout(() => {
      this.conditions.paused = false;
      this._pauseTimer = null;
      if (this.verbose) console.log('[NetworkProxy] Pause lifted.');
    }, durationMs);
    if (this.verbose) console.log(`[NetworkProxy] Pausing for ${durationMs}ms`);
  }

  /**
   * Convenience presets.
   */
  simulateLag(latencyMs = 200, jitterMs = 50) {
    this.setConditions({ latency: latencyMs, jitter: jitterMs });
  }

  simulatePacketLoss(dropRate = 0.15) {
    this.setConditions({ dropRate });
  }

  simulateDuplicates(rate = 0.20) {
    this.setConditions({ duplicateRate: rate });
  }

  clearConditions() {
    this.setConditions({});
  }

  // ── Internal ───────────────────────────────────────────────────────────────

  _handleClient(clientSocket) {
    this._sockets.add(clientSocket);

    const targetSocket = net.createConnection({
      host: this.targetHost,
      port: this.targetPort,
    });

    this._sockets.add(targetSocket);

    const cleanup = () => {
      this._sockets.delete(clientSocket);
      this._sockets.delete(targetSocket);
      try { clientSocket.destroy(); } catch { /* ignore */ }
      try { targetSocket.destroy(); } catch { /* ignore */ }
    };

    clientSocket.on('error', cleanup);
    targetSocket.on('error', cleanup);
    clientSocket.on('close', cleanup);
    targetSocket.on('close', cleanup);

    // Client → Target (outbound: roll/move commands)
    clientSocket.on('data', (chunk) => {
      this._relay(chunk, targetSocket, 'c→t');
    });

    // Target → Client (inbound: turn_started, dice_rolled, etc.)
    targetSocket.on('data', (chunk) => {
      this._relay(chunk, clientSocket, 't→c');
    });
  }

  _relay(chunk, dest, direction) {
    const c = this.conditions;

    // Drop
    if (c.dropRate && Math.random() < c.dropRate) {
      if (this.verbose) console.log(`[NetworkProxy][${direction}] DROPPED ${chunk.length}B`);
      return;
    }

    // Pause
    if (c.paused) {
      if (this.verbose) console.log(`[NetworkProxy][${direction}] BLOCKED (paused) ${chunk.length}B`);
      return;
    }

    const delay = (c.latency || 0) + (c.jitter ? Math.random() * c.jitter : 0);

    const send = () => {
      if (!dest.destroyed) dest.write(chunk);
    };

    // Duplicate
    if (c.duplicateRate && Math.random() < c.duplicateRate) {
      if (this.verbose) console.log(`[NetworkProxy][${direction}] DUPLICATE ${chunk.length}B`);
      if (delay > 0) {
        setTimeout(() => { if (!dest.destroyed) dest.write(chunk); }, delay / 2);
      } else {
        if (!dest.destroyed) dest.write(chunk);
      }
    }

    if (delay > 0) {
      setTimeout(send, delay);
    } else {
      send();
    }
  }
}

// ── CLI entrypoint ─────────────────────────────────────────────────────────────

if (require.main === module) {
  const args = process.argv.slice(2);
  function getArg(flag, def) {
    const i = args.indexOf(flag);
    return i >= 0 ? args[i + 1] : def;
  }

  const proxyPort  = parseInt(getArg('--port',    String(DEFAULT_PROXY_PORT)),  10);
  const targetPort = parseInt(getArg('--target',  String(DEFAULT_TARGET_PORT)), 10);
  const latency    = parseInt(getArg('--latency', '0'),  10);
  const jitter     = parseInt(getArg('--jitter',  '0'),  10);
  const drop       = parseFloat(getArg('--drop',  '0'));
  const verbose    = args.includes('--verbose');

  const proxy = new NetworkProxy({ proxyPort, targetPort, verbose });
  if (latency || jitter || drop) proxy.setConditions({ latency, jitter, dropRate: drop });

  proxy.start().then(() => {
    console.log(`[NetworkProxy] Running. Ctrl+C to stop.`);
    if (latency) console.log(`  latency=${latency}ms jitter=${jitter}ms drop=${drop}`);
  });

  process.on('SIGINT', async () => {
    await proxy.stop();
    process.exit(0);
  });
}

module.exports = { NetworkProxy };
