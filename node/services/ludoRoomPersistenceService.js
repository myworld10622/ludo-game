'use strict';
/**
 * ludoRoomPersistenceService — Redis-backed authoritative state for Ludo rooms.
 *
 * Each live room is stored as a Redis hash under key  ludo:room:<roomId>
 * with a TTL (default 4 hours, configurable via LUDO_ROOM_TTL_SECONDS).
 *
 * The hash has two fields:
 *   meta   — serialised room envelope (seats, config, matchUuid …)
 *   gs     — serialised game state (_gs): tokens, nonces, turn, timers
 *
 * Timer persistence:
 *   We cannot store a live setTimeout in Redis.  Instead we store the
 *   absolute wall-clock expiry (room._gsTimerExpiresAt) and rehydrate it
 *   as a remaining-ms value on recovery so the server can re-arm the timer.
 *
 * Crash recovery:
 *   Call loadAllRooms() once on server startup.  It returns every persisted
 *   room that has not yet expired.  The caller is responsible for re-arming
 *   timers and rejoining active socket rooms.
 *
 * Disabled mode:
 *   Set LUDO_REDIS_PERSIST=false in .env to disable all Redis writes (useful
 *   for local dev without Redis).  Reads will always return null.
 */

const Redis = require('ioredis');

const ENABLED = process.env.LUDO_REDIS_PERSIST !== 'false';
const TTL_S   = parseInt(process.env.LUDO_ROOM_TTL_SECONDS || String(4 * 3600), 10);
const KEY_PFX = 'ludo:room:';

// Singleton Redis client (lazy-init so the module can be required even when
// Redis is not available — the first operation will surface the error).
let _client = null;
function getClient() {
  if (!_client) {
    _client = new Redis({
      host:           process.env.REDIS_HOST     || '127.0.0.1',
      port:           parseInt(process.env.REDIS_PORT || '6379', 10),
      db:             parseInt(process.env.REDIS_LUDO_DB || process.env.REDIS_DB || '2', 10),
      password:       process.env.REDIS_PASSWORD || undefined,
      lazyConnect:    false,
      enableOfflineQueue: true,
      retryStrategy:  times => Math.min(times * 200, 5000),
    });
    _client.on('error', err => {
      console.error('[LudoRedis] connection error:', err.message);
    });
    _client.on('connect', () => {
      console.log(`[LudoRedis] connected (db=${_client.options.db})`);
    });
  }
  return _client;
}

// ── Serialisation helpers ────────────────────────────────────────────────────

/**
 * Convert a room's metadata envelope to a plain JSON-serialisable object.
 * Excludes runtime-only fields (_gs, _gsTimer, settlementPromise, startPromise).
 */
function serialiseMeta(room) {
  return {
    roomId:              room.roomId,
    gameSlug:            room.gameSlug      ?? 'ludo_v2',
    state:               room.state,
    roomType:            room.roomType,
    playMode:            room.playMode,
    maxPlayers:          room.maxPlayers,
    entryFee:            room.entryFee      ?? 0,
    allowBots:           room.allowBots     ?? false,
    queueKey:            room.queueKey      ?? null,
    fillBotsAt:          room.fillBotsAt    ?? null,
    matchUuid:           room.matchUuid     ?? null,
    tournamentUuid:      room.tournamentUuid      ?? null,
    tournamentMatchId:   room.tournamentMatchId   ?? null,
    mode:                room.mode          ?? null,
    startedAt:           room.startedAt     ? room.startedAt.toISOString() : null,
    startRetryCount:     room.startRetryCount ?? 0,
    realPlayers:         room.realPlayers   ?? 0,
    currentPlayers:      room.currentPlayers ?? 0,
    connectedEntryUuids: room.connectedEntryUuids ?? [],
    connectedUserIds:    room.connectedUserIds    ?? [],
    // Seats — must survive round-trip (includes bot metadata)
    seats:               room.seats         ?? [],
    // Chat ring-buffer
    chatHistory:         room.chatHistory   ?? [],
  };
}

/**
 * Convert room._gs to a plain JSON-serialisable object.
 * Sets are serialised as arrays; timer is stored as absolute expiry ms.
 */
function serialiseGs(room) {
  const gs = room._gs;
  if (!gs) return null;
  return {
    active:           gs.active,
    finished:         [...gs.finished],       // Set → Array
    current:          gs.current,
    diceValue:        gs.diceValue   ?? null,
    rolled:           gs.rolled,
    sixRun:           gs.sixRun,
    over:             gs.over,
    tokens:           gs.tokens,
    playerStarts:     gs.playerStarts,
    turnNonce:        gs.turnNonce   ?? null,
    rollNonce:        gs.rollNonce   ?? null,
    // Persist timer expiry as absolute ms so recovery knows how much is left
    timerExpiresAt:   room._gsTimerExpiresAt ?? null,
  };
}

function deserialiseMeta(raw) {
  const m = JSON.parse(raw);
  if (m.startedAt) m.startedAt = new Date(m.startedAt);
  return m;
}

function deserialiseGs(raw) {
  if (!raw) return null;
  const g = JSON.parse(raw);
  g.finished = new Set(g.finished);     // Array → Set
  return g;
}

// ── Public API ───────────────────────────────────────────────────────────────

/**
 * Persist the full room state.  Called after every authoritative mutation.
 * Fire-and-forget — errors are logged but never thrown to callers.
 */
async function saveRoom(room) {
  if (!ENABLED) return;
  try {
    const key  = KEY_PFX + room.roomId;
    const meta = JSON.stringify(serialiseMeta(room));
    const gs   = room._gs ? JSON.stringify(serialiseGs(room)) : '';
    const pipe = getClient().pipeline();
    pipe.hset(key, 'meta', meta, 'gs', gs);
    pipe.expire(key, TTL_S);
    await pipe.exec();
  } catch (err) {
    console.error('[LudoRedis] saveRoom error:', err.message);
  }
}

/**
 * Delete a room from Redis (called on settlement completion).
 */
async function deleteRoom(roomId) {
  if (!ENABLED) return;
  try {
    await getClient().del(KEY_PFX + roomId);
  } catch (err) {
    console.error('[LudoRedis] deleteRoom error:', err.message);
  }
}

/**
 * Load a single room by roomId.
 * Returns { meta, gs, timerRemainingMs } or null if not found.
 */
async function loadRoom(roomId) {
  if (!ENABLED) return null;
  try {
    const key  = KEY_PFX + roomId;
    const data = await getClient().hgetall(key);
    if (!data || !data.meta) return null;
    const meta = deserialiseMeta(data.meta);
    const gs   = data.gs ? deserialiseGs(data.gs) : null;
    let timerRemainingMs = null;
    if (gs && gs.timerExpiresAt) {
      timerRemainingMs = Math.max(0, gs.timerExpiresAt - Date.now());
      delete gs.timerExpiresAt;
    }
    return { meta, gs, timerRemainingMs };
  } catch (err) {
    console.error('[LudoRedis] loadRoom error:', err.message);
    return null;
  }
}

/**
 * Load all persisted rooms on server startup.
 * Returns an array of { meta, gs, timerRemainingMs } objects.
 */
async function loadAllRooms() {
  if (!ENABLED) return [];
  try {
    const keys = await getClient().keys(KEY_PFX + '*');
    if (!keys.length) return [];
    const results = await Promise.all(keys.map(k => {
      const roomId = k.slice(KEY_PFX.length);
      return loadRoom(roomId);
    }));
    return results.filter(Boolean);
  } catch (err) {
    console.error('[LudoRedis] loadAllRooms error:', err.message);
    return [];
  }
}

/**
 * Graceful shutdown — disconnect the Redis client.
 */
async function disconnect() {
  if (_client) {
    await _client.quit().catch(() => {});
    _client = null;
  }
}

module.exports = {
  saveRoom,
  deleteRoom,
  loadRoom,
  loadAllRooms,
  disconnect,
  isEnabled: () => ENABLED,
};
