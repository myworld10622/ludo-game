'use strict';
/**
 * ludoClusterService — Redis-backed multi-node coordination for Ludo rooms.
 *
 * Responsibilities:
 *   • Room ownership lease  — only the owner node arms timers and mutates state
 *   • Settlement lock       — distributed mutex preventing duplicate payouts
 *   • Command pub/sub       — non-owner nodes forward player actions to owner
 *   • State-update notify   — owner signals other nodes after every mutation
 *                             (they reload from Redis for reconnect serving)
 *
 * Disabled mode (LUDO_CLUSTER_ENABLED != 'true'):
 *   Every operation is a synchronous no-op; isOwner() always returns true.
 *   This keeps single-node deployments unchanged.
 *
 * Redis keys:
 *   ludo:owner:<roomId>         SETEX  nodeId  OWNER_TTL_S
 *   ludo:settle:lock:<roomId>   SET NX EX      SETTLE_TTL_S
 *
 * Pub/sub channels (single DB, same instance as persistence):
 *   ludo:cmd       — player action forwarding  { nodeId, roomId, cmd, payload }
 *   ludo:sync      — state-changed hint        { nodeId, roomId }
 */

const { randomUUID } = require('crypto');
const Redis          = require('ioredis');

// ── Configuration ─────────────────────────────────────────────────────────────
const ENABLED          = process.env.LUDO_CLUSTER_ENABLED === 'true';
const OWNER_TTL_S      = parseInt(process.env.LUDO_OWNER_TTL_S      || '60',  10);
// Heartbeat must fire well before the lease expires.  Default: refresh at 1/3 of TTL
// so we tolerate one missed heartbeat before the key expires.
const OWNER_REFRESH_MS = parseInt(process.env.LUDO_OWNER_REFRESH_MS || String(Math.floor(OWNER_TTL_S * 1000 / 3)), 10);
const SETTLE_TTL_S     = parseInt(process.env.LUDO_SETTLE_TTL_S     || '120', 10);
const REDIS_DB         = parseInt(process.env.REDIS_LUDO_DB || process.env.REDIS_DB || '2', 10);
const CMD_CHANNEL      = 'ludo:cmd';
const SYNC_CHANNEL     = 'ludo:sync';
const OWNER_PFX        = 'ludo:owner:';
const SETTLE_PFX       = 'ludo:settle:lock:';

// ── Node identity ─────────────────────────────────────────────────────────────
const NODE_ID = randomUUID();

// ── Shared Redis factory ───────────────────────────────────────────────────────
function makeRedis() {
  const client = new Redis({
    host:               process.env.REDIS_HOST     || '127.0.0.1',
    port:               parseInt(process.env.REDIS_PORT || '6379', 10),
    db:                 REDIS_DB,
    password:           process.env.REDIS_PASSWORD || undefined,
    lazyConnect:        false,
    enableOfflineQueue: true,
    retryStrategy:      times => Math.min(times * 200, 5000),
  });
  client.on('error', err => console.error('[LudoCluster] Redis error:', err.message));
  return client;
}

// ── Fencing epoch counter ─────────────────────────────────────────────────────
// Incremented every time this node claims ownership of a room.
// Callers embed the epoch in timer closures; on fire they compare against the
// current epoch to detect ownership changes that happened after arming.
const _ownerEpochs = new Map();   // roomId → epoch (integer, starts at 1)

function _nextEpoch(roomId) {
  const n = (_ownerEpochs.get(roomId) ?? 0) + 1;
  _ownerEpochs.set(roomId, n);
  return n;
}

// ── In-process state ──────────────────────────────────────────────────────────
const _ownedRooms  = new Set();   // rooms this node currently owns
const _cmdHandlers = [];          // callbacks: ({ roomId, cmd, payload, fromNode })
const _syncHandlers = [];         // callbacks: ({ roomId })
let   _cmdClient  = null;
let   _subClient  = null;
let   _heartbeatTimer = null;

// ── Lazy initialisation ───────────────────────────────────────────────────────
function ensureInit() {
  if (_cmdClient) return;
  _cmdClient = makeRedis();
  _subClient = makeRedis();

  _subClient.subscribe(CMD_CHANNEL, SYNC_CHANNEL, (err) => {
    if (err) console.error('[LudoCluster] subscribe error:', err.message);
  });

  _subClient.on('message', (channel, raw) => {
    let msg;
    try { msg = JSON.parse(raw); } catch { return; }
    if (msg.nodeId === NODE_ID) return;   // ignore own messages

    if (channel === CMD_CHANNEL) {
      _cmdHandlers.forEach(h => h({ roomId: msg.roomId, cmd: msg.cmd, payload: msg.payload, fromNode: msg.nodeId }));
    }
    if (channel === SYNC_CHANNEL) {
      _syncHandlers.forEach(h => h({ roomId: msg.roomId }));
    }
  });

  // Atomic lease-refresh Lua: only extend TTL when value still matches this node.
  // A plain GET → EXPIRE pair is non-atomic: another node could claim between the
  // two commands, and our EXPIRE would then extend the rival's lease.
  const REFRESH_SCRIPT = `
    if redis.call("get", KEYS[1]) == ARGV[1] then
      return redis.call("expire", KEYS[1], ARGV[2])
    else
      return 0
    end
  `;

  // Heartbeat: refresh ownership leases for all rooms this node owns
  _heartbeatTimer = setInterval(async () => {
    if (_ownedRooms.size === 0) return;
    const pipe = _cmdClient.pipeline();
    for (const roomId of [..._ownedRooms]) {
      pipe.eval(REFRESH_SCRIPT, 1, OWNER_PFX + roomId, NODE_ID, String(OWNER_TTL_S));
    }
    const results = await pipe.exec().catch(() => []);
    let i = 0;
    for (const roomId of [..._ownedRooms]) {
      const [err, refreshed] = results[i++] || [null, 0];
      if (err || refreshed === 0) {
        // Key missing or owned by another node — we have lost ownership
        _ownedRooms.delete(roomId);
        _ownerEpochs.delete(roomId);   // invalidate all in-flight timer epochs
        console.warn(`[LudoCluster] lost ownership of room ${roomId} — epoch invalidated`);
      }
    }
  }, OWNER_REFRESH_MS);

  console.log(`[LudoCluster] node ${NODE_ID} initialised (db=${REDIS_DB})`);
}

// ── Public API ─────────────────────────────────────────────────────────────────

/**
 * Attempt to claim ownership of a room.
 * Returns true if this node is now (or was already) the owner.
 * Also increments the local fencing epoch so any previously-armed timer
 * closures from a prior ownership period are invalidated.
 */
async function claimOwnership(roomId) {
  if (!ENABLED) return true;
  ensureInit();
  if (_ownedRooms.has(roomId)) return true;
  // SET NX EX: atomic claim
  const result = await _cmdClient.set(OWNER_PFX + roomId, NODE_ID, 'EX', OWNER_TTL_S, 'NX')
    .catch(() => null);
  if (result === 'OK') {
    _ownedRooms.add(roomId);
    _nextEpoch(roomId);
    return true;
  }
  // Someone else owns it (or Redis error) — do not claim
  return false;
}

/**
 * Force-claim ownership unconditionally (SET without NX).
 * Safe to call only when the previous owner is confirmed gone — i.e. the
 * ownership key has already expired and Redis confirms the room is unowned.
 * Prefer claimOwnership() in all other cases.
 */
async function forceClaimOwnership(roomId) {
  if (!ENABLED) return true;
  ensureInit();
  // First try the safe atomic NX path; only fall back to SET if the key is absent
  const nx = await _cmdClient.set(OWNER_PFX + roomId, NODE_ID, 'EX', OWNER_TTL_S, 'NX')
    .catch(() => null);
  if (nx === 'OK') {
    _ownedRooms.add(roomId);
    _nextEpoch(roomId);
    return true;
  }
  // Key already exists — check if it's our node (race: two nodes recovering same room)
  const current = await _cmdClient.get(OWNER_PFX + roomId).catch(() => null);
  if (current === NODE_ID) {
    _ownedRooms.add(roomId);
    if (!_ownerEpochs.has(roomId)) _nextEpoch(roomId);
    return true;
  }
  // Another live node owns this room — respect that, do not force-steal
  return false;
}

/**
 * Release ownership of a room (call on settlement or explicit shutdown).
 */
async function releaseOwnership(roomId) {
  if (!ENABLED) return;
  _ownedRooms.delete(roomId);
  _ownerEpochs.delete(roomId);   // invalidate in-flight timer epochs
  if (!_cmdClient) return;
  // Only delete if we're still the owner (avoid deleting a new owner's key)
  const script = `
    if redis.call("get", KEYS[1]) == ARGV[1] then
      return redis.call("del", KEYS[1])
    else
      return 0
    end
  `;
  await _cmdClient.eval(script, 1, OWNER_PFX + roomId, NODE_ID).catch(() => {});
}

/**
 * True if this node currently owns the room (synchronous, uses local cache).
 * When cluster is disabled, always returns true.
 */
function isOwner(roomId) {
  if (!ENABLED) return true;
  return _ownedRooms.has(roomId);
}

/**
 * Return the current fencing epoch for a room on this node.
 * Returns 0 when cluster is disabled (epoch checks always pass since no
 * competing nodes exist and timers never need to be invalidated).
 * Timer closures should capture this value and abort if it changes.
 */
function getOwnerEpoch(roomId) {
  if (!ENABLED) return 0;
  return _ownerEpochs.get(roomId) ?? 0;
}

/**
 * Acquire a one-shot settlement lock.
 * Returns 'won'    — this node holds the lock and should proceed with settlement.
 * Returns 'locked' — another node already holds the lock (skip settlement).
 * Returns 'error'  — Redis is unavailable; caller should retry or alert.
 *
 * Using an enum rather than a bool lets callers distinguish a genuine lock
 * contention (skip silently) from a transient Redis outage (surface the error).
 */
async function acquireSettleLock(roomId) {
  if (!ENABLED) return 'won';
  ensureInit();
  let result;
  try {
    result = await _cmdClient.set(SETTLE_PFX + roomId, NODE_ID, 'EX', SETTLE_TTL_S, 'NX');
  } catch (err) {
    console.error(`[LudoCluster] acquireSettleLock Redis error for room ${roomId}:`, err.message);
    return 'error';
  }
  return result === 'OK' ? 'won' : 'locked';
}

/**
 * Release the settlement lock (called after settlement finishes).
 */
async function releaseSettleLock(roomId) {
  if (!ENABLED) return;
  if (!_cmdClient) return;
  const script = `
    if redis.call("get", KEYS[1]) == ARGV[1] then
      return redis.call("del", KEYS[1])
    else
      return 0
    end
  `;
  await _cmdClient.eval(script, 1, SETTLE_PFX + roomId, NODE_ID).catch(() => {});
}

/**
 * Publish a player action command for the owner node to process.
 * Payload must be a plain JSON-serialisable object.
 */
async function publishCommand(roomId, cmd, payload) {
  if (!ENABLED) return;
  ensureInit();
  await _cmdClient.publish(CMD_CHANNEL, JSON.stringify({ nodeId: NODE_ID, roomId, cmd, payload }))
    .catch(() => {});
}

/**
 * Notify all nodes that a room's state has changed (they should reload from Redis).
 */
async function publishStateUpdate(roomId) {
  if (!ENABLED) return;
  ensureInit();
  await _cmdClient.publish(SYNC_CHANNEL, JSON.stringify({ nodeId: NODE_ID, roomId }))
    .catch(() => {});
}

/**
 * Register a handler for incoming commands from other nodes.
 * handler: ({ roomId, cmd, payload, fromNode }) => void
 * Returns an unsubscribe function.
 */
function onCommand(handler) {
  _cmdHandlers.push(handler);
  return () => {
    const idx = _cmdHandlers.indexOf(handler);
    if (idx >= 0) _cmdHandlers.splice(idx, 1);
  };
}

/**
 * Register a handler for state-update notifications from other nodes.
 * handler: ({ roomId }) => void
 * Returns an unsubscribe function.
 */
function onStateUpdate(handler) {
  _syncHandlers.push(handler);
  return () => {
    const idx = _syncHandlers.indexOf(handler);
    if (idx >= 0) _syncHandlers.splice(idx, 1);
  };
}

/**
 * Get the owner nodeId for a room from Redis (async).
 * Used to decide where to forward commands.
 */
async function getOwnerNodeId(roomId) {
  if (!ENABLED) return NODE_ID;
  ensureInit();
  return _cmdClient.get(OWNER_PFX + roomId).catch(() => null);
}

/**
 * Graceful shutdown — release all owned rooms and disconnect.
 */
async function shutdown() {
  if (_heartbeatTimer) { clearInterval(_heartbeatTimer); _heartbeatTimer = null; }
  if (!_cmdClient) return;
  // Release all owned rooms
  await Promise.all([..._ownedRooms].map(id => releaseOwnership(id)));
  await Promise.all([_cmdClient.quit(), _subClient.quit()].map(p => p.catch(() => {})));
  _cmdClient = null;
  _subClient = null;
}

/**
 * Create a pair of Redis clients suitable for the Socket.IO Redis adapter.
 * Caller must NOT subscribe on these clients — the adapter does that internally.
 */
function makeAdapterClients() {
  return { pubClient: makeRedis(), subClient: makeRedis() };
}

module.exports = {
  NODE_ID,
  isEnabled:          () => ENABLED,
  claimOwnership,
  forceClaimOwnership,
  releaseOwnership,
  isOwner,
  getOwnerEpoch,
  acquireSettleLock,
  releaseSettleLock,
  publishCommand,
  publishStateUpdate,
  onCommand,
  onStateUpdate,
  getOwnerNodeId,
  makeAdapterClients,
  shutdown,
};
