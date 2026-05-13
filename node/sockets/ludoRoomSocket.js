const { v4: uuidv4 } = require("uuid");
const LudoRoomEngineService = require("../services/ludoRoomEngineService");
const LudoLaravelSyncService = require("../services/ludoLaravelSyncService");
const LudoRoomChatSyncService = require("../services/ludoRoomChatSyncService");
const tournamentLudoLaravelSyncService = require("../services/tournamentLudoLaravelSyncService");
const tournamentLudoRoomService = require("../services/tournamentLudoRoomService");
const tournamentMatchResultService = require("../services/tournamentMatchResultService");
const { roomStates, playerTypes, socketEvents } = require("../constants/ludoRoom");
const persistence = require("../services/ludoRoomPersistenceService");
const cluster     = require("../services/ludoClusterService");

module.exports = function (namespace) {
  const engine = new LudoRoomEngineService();
  const laravelSync = new LudoLaravelSyncService();
  const chatSync = new LudoRoomChatSyncService();
  const rooms = new Map();
  const roomTimers = new Map();
  const roomStartRetryTimers = new Map();

  // ── Redis persistence + cluster sync helper ──────────────────────────────
  // Fire-and-forget: never await inside hot synchronous paths.
  function _persist(room) {
    if (!room) return;
    persistence.saveRoom(room).catch(() => {});
    // Notify peer nodes that state changed so they can reload for reconnect serving
    cluster.publishStateUpdate(room.roomId).catch(() => {});
  }
  const CHAT_MESSAGE_COOLDOWN_MS = Math.max(300, Number(process.env.LUDO_CHAT_MESSAGE_COOLDOWN_MS || "800"));
  const CHAT_MESSAGE_MAX_LENGTH = Math.max(20, Number(process.env.LUDO_CHAT_MESSAGE_MAX_LENGTH || "200"));
  const EMOJI_COOLDOWN_MS = Math.max(500, Number(process.env.LUDO_EMOJI_COOLDOWN_MS || "2000"));
  const MAX_EMOJI_ID = Math.max(0, Number(process.env.LUDO_EMOJI_MAX_ID || "31"));
  const START_SYNC_CONCURRENCY = Math.max(1, Number(process.env.LUDO_MATCH_START_SYNC_CONCURRENCY || "8"));
  const SETTLEMENT_SYNC_CONCURRENCY = Math.max(1, Number(process.env.LUDO_MATCH_SETTLEMENT_SYNC_CONCURRENCY || "8"));
  const START_RETRY_DELAY_MS = Math.max(500, Number(process.env.LUDO_MATCH_START_RETRY_DELAY_MS || "3000"));
  const START_RETRY_LIMIT = Math.max(0, Number(process.env.LUDO_MATCH_START_RETRY_LIMIT || "6"));

  function createLimiter(limit) {
    let activeCount = 0;
    const queue = [];

    function next() {
      if (activeCount >= limit || queue.length === 0) {
        return;
      }

      const task = queue.shift();
      if (!task) {
        return;
      }

      activeCount += 1;
      Promise.resolve()
        .then(task.fn)
        .then(task.resolve, task.reject)
        .finally(() => {
          activeCount = Math.max(0, activeCount - 1);
          next();
        });
    }

    return function runLimited(fn) {
      return new Promise((resolve, reject) => {
        queue.push({ fn, resolve, reject });
        next();
      });
    };
  }

  const runStartSync = createLimiter(START_SYNC_CONCURRENCY);
  const runSettlementSync = createLimiter(SETTLEMENT_SYNC_CONCURRENCY);

  function normalizePayload(payload) {
    if (!payload) {
      return {};
    }

    if (typeof payload === "string") {
      try {
        return JSON.parse(payload);
      } catch (error) {
        return {};
      }
    }

    return payload;
  }

  function serializeRoom(room) {
    return {
      room_id: room.roomId,
      game_slug: room.gameSlug,
      room_type: room.roomType,
      play_mode: room.playMode,
      state: room.state,
      max_players: room.maxPlayers,
      current_players: room.currentPlayers,
      real_players: room.realPlayers,
      bot_players: room.botPlayers,
      allow_bots: room.allowBots,
      min_real_players: room.minRealPlayers,
      bot_fill_after_seconds: room.botFillAfterSeconds,
      bot_start_policy: room.botStartPolicy ?? "disabled",
      fill_bots_at: room.fillBotsAt,
      entry_fee: room.entryFee,
      match_uuid: room.matchUuid ?? null,
      tournament_uuid: room.tournamentUuid ?? null,
      tournament_entry_uuid: room.tournamentEntryUuid ?? null,
      mode: room.mode ?? "public",
      seats: room.seats,
      players: room.players ?? [],
    };
  }

  function mergeTournamentRoomState(existingRoom, incomingRoom, claimedUserId, claimedEntryUuid) {
    if (!existingRoom) {
      return incomingRoom;
    }

    const existingSeats = Array.isArray(existingRoom.seats) ? existingRoom.seats : [];
    const incomingSeats = Array.isArray(incomingRoom.seats) ? incomingRoom.seats : [];
    const mergedSeats = [];
    const incomingSeatNos = new Set(incomingSeats.map((seat) => Number(seat.seatNo)));
    const connectedEntryUuids = new Set([
      ...((existingRoom.connectedEntryUuids ?? []).map((value) => String(value))),
      ...((incomingRoom.connectedEntryUuids ?? []).map((value) => String(value))),
      ...existingSeats
        .filter((seat) => seat?.isConnected === true)
        .map((seat) =>
          String(
            seat?.tournamentEntryUuid ??
              seat?.meta?.tournament_entry_uuid ??
              seat?.meta?.tournamentEntryUuid ??
              ""
          )
        )
        .filter(Boolean),
    ]);
    const connectedUserIds = new Set([
      ...((existingRoom.connectedUserIds ?? []).map((value) => String(value))),
      ...((incomingRoom.connectedUserIds ?? []).map((value) => String(value))),
      ...existingSeats
        .filter((seat) => seat?.isConnected === true && seat?.userId !== null && seat?.userId !== undefined)
        .map((seat) => String(seat.userId)),
    ]);

    if (claimedEntryUuid !== null && claimedEntryUuid !== undefined) {
      connectedEntryUuids.add(String(claimedEntryUuid));
    }
    if (claimedUserId !== null && claimedUserId !== undefined) {
      connectedUserIds.add(String(claimedUserId));
    }

    for (const seat of incomingSeats) {
      const seatNo = Number(seat.seatNo);
      const priorSeat = existingSeats.find((item) => Number(item.seatNo) === seatNo);
      const seatEntryUuid = String(
        seat?.tournamentEntryUuid ??
          seat?.meta?.tournament_entry_uuid ??
          seat?.meta?.tournamentEntryUuid ??
          ""
      );
      const seatUserId =
        seat?.userId !== null && seat?.userId !== undefined
          ? String(seat.userId)
          : "";
      const isClaimedSeat =
        seatEntryUuid === String(claimedEntryUuid ?? "") ||
        seatUserId === String(claimedUserId ?? "");
      const isKnownConnected =
        (seatEntryUuid && connectedEntryUuids.has(seatEntryUuid)) ||
        (seatUserId && connectedUserIds.has(seatUserId));

      mergedSeats.push({
        ...priorSeat,
        ...seat,
        isConnected: isClaimedSeat || isKnownConnected
          ? true
          : (priorSeat?.isConnected ?? seat?.isConnected ?? false),
        isReady: isClaimedSeat || isKnownConnected
          ? true
          : (priorSeat?.isReady ?? seat?.isReady ?? false),
      });
    }

    for (const seat of existingSeats) {
      const seatNo = Number(seat.seatNo);
      if (incomingSeatNos.has(seatNo)) {
        continue;
      }

      if (seat.playerType === playerTypes.BOT) {
        mergedSeats.push(seat);
      }
    }

    const realPlayers = mergedSeats.filter((seat) => seat.playerType === playerTypes.HUMAN).length;
    const botPlayers = mergedSeats.filter((seat) => seat.playerType === playerTypes.BOT).length;

    return {
      ...existingRoom,
      ...incomingRoom,
      seats: mergedSeats.sort((a, b) => Number(a.seatNo) - Number(b.seatNo)),
      currentPlayers: mergedSeats.length,
      realPlayers,
      botPlayers,
      connectedEntryUuids: Array.from(connectedEntryUuids),
      connectedUserIds: Array.from(connectedUserIds),
      fillBotsAt: existingRoom.fillBotsAt ?? incomingRoom.fillBotsAt ?? null,
      startedAt: existingRoom.startedAt ?? incomingRoom.startedAt ?? null,
    };
  }

  function toSeatPayload(room) {
    return (room.seats ?? []).map((seat) => ({
      seat_no: seat.seatNo,
      user_id: seat.userId ?? null,
      player_type: seat.playerType,
      display_name: seat.displayName,
      bot_code: seat.botCode ?? null,
    }));
  }

  function queueKey(payload) {
    return [
      "ludo",
      payload.roomType ?? "public",
      payload.playMode ?? "cash",
      payload.gameMode ?? "classic",
      payload.maxPlayers ?? 4,
      payload.entryFee ?? 0,
    ].join(":");
  }

  function emitSnapshot(room) {
    namespace.to(room.roomId).emit(socketEvents.server.SNAPSHOT, serializeRoom(room));
  }

  function syncSocketSeatContext(socket, room) {
    if (!socket || !room) {
      return null;
    }

    const tournamentEntryUuid = socket.data.tournamentEntryUuid ?? null;
    const userId = socket.data.userId ?? null;
    const seat = (room.seats ?? []).find((candidate) => {
      if (!candidate) {
        return false;
      }

      if (tournamentEntryUuid) {
        const seatTournamentEntryUuid =
          candidate?.tournamentEntryUuid ??
          candidate?.meta?.tournament_entry_uuid ??
          candidate?.meta?.tournamentEntryUuid ??
          null;

        return String(seatTournamentEntryUuid ?? "") === String(tournamentEntryUuid);
      }

      return String(candidate.userId ?? "") === String(userId ?? "");
    }) ?? null;

    socket.data.seatNo = seat?.seatNo ?? null;
    socket.data.playerType = seat?.playerType ?? null;
    socket.data.displayName = seat?.displayName ?? null;
    return seat;
  }

  function clearRoomTimer(roomId) {
    const timerId = roomTimers.get(roomId);
    if (timerId) {
      clearTimeout(timerId);
      roomTimers.delete(roomId);
    }
  }

  function clearRoomStartRetryTimer(roomId) {
    const timerId = roomStartRetryTimers.get(roomId);
    if (timerId) {
      clearTimeout(timerId);
      roomStartRetryTimers.delete(roomId);
    }
  }

  function handleChatEmoji(socket, payload = {}) {
    const roomId = payload.roomId ?? payload.room_id ?? socket.data.roomId;
    if (!roomId || !rooms.has(roomId)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Room not found.",
      });
      return;
    }

    const room = rooms.get(roomId);
    if (!room || [roomStates.COMPLETED, roomStates.CANCELLED, roomStates.ABANDONED].includes(room.state)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Emoji is not available for this room state.",
      });
      return;
    }

    if (!socket.data.roomId || String(socket.data.roomId) !== String(room.roomId)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "You are not connected to this room.",
      });
      return;
    }

    const seat = syncSocketSeatContext(socket, room);
    if (!seat || seat.playerType !== playerTypes.HUMAN) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Only connected room players can send emoji.",
      });
      return;
    }

    const emojiIdRaw = payload.emojiId ?? payload.emoji_id ?? payload.emoji;
    const emojiId = Number(emojiIdRaw);
    if (!Number.isInteger(emojiId) || emojiId < 0 || emojiId > MAX_EMOJI_ID) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Invalid emoji selection.",
      });
      return;
    }

    const now = Date.now();
    if (socket.data.lastEmojiAt && now - Number(socket.data.lastEmojiAt) < EMOJI_COOLDOWN_MS) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Please wait before sending another emoji.",
      });
      return;
    }

    socket.data.lastEmojiAt = now;

    namespace.to(room.roomId).emit(socketEvents.server.CHAT_EMOJI, {
      room_id: room.roomId,
      emoji_id: emojiId,
      sender: {
        user_id: seat.userId ?? null,
        seat_no: seat.seatNo,
        display_name: seat.displayName ?? `Player ${seat.seatNo}`,
        player_type: seat.playerType,
      },
      created_at: new Date(now).toISOString(),
    });
  }

  function sanitizeChatMessage(input) {
    return String(input ?? "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function roomSupportsChat(room) {
    return Boolean(
      room &&
      ![roomStates.COMPLETED, roomStates.CANCELLED, roomStates.ABANDONED].includes(room.state)
    );
  }

  function ensureRoomChatBuffer(room) {
    if (!Array.isArray(room.chatHistory)) {
      room.chatHistory = [];
    }

    return room.chatHistory;
  }

  async function loadRoomChatHistory(socket, room, limit = 50) {
    if (!socket || !room) {
      return;
    }

    try {
      let messages;
      if (chatSync.isEnabled()) {
        messages = await chatSync.fetchRoomMessages(room.roomId, limit);
        room.chatHistory = Array.isArray(messages) ? messages.slice(-100) : [];
      } else {
        messages = ensureRoomChatBuffer(room).slice(-Math.max(1, Math.min(100, Number(limit) || 50)));
      }

      socket.emit(socketEvents.server.CHAT_HISTORY, {
        room_id: room.roomId,
        messages: Array.isArray(messages) ? messages : [],
      });
    } catch (error) {
      console.error('[Chat] Unable to fetch room chat history:', error.message);
      // Send empty history instead of error — chat failure should not disrupt the game
      socket.emit(socketEvents.server.CHAT_HISTORY, {
        room_id: room.roomId,
        messages: [],
      });
    }
  }

  function buildEphemeralChatMessage(room, seat, message, clientMessageId = null) {
    return {
      message_id: `local-${Date.now()}-${seat.seatNo}`,
      room_id: room.roomId,
      match_uuid: room.matchUuid ?? null,
      message_type: "text",
      sender_type: seat.playerType ?? playerTypes.HUMAN,
      message,
      sender: {
        user_id: seat.userId ?? null,
        seat_no: seat.seatNo,
        display_name: seat.displayName ?? `Player ${seat.seatNo}`,
        player_id: null,
        avatar: null,
        bot_code: seat.botCode ?? null,
      },
      meta: clientMessageId ? { client_message_id: clientMessageId } : {},
      created_at: new Date().toISOString(),
    };
  }

  async function handleChatSend(socket, payload = {}) {
    const roomId = payload.roomId ?? payload.room_id ?? socket.data.roomId;
    if (!roomId || !rooms.has(roomId)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Room not found.",
      });
      return;
    }

    const room = rooms.get(roomId);
    if (!roomSupportsChat(room)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Chat is not available for this room state.",
      });
      return;
    }

    if (!socket.data.roomId || String(socket.data.roomId) !== String(room.roomId)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "You are not connected to this room.",
      });
      return;
    }

    const seat = syncSocketSeatContext(socket, room);
    if (!seat || seat.playerType !== playerTypes.HUMAN) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Only connected room players can send chat.",
      });
      return;
    }

    const message = sanitizeChatMessage(payload.message);
    if (!message) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Message cannot be empty.",
      });
      return;
    }

    if (message.length > CHAT_MESSAGE_MAX_LENGTH) {
      socket.emit(socketEvents.server.ERROR, {
        message: `Message cannot exceed ${CHAT_MESSAGE_MAX_LENGTH} characters.`,
      });
      return;
    }

    const now = Date.now();
    if (socket.data.lastChatMessageAt && now - Number(socket.data.lastChatMessageAt) < CHAT_MESSAGE_COOLDOWN_MS) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Please wait before sending another message.",
      });
      return;
    }

    socket.data.lastChatMessageAt = now;

    try {
      let outgoingMessage;
      if (chatSync.isEnabled()) {
        outgoingMessage = await chatSync.createRoomMessage(room.roomId, {
          user_id: seat.userId ?? null,
          seat_no: seat.seatNo,
          sender_type: "human",
          message_type: "text",
          message,
          display_name: seat.displayName ?? `Player ${seat.seatNo}`,
          meta: {
            client_message_id: payload.client_message_id ?? payload.clientMessageId ?? null,
          },
        });

        if (outgoingMessage) {
          ensureRoomChatBuffer(room).push(outgoingMessage);
          room.chatHistory = room.chatHistory.slice(-100);
        }
      } else {
        outgoingMessage = buildEphemeralChatMessage(
          room,
          seat,
          message,
          payload.client_message_id ?? payload.clientMessageId ?? null
        );
        ensureRoomChatBuffer(room).push(outgoingMessage);
        room.chatHistory = room.chatHistory.slice(-100);
      }

      namespace.to(room.roomId).emit(socketEvents.server.CHAT_MESSAGE, outgoingMessage);
    } catch (error) {
      console.error(error.message);
      socket.emit(socketEvents.server.ERROR, {
        message: "Unable to send room chat message.",
      });
    }
  }

  function areAllHumanSeatsConnected(room) {
    const seats = room.seats ?? [];
    const humanSeats = seats.filter((seat) => seat.playerType === playerTypes.HUMAN);

    if (humanSeats.length === 0) {
      return false;
    }

    return humanSeats.every((seat) => seat.isConnected === true);
  }

  function isTournamentRoomReadyToStart(room) {
    return (
      room.currentPlayers >= room.maxPlayers &&
      areAllHumanSeatsConnected(room)
    );
  }

  function hasDisconnectedHumanSeats(room) {
    const seats = room?.seats ?? [];
    return seats.some(
      (seat) => seat.playerType === playerTypes.HUMAN && seat.isConnected !== true
    );
  }

  function replaceDisconnectedHumanSeatsWithBots(room) {
    const replacedSeats = [];

    room.seats = (room.seats ?? []).map((seat) => {
      if (seat.playerType !== playerTypes.HUMAN || seat.isConnected === true) {
        return seat;
      }

      const replacement = {
        ...seat,
        userId: null,
        playerType: playerTypes.BOT,
        botCode: seat.botCode ?? `tournament-bot-${seat.seatNo}`,
        displayName: seat.displayName || `Player ${seat.seatNo}`,
        isConnected: true,
        isReady: true,
      };

      replacedSeats.push(replacement);
      return replacement;
    });

    if (replacedSeats.length > 0) {
      room.realPlayers = Math.max(0, room.realPlayers - replacedSeats.length);
      room.botPlayers += replacedSeats.length;
    }

    return replacedSeats;
  }

  function tournamentBotPolicy(room) {
    return room?.botStartPolicy ?? "disabled";
  }

  function supportsTournamentBotFill(room) {
    return ["fill_missing", "hybrid"].includes(tournamentBotPolicy(room));
  }

  function supportsTournamentOfflineReplacement(room) {
    return ["replace_offline", "hybrid"].includes(tournamentBotPolicy(room));
  }

  function scheduleStartRetry(room, startedWithBots) {
    if (!room || room.startRetryCount >= START_RETRY_LIMIT) {
      return;
    }

    clearRoomStartRetryTimer(room.roomId);
    room.startRetryCount = (room.startRetryCount || 0) + 1;
    room.state = roomStates.WAITING;
    emitSnapshot(room);

    const timerId = setTimeout(() => {
      roomStartRetryTimers.delete(room.roomId);
      const currentRoom = rooms.get(room.roomId);
      if (!currentRoom || currentRoom.state === roomStates.COMPLETED || currentRoom.state === roomStates.CANCELLED) {
        return;
      }

      if (
        currentRoom.currentPlayers >= currentRoom.maxPlayers &&
        !hasDisconnectedHumanSeats(currentRoom)
      ) {
        startRoom(currentRoom, startedWithBots);
      }
    }, START_RETRY_DELAY_MS * room.startRetryCount);

    roomStartRetryTimers.set(room.roomId, timerId);
  }

  // ── Server-driven game engine ─────────────────────────────────────────────
  //
  // The server is the sole authority over:
  //   • token positions          • legal move validation
  //   • kill (capture) detection • extra-turn computation
  //   • win detection            • automatic settlement
  //
  // Board model — player-relative position per token:
  //   -1        in home yard (not yet in play)
  //    0        on player's entry square (shared ring)
  //    1–50     on shared ring
  //   51–56     in player's safe home column (cannot be killed)
  //   57        token has reached home (this token is DONE)
  //
  // A player wins when all TOKENS_PER_PLAYER tokens reach 57.
  //
  // Absolute ring position (kill detection):
  //   abs = (PLAYER_STARTS[seat] + relPos) % BOARD_RING_SIZE   (relPos 0–50 only)
  //
  // Safe squares (absolute): 0 8 13 21 26 34 39 47
  // Tokens on safe squares cannot be killed.

  const ROLL_TIMEOUT_MS         = parseInt(process.env.ROLL_TIMEOUT_MS    || '17000', 10);
  const MOVE_TIMEOUT_MS         = parseInt(process.env.MOVE_TIMEOUT_MS    || '17000', 10);
  const BOT_ROLL_DELAY          = parseInt(process.env.BOT_ROLL_DELAY     || '1500',  10);
  const BOT_MOVE_DELAY          = parseInt(process.env.BOT_MOVE_DELAY     || '2000',  10);
  const NO_MOVES_AUTO_PASS_MS   = parseInt(process.env.NO_MOVES_AUTO_PASS_MS || '1500', 10);
  const BOARD_RING_SIZE         = 52;
  const TOKENS_PER_PLAYER       = 4;
  const TOKEN_HOME_POS          = 56;   // inclusive: token at 56 = home (matches Unity way_point[56])
  const HOME_COL_START          = 51;   // first safe home-column position (relative)

  // ── Anti-cheat constants ──────────────────────────────────────────────────
  const AC_RATE_LIMIT_MS       = parseInt(process.env.AC_RATE_LIMIT_MS || '250', 10);   // minimum ms between same-event emits per socket
  const AC_MAX_VIOLATIONS      = 8;     // cumulative violations before socket is kicked
  const AC_VIOLATION_DECAY_MS  = 60_000; // violations older than this are not counted

  // ── Anti-cheat helpers ────────────────────────────────────────────────────

  /** Cryptographically random 16-byte hex token used for nonce chains. */
  function _acNonce() {
    return require('crypto').randomBytes(16).toString('hex');
  }

  /**
   * Structured audit log for all anti-cheat events.
   * level: 'warn' | 'block' | 'kick'
   */
  function _acLog(level, socket, reason, extra = {}) {
    const uid  = socket.data?.userId  ?? '?';
    const room = socket.data?.roomId  ?? '?';
    const addr = socket.handshake?.address ?? '?';
    console[level === 'kick' ? 'error' : 'warn'](
      `[AntiCheat][${level.toUpperCase()}] uid=${uid} room=${room} addr=${addr} reason="${reason}"`,
      Object.keys(extra).length ? extra : ''
    );
  }

  /**
   * Record a violation against a socket.
   * Returns true if the socket should be kicked (threshold reached).
   */
  function _acViolation(socket, reason, extra = {}) {
    _acLog('block', socket, reason, extra);
    if (!socket.data._acViolations) socket.data._acViolations = [];
    const now = Date.now();
    // Prune old violations outside the decay window
    socket.data._acViolations = socket.data._acViolations.filter(
      t => now - t < AC_VIOLATION_DECAY_MS
    );
    socket.data._acViolations.push(now);
    if (socket.data._acViolations.length >= AC_MAX_VIOLATIONS) {
      _acLog('kick', socket, `${AC_MAX_VIOLATIONS} violations in ${AC_VIOLATION_DECAY_MS / 1000}s`);
      socket.emit(socketEvents.server.ERROR, { message: 'Suspicious activity detected.' });
      socket.disconnect(true);
      return true;
    }
    return false;
  }

  /**
   * Per-socket per-event rate limiter.
   * Returns true (allowed) or false (too fast — violation recorded).
   */
  function _acCheckRate(socket, eventName) {
    if (!socket.data._acLastEvent) socket.data._acLastEvent = {};
    const now  = Date.now();
    const last = socket.data._acLastEvent[eventName] ?? 0;
    if (now - last < AC_RATE_LIMIT_MS) {
      _acViolation(socket, `rate_limit:${eventName}`, { gapMs: now - last });
      return false;
    }
    socket.data._acLastEvent[eventName] = now;
    return true;
  }

  /**
   * Verify that the userId in a payload matches the socket's bound identity.
   * Returns true if valid, false (+ violation) if spoofed.
   */
  function _acValidateIdentity(socket, payloadUserId) {
    const bound   = socket.data?.userId != null ? String(socket.data.userId) : null;
    const claimed = payloadUserId      != null ? String(payloadUserId)      : null;
    if (!bound || !claimed) return true; // no identity bound yet — permit (join flow)
    if (bound !== claimed) {
      _acViolation(socket, 'identity_spoof', { bound, claimed });
      return false;
    }
    return true;
  }

  /**
   * Verify that the room_id in a payload matches the socket's joined room.
   * Returns true if valid.
   */
  function _acValidateRoom(socket, payloadRoomId) {
    const bound   = socket.data?.roomId;
    if (!bound || !payloadRoomId) return true;  // no room bound yet
    if (String(payloadRoomId) !== String(bound)) {
      _acViolation(socket, 'room_spoof', { bound, claimed: payloadRoomId });
      return false;
    }
    return true;
  }

  /**
   * Validate the turn nonce in a ROLL_DICE payload.
   * Prevents replay of roll events from previous turns.
   */
  function _acValidateTurnNonce(socket, gs, payloadNonce) {
    if (!gs.turnNonce) return true;  // nonce not yet required (upgrade path)
    if (!payloadNonce || payloadNonce !== gs.turnNonce) {
      _acViolation(socket, 'bad_turn_nonce', {
        expected: gs.turnNonce, received: payloadNonce ?? '(none)',
      });
      return false;
    }
    return true;
  }

  /**
   * Validate the roll nonce in a MOVE_TOKEN payload.
   * Prevents replay of move events from previous dice rolls.
   */
  function _acValidateRollNonce(socket, gs, payloadNonce) {
    if (!gs.rollNonce) return true;
    if (!payloadNonce || payloadNonce !== gs.rollNonce) {
      _acViolation(socket, 'bad_roll_nonce', {
        expected: gs.rollNonce, received: payloadNonce ?? '(none)',
      });
      return false;
    }
    return true;
  }

  const SAFE_SQUARES_ABS = new Set([0, 8, 13, 21, 26, 34, 39, 47]);

  // Player entry points on the shared ring for each seat index (0-based)
  function _getPlayerStarts(maxPlayers) {
    if (maxPlayers <= 2) return [0, 26];          // diagonal opponents
    if (maxPlayers === 3) return [0, 13, 26];
    return [0, 13, 26, 39];
  }

  // Convert player-relative position to absolute ring square (valid for relPos 0–50)
  function _absPos(relPos, seatIndex, playerStarts) {
    if (relPos < 0 || relPos > 50) return -1;
    return (playerStarts[seatIndex] + relPos) % BOARD_RING_SIZE;
  }

  // Can this token move with this dice value?
  function _canMoveToken(pos, diceValue) {
    if (pos >= TOKEN_HOME_POS)  return false;          // already home
    if (pos === -1)             return diceValue === 6; // need 6 to enter
    return pos + diceValue <= TOKEN_HOME_POS;           // cannot overshoot
  }

  // Return indices of tokens that can legally move
  function _legalMoves(gs, seatIndex, diceValue) {
    return gs.tokens[seatIndex].reduce((acc, pos, ti) => {
      if (_canMoveToken(pos, diceValue)) acc.push(ti);
      return acc;
    }, []);
  }

  // Apply a token move; returns { killed[], extraTurn, isWin }
  // Mutates gs.tokens in place.
  function _applyTokenMove(gs, seatIndex, tokenIndex, diceValue, playerStarts) {
    const tokens = gs.tokens[seatIndex];
    const oldPos = tokens[tokenIndex];

    // Compute new position.  -1 (yard) treated as -1 so entry = -1 + 6 = 5
    // which matches Unity's way_point[5] after 6 movement steps from yard.
    const newPos = Math.min(oldPos + diceValue, TOKEN_HOME_POS);
    tokens[tokenIndex] = newPos;

    // Kill detection — only on shared ring, non-safe squares
    const killed = [];
    if (newPos >= 0 && newPos <= 50) {
      const myAbs = _absPos(newPos, seatIndex, playerStarts);
      if (myAbs >= 0 && !SAFE_SQUARES_ABS.has(myAbs)) {
        for (let si = 0; si < gs.tokens.length; si++) {
          if (si === seatIndex) continue;
          for (let ti = 0; ti < TOKENS_PER_PLAYER; ti++) {
            const op = gs.tokens[si][ti];
            if (op < 0 || op > 50) continue;           // not on shared ring
            if (_absPos(op, si, playerStarts) === myAbs) {
              gs.tokens[si][ti] = -1;                   // send to home yard
              killed.push({ seat_index: si, token_index: ti });
            }
          }
        }
      }
    }

    // Extra-turn: rolling 6 or killing at least one opponent token
    const rolledSix  = diceValue === 6;
    let   extraTurn  = rolledSix || killed.length > 0;

    if (rolledSix) {
      gs.sixRun = (gs.sixRun || 0) + 1;
      if (gs.sixRun >= 3) {
        // Three consecutive sixes: forfeit — token that just moved goes back
        gs.tokens[seatIndex][tokenIndex] = -1;
        gs.sixRun   = 0;
        extraTurn   = false;
      }
    } else {
      gs.sixRun = 0;
    }

    // Win: all four tokens home
    const isWin = gs.tokens[seatIndex].every(p => p >= TOKEN_HOME_POS);

    return { killed, extraTurn, isWin };
  }

  function _allHumanRoom(room) {
    return (room.seats ?? []).length > 0 &&
      (room.seats ?? []).every(s => s && s.playerType === 'human');
  }

  function _gameState(room) {
    return room._gs ?? null;
  }

  function _seatForSocket(room, socket, payloadUserId) {
    const uid = payloadUserId != null
      ? String(payloadUserId)
      : (socket.data?.userId != null ? String(socket.data.userId) : null);
    if (!uid) return null;
    return (room.seats ?? []).find(s => s && String(s.userId ?? '') === uid) ?? null;
  }

  function _clearGameTimer(room) {
    if (room._gsTimer) { clearTimeout(room._gsTimer); room._gsTimer = null; }
    room._gsTimerExpiresAt = null;
  }

  function _setGameTimer(room, fn, delayMs) {
    _clearGameTimer(room);
    room._gsTimerExpiresAt = Date.now() + delayMs;
    // Only the owner node arms real timers; non-owners track the expiry for
    // reconnect snapshots but do not fire callbacks.
    if (!cluster.isOwner(room.roomId)) return;
    // Embed fencing epoch: if ownership changes between arming and firing
    // (e.g. GC pause, Redis blip) the stale callback is silently dropped.
    const epoch = cluster.getOwnerEpoch(room.roomId);
    room._gsTimer = setTimeout(() => {
      if (cluster.getOwnerEpoch(room.roomId) !== epoch) {
        console.warn(`[LudoCluster] stale timer dropped (epoch mismatch) roomId=${room.roomId}`);
        return;
      }
      if (!cluster.isOwner(room.roomId)) {
        console.warn(`[LudoCluster] stale timer dropped (not owner) roomId=${room.roomId}`);
        return;
      }
      fn();
    }, delayMs);
  }

  function _advanceTurn(room) {
    const gs = _gameState(room);
    if (!gs) return;
    const active = gs.active.filter(i => !gs.finished.has(i));
    if (active.length === 0) { gs.over = true; return; }
    const pos   = active.indexOf(gs.current);
    gs.current  = active[(pos + 1) % active.length];
    gs.diceValue = null;
    gs.rolled    = false;
    gs.sixRun    = 0;
  }

  function _startTurn(room) {
    const gs = _gameState(room);
    if (!gs || gs.over) return;
    _clearGameTimer(room);
    const seat = room.seats[gs.current];
    if (!seat) return;

    // Rotate nonces: old roll nonce expires, fresh turn nonce issued
    gs.turnNonce = _acNonce();
    gs.rollNonce = null;

    namespace.to(room.roomId).emit(socketEvents.server.TURN_STARTED, {
      seat_index: gs.current,
      is_bot:     seat.playerType === 'bot',
      turn_nonce: gs.turnNonce,    // client must echo this in ROLL_DICE
    });

    if (seat.playerType === 'bot') {
      _setGameTimer(room, () => _botRoll(room, gs.current), BOT_ROLL_DELAY);
    } else {
      _setGameTimer(room, () => _missRoll(room), ROLL_TIMEOUT_MS);
    }
    _persist(room);
  }

  function _missRoll(room) {
    const gs = _gameState(room);
    if (!gs || gs.over) return;
    namespace.to(room.roomId).emit(socketEvents.server.TURN_MISSED, {
      seat_index: gs.current,
      reason:     'roll_timeout',
    });
    gs.diceValue = null;
    gs.rolled    = false;
    _advanceTurn(room);
    _startTurn(room);
  }

  function _missMove(room) {
    const gs = _gameState(room);
    if (!gs || gs.over || gs.diceValue == null) return;
    namespace.to(room.roomId).emit(socketEvents.server.TURN_MISSED, {
      seat_index: gs.current,
      reason:     'move_timeout',
    });
    gs.diceValue = null;
    gs.rolled    = false;
    _advanceTurn(room);
    _startTurn(room);
  }

  function _rollDiceValue() {
    const crypto = require('crypto');
    return (crypto.randomBytes(1)[0] % 6) + 1;
  }

  function _doRoll(room, seatIndex) {
    const gs = _gameState(room);
    if (!gs || gs.over) return;
    _clearGameTimer(room);

    const dv           = _rollDiceValue();
    gs.diceValue       = dv;
    gs.rolled          = true;

    // Issue a fresh roll nonce; client must echo it in MOVE_TOKEN
    gs.rollNonce  = _acNonce();
    gs.turnNonce  = null;   // turn nonce consumed — cannot roll twice

    const maxPlayers   = (room.seats ?? []).length;
    const playerStarts = _getPlayerStarts(maxPlayers);
    const legalTokens  = _legalMoves(gs, seatIndex, dv);
    const hasMoves     = legalTokens.length > 0;

    namespace.to(room.roomId).emit(socketEvents.server.DICE_ROLLED, {
      seat_index:   seatIndex,
      dice_value:   dv,
      legal_tokens: legalTokens,
      has_moves:    hasMoves,
      roll_nonce:   gs.rollNonce,   // client must echo this in MOVE_TOKEN
    });
    _persist(room);

    if (!hasMoves) {
      // No tokens can move — server auto-passes after short delay so clients
      // can animate the dice before the turn advances.
      _setGameTimer(room, () => {
        const gs2 = _gameState(room);
        if (!gs2 || gs2.over || !gs2.rolled || gs2.current !== seatIndex) return;
        namespace.to(room.roomId).emit(socketEvents.server.TOKEN_MOVED, {
          seat_index:    seatIndex,
          token_index:   -1,        // sentinel: no token moved (forced pass)
          dice_value:    dv,
          extra_turn:    false,
          is_win:        false,
          killed_tokens: [],
          tokens:        gs2.tokens,
        });
        gs2.diceValue = null;
        gs2.rolled    = false;
        _advanceTurn(room);
        _startTurn(room);
      }, NO_MOVES_AUTO_PASS_MS);
      return;
    }

    _setGameTimer(room, () => _missMove(room), MOVE_TIMEOUT_MS);
  }

  function _botRoll(room, capturedSeat) {
    const gs = _gameState(room);
    if (!gs || gs.over || gs.current !== capturedSeat) return;
    _doRoll(room, capturedSeat);
    // Bot moves after its dice animation window; guard against stale turns
    setTimeout(() => _botMove(room, capturedSeat), BOT_MOVE_DELAY);
  }

  function _botMove(room, capturedSeat) {
    const gs = _gameState(room);
    if (!gs || gs.over || !gs.rolled || gs.diceValue == null) return;
    if (gs.current !== capturedSeat) return;   // turn advanced already

    const maxPlayers   = (room.seats ?? []).length;
    const playerStarts = _getPlayerStarts(maxPlayers);
    const legal        = _legalMoves(gs, capturedSeat, gs.diceValue);
    if (legal.length === 0) return;            // auto-pass timer handles it

    // Strategy: prefer tokens already on the board; otherwise take any legal token
    const onBoard = legal.filter(ti => gs.tokens[capturedSeat][ti] >= 0);
    const chosen  = onBoard.length > 0 ? onBoard[0] : legal[0];
    _doMove(room, capturedSeat, chosen);
  }

  // Core move processor — all game-outcome computation is done here.
  // Client-supplied extra_turn and is_win are intentionally ignored.
  function _doMove(room, seatIndex, tokenIndex) {
    const gs = _gameState(room);
    if (!gs || gs.over) return;
    _clearGameTimer(room);

    const dv           = gs.diceValue;
    const maxPlayers   = (room.seats ?? []).length;
    const playerStarts = _getPlayerStarts(maxPlayers);

    // Validate: if the chosen token is illegal, restart the move timer and let
    // the player try again (or timeout and lose the turn).
    if (tokenIndex >= 0 && !_canMoveToken(gs.tokens[seatIndex][tokenIndex], dv)) {
      console.warn(
        `[LudoEngine] _doMove: illegal tokenIndex=${tokenIndex} pos=${gs.tokens[seatIndex][tokenIndex]} dv=${dv}`
      );
      _setGameTimer(room, () => _missMove(room), MOVE_TIMEOUT_MS);
      return;
    }

    let extraTurn = false;
    let isWin     = false;
    let killed    = [];

    if (tokenIndex >= 0) {
      const result = _applyTokenMove(gs, seatIndex, tokenIndex, dv, playerStarts);
      extraTurn = result.extraTurn;
      isWin     = result.isWin;
      killed    = result.killed;
    }

    namespace.to(room.roomId).emit(socketEvents.server.TOKEN_MOVED, {
      seat_index:    seatIndex,
      token_index:   tokenIndex,    // -1 = pass (caller-supplied for no-move path)
      dice_value:    dv,
      extra_turn:    extraTurn,     // server-computed, not client-supplied
      is_win:        isWin,         // server-computed, not client-supplied
      killed_tokens: killed,        // [{seat_index, token_index}] — opponents sent home
      tokens:        gs.tokens,     // full authoritative snapshot for client reconciliation
    });

    if (isWin) {
      gs.finished.add(seatIndex);
      const remaining = gs.active.filter(i => !gs.finished.has(i));
      // Game ends when only one (or zero) active players remain unfinished
      if (remaining.length <= 1) {
        remaining.forEach(i => gs.finished.add(i));
        gs.over = true;
        _persist(room);
        _autoSettle(room).catch(err =>
          console.error('[LudoEngine] _autoSettle error:', err.message)
        );
        return;
      }
    }

    if (!extraTurn) {
      _advanceTurn(room);
    } else {
      gs.diceValue = null;
      gs.rolled    = false;
    }

    _startTurn(room);
  }

  // Build finish-order placements and run settlement without a client socket.
  async function _autoSettle(room) {
    const gs    = _gameState(room);
    const seats = room.seats ?? [];
    if (!gs) return;

    const finishedArr = [...gs.finished];               // ordered by insertion
    const allSeats    = seats.map((_, i) => i);
    const notFinished = allSeats.filter(i => !gs.finished.has(i));
    const ranked      = [...finishedArr, ...notFinished];

    const placements = ranked.map((si, rank) => ({
      seat_no:         seats[si]?.seatNo   ?? si + 1,
      user_id:         seats[si]?.userId   ?? null,
      finish_position: rank + 1,
      score:           rank === 0 ? 100 : 0,
      is_winner:       rank === 0,
      result:          rank === 0 ? 'win' : 'loss',
    }));

    const winnerSeat = seats[finishedArr[0]];
    const winner     = winnerSeat
      ? { seat_no: winnerSeat.seatNo, user_id: winnerSeat.userId ?? null }
      : null;

    await _serverCompleteMatch(room, winner, placements);
  }

  // Server-initiated settlement (no originating socket).
  async function _serverCompleteMatch(room, winner, placements) {
    if (!room) return;
    clearRoomTimer(room.roomId);
    clearRoomStartRetryTimer(room.roomId);

    if (room.settlementPromise) {
      await room.settlementPromise;
      return;
    }

    // Distributed settlement lock — only one node runs settlement per room.
    // 'won'    → proceed.  'locked' → another node is settling, skip silently.
    // 'error'  → Redis unavailable; retry up to 3 times before aborting.
    let lockResult = await cluster.acquireSettleLock(room.roomId);
    if (lockResult === 'error') {
      for (let attempt = 1; attempt <= 3 && lockResult === 'error'; attempt++) {
        await new Promise(r => setTimeout(r, 500 * attempt));
        lockResult = await cluster.acquireSettleLock(room.roomId);
      }
    }
    if (lockResult !== 'won') {
      if (lockResult === 'locked') {
        console.log(`[LudoCluster] settlement lock held by another node for room ${room.roomId} — skipping`);
      } else {
        console.error(`[LudoCluster] could not acquire settlement lock for room ${room.roomId} after retries — settlement aborted`);
      }
      return;
    }

    room.settlementPromise = (async () => {
      room.state = roomStates.SETTLEMENT_PENDING;
      emitSnapshot(room);

      const resultPayload = {
        cancelled: false,
        winner,
        placements,
        result_payload: {
          room_id:      room.roomId,
          node_room_id: room.roomId,
          seats:        toSeatPayload(room),
          winner,
          placements,
          cancelled:    false,
        },
      };

      let settledMatch = null;

      if (room.mode === 'tournament' || room.playMode === 'tournament') {
        try {
          const seatResults = placements.map(p => ({
            seat_no:    p.seat_no,
            final_rank: p.finish_position,
            score:      p.score ?? 0,
          }));
          const rankings = tournamentLudoRoomService.buildRankingsFromSeatResults(
            serializeRoom(room), seatResults
          );
          settledMatch = await runSettlementSync(() =>
            tournamentLudoLaravelSyncService.completeTournamentRoom(room.roomId, rankings)
          );

          if (room.tournamentMatchId) {
            const resultsList = tournamentMatchResultService.buildResultsFromSeatState(
              serializeRoom(room),
              placements.map(p => ({
                seatNo:         p.seat_no,
                userId:         p.user_id ?? null,
                score:          p.score   ?? 0,
                finishPosition: p.finish_position,
                result:         p.result ?? (p.finish_position === 1 ? 'win' : 'loss'),
              }))
            );
            await runSettlementSync(() =>
              tournamentMatchResultService.postResult({
                matchId:   room.tournamentMatchId,
                roomId:    room.roomId,
                startedAt: room.startedAt ?? new Date(),
                endedAt:   new Date(),
                results:   resultsList,
                gameLog:   null,
              })
            ).catch(err => {
              console.error(
                `[TournamentMatchResult] Failed to post result for match ${room.tournamentMatchId}:`,
                err.message
              );
            });
          }
        } catch (err) {
          console.error('[_serverCompleteMatch] Tournament settlement error:', err.message);
        }
      } else if (laravelSync.isEnabled() && room.matchUuid) {
        try {
          settledMatch = await runSettlementSync(() =>
            laravelSync.notifyMatchCompleted(room.matchUuid, resultPayload)
          );
        } catch (err) {
          console.error('[_serverCompleteMatch] Cash game settlement error:', err.message);
        }
      }

      room.state       = roomStates.COMPLETED;
      room.completedAt = new Date().toISOString();

      namespace.to(room.roomId).emit(socketEvents.server.RESULT, {
        room_id:    room.roomId,
        match_uuid: room.matchUuid ?? settledMatch?.match_uuid ?? null,
        cancelled:  false,
        winner,
        placements,
        settlement: settledMatch ?? null,
      });

      emitSnapshot(room);
      // Room is fully settled — release cluster resources and purge from Redis
      cluster.releaseOwnership(room.roomId).catch(() => {});
      cluster.releaseSettleLock(room.roomId).catch(() => {});
      persistence.deleteRoom(room.roomId).catch(() => {});
    })();

    try {
      await room.settlementPromise;
    } finally {
      room.settlementPromise = null;
    }
  }

  async function startServerDrivenGame(room) {
    if (!_allHumanRoom(room)) return;

    // Ownership MUST be confirmed before initialising any state or arming timers.
    // If two nodes both receive the last-player-join event, only the winner of
    // the atomic SET NX proceeds; the loser returns here without touching _gs.
    const owned = await cluster.claimOwnership(room.roomId).catch(() => false);
    if (!owned) {
      console.log(`[LudoCluster] another node owns room ${room.roomId} — skipping game start`);
      return;
    }

    const seats      = room.seats ?? [];
    const maxPlayers = seats.length;

    room._gs = {
      active:       seats.map((_, i) => i),
      finished:     new Set(),
      current:      0,
      diceValue:    null,
      rolled:       false,
      sixRun:       0,
      over:         false,
      tokens:       Array.from({ length: seats.length }, () =>
                      Array(TOKENS_PER_PLAYER).fill(-1)),
      playerStarts: _getPlayerStarts(maxPlayers),
      // Anti-cheat nonce chain: turn → roll → move
      // Each step issues a new nonce; the next step must echo it.
      turnNonce:    null,   // set in _startTurn; client must include in ROLL_DICE
      rollNonce:    null,   // set in _doRoll;   client must include in MOVE_TOKEN
    };
    room._gsTimer = null;

    // Persist initial game state before first turn fires
    _persist(room);

    // Give clients 5.5 s to load the board before the first turn fires
    setTimeout(() => _startTurn(room), 5500);
    console.log(
      `[LudoEngine] started server-driven game roomId=${room.roomId} seats=${maxPlayers}`
    );
  }

  // ── End server-driven game engine ──────────────────────────────────────────

  async function startRoom(room, startedWithBots) {
    if (!room) {
      return false;
    }

    if (room.startPromise) {
      return room.startPromise;
    }

    if (room.state === roomStates.STARTING || room.state === roomStates.IN_PROGRESS || room.state === roomStates.COMPLETED) {
      return true;
    }

    room.startPromise = (async () => {
      room.fillBotsAt = null;
      clearRoomTimer(room.roomId);
      clearRoomStartRetryTimer(room.roomId);
      room.state = roomStates.STARTING;
      emitSnapshot(room);

      if (laravelSync.isEnabled()) {
        const isPractice = room.playMode === 'practice' || room.entryFee === 0;
        try {
          const match = await runStartSync(() => laravelSync.notifyMatchStarted(room));
          if (match?.match_uuid) {
            room.matchUuid = match.match_uuid;
          }
        } catch (error) {
          console.error(error.message);
          if (!isPractice) {
            // Cash games: block on Laravel failure (wallet settlement needs it)
            scheduleStartRetry(room, startedWithBots);
            namespace.to(room.roomId).emit(socketEvents.server.ERROR, {
              message: "Unable to persist Ludo match start.",
            });
            return false;
          }
          // Practice / private / free games: log warning but continue anyway
          console.warn('[startRoom] Laravel sync failed for practice room — continuing without match_uuid');
        }
      }

      room.startRetryCount = 0;
      room.state = roomStates.IN_PROGRESS;
      room.startedAt = new Date();
      namespace.to(room.roomId).emit(socketEvents.server.STARTING, {
        room_id: room.roomId,
        started_with_bots: startedWithBots,
        match_uuid: room.matchUuid ?? null,
        seats: room.seats,
      });
      // Tell each connected player their own seat number individually so Unity
      // can set localSeatOffset without relying on userId matching in deserialized JSON.
      namespace.in(room.roomId).fetchSockets().then(sockets => {
        console.log(`[my_seat] room=${room.roomId} found ${sockets.length} sockets`);
        for (const sock of sockets) {
          const uid = sock.data?.userId;
          if (!uid) { console.log(`[my_seat] skip socket — no userId`); continue; }
          const seat = (room.seats ?? []).find(s => String(s.userId ?? '') === String(uid));
          if (!seat) { console.log(`[my_seat] skip uid=${uid} — no matching seat`); continue; }
          console.log(`[my_seat] emit uid=${uid} seat_no=${seat.seatNo}`);
          sock.emit('ludo.game.my_seat', { seat_no: seat.seatNo, room_id: room.roomId });
        }
      }).catch(err => { console.error('[my_seat] fetchSockets error:', err); });
      emitSnapshot(room);
      startServerDrivenGame(room);
      return true;
    })();

    try {
      return await room.startPromise;
    } finally {
      room.startPromise = null;
    }
  }

  function buildTournamentRoomFromClaim(claimData, userId, tournamentUuid, tournamentEntryUuid) {
    const roomData = claimData && claimData.data ? claimData.data : claimData;
    const players = Array.isArray(roomData.players) ? roomData.players : [];
    // allow_bots from Laravel response (tournament.bot_allowed) takes priority
    const allowBotsInTournaments =
      roomData.allow_bots !== undefined
        ? Boolean(roomData.allow_bots)
        : process.env.LUDO_ALLOW_BOTS_IN_TOURNAMENTS !== "false";
    const botFillAfterSeconds = Number(
      roomData.bot_fill_after_seconds ??
        process.env.LUDO_BOT_FILL_AFTER_SECONDS ??
        8
    );
    const seats = players.map((player) => ({
      tournamentEntryUuid:
        player?.meta?.tournament_entry_uuid ??
        player?.meta?.tournamentEntryUuid ??
        null,
      seatNo: Number(player.seat_no),
      userId: player.user_id ?? null,
      playerType: player.player_type || playerTypes.HUMAN,
      displayName: player.display_name || `Player ${player.seat_no}`,
      botCode: player.bot_code ?? null,
      isConnected:
        String(
          player?.meta?.tournament_entry_uuid ??
          player?.meta?.tournamentEntryUuid ??
          ""
        ) === String(tournamentEntryUuid) ||
        String(player.user_id ?? "") === String(userId),
      isReady:
        String(
          player?.meta?.tournament_entry_uuid ??
          player?.meta?.tournamentEntryUuid ??
          ""
        ) === String(tournamentEntryUuid) ||
        String(player.user_id ?? "") === String(userId),
      meta: player.meta || {},
    }));

    return {
      roomId: roomData.uuid,
      gameSlug: "ludo",
      roomType: "tournament",
      playMode: "tournament",
      mode: roomData.mode || "tournament",
      state: roomData.status === "completed" ? roomStates.COMPLETED : roomStates.WAITING,
      maxPlayers: Number(roomData.max_players || seats.length || 4),
      currentPlayers: Number(roomData.current_players || seats.length),
      realPlayers: Number(roomData.current_real_players || seats.filter((seat) => seat.playerType === playerTypes.HUMAN).length),
      botPlayers: Number(roomData.current_bot_players || 0),
      allowBots: allowBotsInTournaments,
      minRealPlayers: Number(roomData.min_real_players || 1),
      botFillAfterSeconds: Math.max(0, botFillAfterSeconds),
      botStartPolicy: roomData.bot_start_policy || (allowBotsInTournaments ? "hybrid" : "disabled"),
      fillBotsAt: null,
      entryFee: Number(roomData.entry_fee || 0),
      matchUuid: roomData.match_uuid ?? null,
      // New: Laravel tournament_matches.id for result posting
      tournamentMatchId: roomData.tournament_match_id ?? null,
      tournamentId: roomData.tournament_id ?? null,
      tournamentUuid,
      tournamentEntryUuid,
      connectedEntryUuids: tournamentEntryUuid ? [String(tournamentEntryUuid)] : [],
      connectedUserIds: userId !== null && userId !== undefined ? [String(userId)] : [],
      players,
      seats,
      startedAt: null,
      queueKey: `ludo:tournament:${tournamentUuid}`,
    };
  }

  async function claimTournamentRoom(socket, payload = {}) {
    const tournamentUuid = payload.tournamentUuid ?? payload.tournament_uuid;
    const tournamentEntryUuid = payload.tournamentEntryUuid ?? payload.tournament_entry_uuid;
    const accessToken = payload.accessToken ?? payload.access_token;
    const userId = payload.userId ?? payload.user_id ?? socket.data.userId ?? null;

    if (!tournamentUuid || !tournamentEntryUuid || !accessToken) {
      socket.emit("ludo.tournament.room_claim_failed", {
        message: "Tournament room claim requires tournament UUID, tournament entry UUID, and access token.",
      });
      return;
    }

    try {
      // Tournament winners can immediately re-claim into the next round after a result event.
      // Force-leave any previous room first so socket state and room occupancy stay consistent.
      leaveRoom(socket);

      const claimData = await tournamentLudoRoomService.claimRoom(
        accessToken,
        tournamentUuid,
        tournamentEntryUuid
      );

      const room = buildTournamentRoomFromClaim(
        claimData,
        userId,
        tournamentUuid,
        tournamentEntryUuid
      );
      const existingRoom = rooms.get(room.roomId);
      const mergedRoom = mergeTournamentRoomState(existingRoom, room, userId, tournamentEntryUuid);

      rooms.set(mergedRoom.roomId, mergedRoom);
      socket.join(room.roomId);
      socket.data.roomId = room.roomId;
      socket.data.userId = userId;
      socket.data.tournamentEntryUuid = tournamentEntryUuid;
      syncSocketSeatContext(socket, mergedRoom);

      namespace.to(mergedRoom.roomId).emit("ludo.tournament.room_claimed", serializeRoom(mergedRoom));
      loadRoomChatHistory(socket, mergedRoom).catch(() => {});
      emitSnapshot(mergedRoom);

      if (isTournamentRoomReadyToStart(mergedRoom)) {
        await startRoom(mergedRoom, false);
      } else if (
        mergedRoom.allowBots &&
        (
          (mergedRoom.currentPlayers < mergedRoom.maxPlayers && supportsTournamentBotFill(mergedRoom)) ||
          (hasDisconnectedHumanSeats(mergedRoom) && supportsTournamentOfflineReplacement(mergedRoom))
        )
      ) {
        scheduleBotFill(mergedRoom);
      }
    } catch (error) {
      socket.emit("ludo.tournament.room_claim_failed", {
        message:
          error?.response?.data?.message ||
          error?.message ||
          "Unable to claim tournament room.",
      });
    }
  }

  function scheduleBotFill(room) {
    clearRoomTimer(room.roomId);

    const delayMs = room.botFillAfterSeconds * 1000;
    room.fillBotsAt = new Date(Date.now() + delayMs).toISOString();
    room.state = roomStates.WAITING_BOT_FILL;
    emitSnapshot(room);

    const timerId = setTimeout(() => {
      const currentRoom = rooms.get(room.roomId);

      if (!currentRoom) {
        return;
      }

      const decision = engine.buildStartDecision(currentRoom);

      if (
        (currentRoom.mode !== "tournament" || supportsTournamentBotFill(currentRoom)) &&
        decision.shouldFillBots &&
        decision.botSeat
      ) {
        const seat = decision.botSeat;
        currentRoom.seats.push({
          seatNo: seat.seatNo,
          playerType: playerTypes.BOT,
          botCode: seat.botCode,
          displayName: seat.displayName,
          isConnected: true,
          isReady: true,
        });
        currentRoom.botPlayers += 1;
        currentRoom.currentPlayers += 1;

        namespace
          .to(currentRoom.roomId)
          .emit(socketEvents.server.BOT_JOINED, {
            room_id: currentRoom.roomId,
            seat,
          });
      }

      if (
        currentRoom.mode === "tournament" &&
        currentRoom.playMode === "tournament" &&
        supportsTournamentOfflineReplacement(currentRoom) &&
        hasDisconnectedHumanSeats(currentRoom)
      ) {
        const replacedSeats = replaceDisconnectedHumanSeatsWithBots(currentRoom);
        replacedSeats.forEach((seat) => {
          namespace
            .to(currentRoom.roomId)
            .emit(socketEvents.server.BOT_JOINED, {
              room_id: currentRoom.roomId,
              seat: {
                seatNo: seat.seatNo,
                playerType: seat.playerType,
                botCode: seat.botCode,
                displayName: seat.displayName,
              },
            });
        });
      }

      clearRoomTimer(currentRoom.roomId);

      if (
        currentRoom.currentPlayers >= currentRoom.maxPlayers &&
        !hasDisconnectedHumanSeats(currentRoom)
      ) {
        startRoom(currentRoom, currentRoom.botPlayers > 0);
        return;
      }

      currentRoom.state = roomStates.WAITING_BOT_FILL;
      emitSnapshot(currentRoom);
      if (
        (currentRoom.currentPlayers < currentRoom.maxPlayers && (currentRoom.mode !== "tournament" || supportsTournamentBotFill(currentRoom))) ||
        (currentRoom.mode === "tournament" && supportsTournamentOfflineReplacement(currentRoom) && hasDisconnectedHumanSeats(currentRoom))
      ) {
        scheduleBotFill(currentRoom);
      }
    }, delayMs);

    roomTimers.set(room.roomId, timerId);
  }

  function joinQueue(socket, payload = {}) {
    const key = queueKey(payload);
    const requestedRoomId = payload.roomUuid ?? payload.room_uuid ?? null;
    let room = requestedRoomId ? rooms.get(requestedRoomId) : null;

    if (!room) {
      room = [...rooms.values()].find(
        (candidate) =>
          candidate.queueKey === key &&
          (candidate.state === roomStates.WAITING ||
            candidate.state === roomStates.WAITING_BOT_FILL) &&
          candidate.currentPlayers < candidate.maxPlayers
      );
    }

    if (!room) {
      room = engine.buildWaitingRoom({
        roomId: requestedRoomId ?? uuidv4(),
        roomType: payload.roomType ?? "public",
        playMode: payload.playMode ?? "cash",
        maxPlayers: Number(payload.maxPlayers ?? 4),
        entryFee: Number(payload.entryFee ?? 0),
        allowBots: payload.allowBots ?? true,
      });
      room.queueKey = key;
      room.seats = [];
      room.fillBotsAt = null;
      rooms.set(room.roomId, room);
    }

    if (requestedRoomId && room.roomId !== requestedRoomId) {
      namespace.to(socket.id).emit(socketEvents.server.ERROR, {
        message: "Socket room does not match the queued Laravel room.",
      });
      return;
    }

    const existingSeat = room.seats.find(
      (seat) => seat.userId && String(seat.userId) === String(payload.userId)
    );

    if (existingSeat) {
      socket.join(room.roomId);
      socket.data.roomId = room.roomId;
      syncSocketSeatContext(socket, room);
      socket.emit(socketEvents.server.ROOM_WAITING, serializeRoom(room));
      loadRoomChatHistory(socket, room).catch(() => {});
      return;
    }

    const occupiedSeats = room.seats.map((seat) => seat.seatNo);
    let seatNo = 1;
    while (occupiedSeats.includes(seatNo) && seatNo <= room.maxPlayers) {
      seatNo += 1;
    }

    const seat = {
      seatNo,
      userId: payload.userId ?? null,
      playerType: playerTypes.HUMAN,
      displayName: payload.displayName ?? `Player ${seatNo}`,
      isConnected: true,
      isReady: false,
    };

    room.seats.push(seat);
    room.realPlayers += 1;
    room.currentPlayers += 1;
    socket.join(room.roomId);
    socket.data.roomId = room.roomId;
    socket.data.userId = seat.userId;
    socket.data.seatNo = seat.seatNo;
    socket.data.playerType = seat.playerType;
    socket.data.displayName = seat.displayName;
    _persist(room);

    namespace.to(room.roomId).emit(socketEvents.server.PLAYER_JOINED, {
      room_id: room.roomId,
      seat,
    });
    socket.emit(socketEvents.server.ROOM_WAITING, serializeRoom(room));
    loadRoomChatHistory(socket, room).catch(() => {});
    emitSnapshot(room);

    if (room.currentPlayers >= room.maxPlayers) {
      clearRoomTimer(room.roomId);
      startRoom(room, false);
    } else if (room.allowBots && !roomTimers.has(room.roomId)) {
      scheduleBotFill(room);
    }
  }

  function leaveRoom(socket) {
    const roomId = socket.data.roomId;
    if (!roomId || !rooms.has(roomId)) {
      return;
    }

    const room = rooms.get(roomId);
    const isTournamentRoom = room.mode === "tournament" || room.playMode === "tournament";
    const before = room.seats.length;
    const socketTournamentEntryUuid = socket.data.tournamentEntryUuid ?? null;
    const socketUserId = socket.data.userId ?? null;

    if (isTournamentRoom) {
      let markedDisconnected = false;
      room.seats = room.seats.map((seat) => {
        const seatTournamentEntryUuid =
          seat?.tournamentEntryUuid ??
          seat?.meta?.tournament_entry_uuid ??
          seat?.meta?.tournamentEntryUuid ??
          null;
        const matchesSeat = socketTournamentEntryUuid
          ? String(seatTournamentEntryUuid ?? "") === String(socketTournamentEntryUuid)
          : String(seat.userId ?? "") === String(socketUserId ?? "");

        if (!matchesSeat) {
          return seat;
        }

        markedDisconnected = true;
        return {
          ...seat,
          isConnected: false,
          isReady: false,
        };
      });

      if (markedDisconnected) {
        room.connectedEntryUuids = (room.connectedEntryUuids ?? []).filter(
          (value) => String(value) !== String(socketTournamentEntryUuid ?? "")
        );
        room.connectedUserIds = (room.connectedUserIds ?? []).filter(
          (value) => String(value) !== String(socketUserId ?? "")
        );
      }

      socket.leave(roomId);
      socket.data.roomId = null;
      socket.data.tournamentEntryUuid = null;
      emitSnapshot(room);
      return;
    }

    // If a game is in progress, keep the seat but mark it disconnected so the
    // player can reconnect.  Only remove the seat if no game has started yet.
    const gameInProgress = !!_gameState(room);
    let removed = 0;

    if (gameInProgress) {
      room.seats = room.seats.map((seat) => {
        if (String(seat.userId ?? '') !== String(socket.data.userId ?? '')) return seat;
        removed++;
        return { ...seat, isConnected: false };
      });
    } else {
      room.seats = room.seats.filter((seat) => {
        if (socketTournamentEntryUuid) {
          const seatTournamentEntryUuid =
            seat?.tournamentEntryUuid ??
            seat?.meta?.tournament_entry_uuid ??
            seat?.meta?.tournamentEntryUuid ??
            null;
          return String(seatTournamentEntryUuid ?? "") !== String(socketTournamentEntryUuid);
        }
        return String(seat.userId ?? "") !== String(socket.data.userId ?? "");
      });
      removed = before - room.seats.length;
    }

    if (removed > 0 && !gameInProgress) {
      room.realPlayers = Math.max(0, room.realPlayers - removed);
      room.currentPlayers = Math.max(0, room.currentPlayers - removed);
    }

    socket.leave(roomId);
    socket.data.roomId = null;
    socket.data.tournamentEntryUuid = null;

    if (room.currentPlayers === 0 && !gameInProgress) {
      clearRoomTimer(roomId);
      rooms.delete(roomId);
      persistence.deleteRoom(roomId).catch(() => {});
      cluster.releaseOwnership(roomId).catch(() => {});
      return;
    }

    _persist(room);
    emitSnapshot(room);
  }

  async function completeMatch(socket, payload = {}, cancelled = false) {
    const roomId = payload.roomId ?? payload.room_id ?? socket.data.roomId;
    if (!roomId || !rooms.has(roomId)) {
      socket.emit(socketEvents.server.ERROR, {
        message: "Room not found.",
      });
      return;
    }

    const room = rooms.get(roomId);

    // Verify socket is a participant in this room before touching settlement
    const settlingSeat = _seatForSocket(room, socket, socket.data?.userId);
    if (!settlingSeat) {
      _acViolation(socket, 'complete_match:not_in_room', { roomId });
      socket.emit(socketEvents.server.ERROR, { message: 'Not a participant in this room.' });
      return;
    }

    clearRoomTimer(room.roomId);
    clearRoomStartRetryTimer(room.roomId);

    if (room.settlementPromise) {
      await room.settlementPromise;
      return;
    }

    // Block ALL client-initiated settlement when a server-driven engine exists.
    // This closes the race window between gs.over=true and settlementPromise being set —
    // a malicious client cannot race in with forged placements in that tick.
    if (room._gs) {
      socket.emit(socketEvents.server.ERROR, { message: 'Settlement is handled automatically by the server.' });
      return;
    }

    room.settlementPromise = (async () => {
      room.state = cancelled ? roomStates.CANCELLED : roomStates.SETTLEMENT_PENDING;
      emitSnapshot(room);

      // Validate that every placement references a real seat in this room
      const rawPlacements = Array.isArray(payload.placements) ? payload.placements : [];
      const validSeatNos  = new Set((room.seats ?? []).map(s => Number(s.seatNo)));
      const invalidSeats  = rawPlacements.filter(p => p && !validSeatNos.has(Number(p.seat_no)));
      if (invalidSeats.length > 0) {
        _acViolation(socket, 'complete_match:invalid_placements', { invalidSeats });
        return;  // exits the async IIFE; settlementPromise stays set but resolves without action
      }
      const placements = rawPlacements;
      const resultPayload = {
        cancelled,
        winner: payload.winner ?? null,
        placements,
        result_payload: {
          room_id: room.roomId,
          node_room_id: room.roomId,
          seats: toSeatPayload(room),
          winner: payload.winner ?? null,
          placements,
          cancelled,
        },
      };

      let settledMatch = null;
      if (room.mode === "tournament" || room.playMode === "tournament") {
        try {
          const seatResults = Array.isArray(payload.seatResults)
            ? payload.seatResults
            : placements.map((placement) => ({
                seat_no: placement.seat_no,
                final_rank: placement.finish_position,
                score: placement.score ?? 0,
              }));

          const rankings = tournamentLudoRoomService.buildRankingsFromSeatResults(
            serializeRoom(room),
            seatResults
          );

          settledMatch = await runSettlementSync(() =>
            tournamentLudoLaravelSyncService.completeTournamentRoom(
              room.roomId,
              rankings
            )
          );

          // ── NEW: Post result to new tournament match result endpoint ──────────
          if (room.tournamentMatchId) {
            const resultsList = tournamentMatchResultService.buildResultsFromSeatState(
              serializeRoom(room),
              placements.map((p) => ({
                seatNo:         p.seat_no ?? p.seatNo,
                userId:         p.user_id ?? p.userId ?? null,
                score:          p.score ?? 0,
                finishPosition: p.finish_position ?? p.finishPosition,
                result:         p.result ?? (p.finish_position === 1 ? "win" : "loss"),
              }))
            );

            await runSettlementSync(() =>
              tournamentMatchResultService.postResult({
                matchId:    room.tournamentMatchId,
                roomId:     room.roomId,
                startedAt:  room.startedAt ?? new Date(),
                endedAt:    new Date(),
                results:    resultsList,
                gameLog:    payload.gameLog ?? null,
              })
            ).catch((err) => {
              // Non-blocking: log but don't fail the settlement
              console.error(`[TournamentMatchResult] Failed to post result for match ${room.tournamentMatchId}:`, err.message);
            });
          }
        } catch (error) {
          console.error(error.message);
          socket.emit(socketEvents.server.ERROR, {
            message: cancelled
              ? "Unable to cancel tournament Ludo match settlement."
              : "Unable to settle tournament Ludo match.",
          });
          return;
        }
      } else if (laravelSync.isEnabled() && room.matchUuid) {
        try {
          settledMatch = await runSettlementSync(() =>
            laravelSync.notifyMatchCompleted(room.matchUuid, resultPayload)
          );
        } catch (error) {
          console.error(error.message);
          socket.emit(socketEvents.server.ERROR, {
            message: cancelled
              ? "Unable to cancel Ludo match settlement."
              : "Unable to settle Ludo match.",
          });
          return;
        }
      }

      room.state = cancelled ? roomStates.CANCELLED : roomStates.COMPLETED;
      room.completedAt = new Date().toISOString();

      namespace.to(room.roomId).emit(socketEvents.server.RESULT, {
        room_id: room.roomId,
        match_uuid: room.matchUuid ?? settledMatch?.match_uuid ?? null,
        cancelled,
        winner: payload.winner ?? null,
        placements,
        settlement: settledMatch ?? null,
      });

      emitSnapshot(room);
    })();

    try {
      await room.settlementPromise;
    } finally {
      room.settlementPromise = null;
    }
  }

  namespace.on("connection", (socket) => {
    socket.on(socketEvents.client.JOIN_QUEUE, (payload = {}) => {
      payload = normalizePayload(payload);
      socket.data.userId = payload.userId ?? payload.user_id ?? null;
      joinQueue(socket, {
        userId: payload.userId ?? payload.user_id ?? null,
        displayName: payload.displayName ?? payload.display_name,
        roomUuid: payload.roomUuid ?? payload.room_uuid ?? null,
        roomType: payload.roomType ?? payload.room_type,
        playMode: payload.playMode ?? payload.play_mode,
        gameMode: payload.gameMode ?? payload.game_mode,
        maxPlayers: payload.maxPlayers ?? payload.max_players,
        entryFee: payload.entryFee ?? payload.entry_fee,
        allowBots: payload.allowBots ?? payload.allow_bots,
      });
    });

    socket.on("ludo.tournament.claim_room", async (payload = {}) => {
      payload = normalizePayload(payload);
      await claimTournamentRoom(socket, payload);
    });

    socket.on(socketEvents.client.LEAVE_ROOM, () => {
      leaveRoom(socket);
    });

    socket.on(socketEvents.client.CHAT_SEND, async (payload = {}) => {
      payload = normalizePayload(payload);
      await handleChatSend(socket, payload);
    });

    socket.on(socketEvents.client.CHAT_HISTORY, async (payload = {}) => {
      payload = normalizePayload(payload);
      const roomId = payload.roomId ?? payload.room_id ?? socket.data.roomId;
      const room = roomId ? rooms.get(roomId) : null;

      if (!room) {
        socket.emit(socketEvents.server.ERROR, {
          message: "Room not found.",
        });
        return;
      }

      await loadRoomChatHistory(socket, room, payload.limit ?? 50);
    });

    socket.on(socketEvents.client.CHAT_EMOJI, (payload = {}) => {
      payload = normalizePayload(payload);
      handleChatEmoji(socket, payload);
    });

    socket.on(socketEvents.client.RECONNECT, async (payload = {}) => {
      payload = normalizePayload(payload);
      const roomId = payload.roomId ?? payload.room_id;
      let room = roomId ? rooms.get(roomId) : null;

      // ── Crash-recovery path ──────────────────────────────────────────────
      // If the room is not in memory (server restarted), try to restore it
      // from Redis so the player can resume the game.
      if (!room && roomId) {
        const saved = await persistence.loadRoom(roomId).catch(() => null);
        if (saved) {
          room = Object.assign({}, saved.meta);
          room._gs      = saved.gs ?? null;
          room._gsTimer = null;
          room._gsTimerExpiresAt = null;
          rooms.set(room.roomId, room);
          // Re-arm the game timer using the persisted remaining time
          if (room._gs && !room._gs.over && saved.timerRemainingMs !== null) {
            const remainMs = Math.max(200, saved.timerRemainingMs);
            const gs = room._gs;
            if (gs.rolled && gs.diceValue != null) {
              // Was mid-move: re-arm miss-move timer
              _setGameTimer(room, () => _missMove(room), remainMs);
            } else {
              // Was waiting for roll: re-arm miss-roll timer
              _setGameTimer(room, () => _missRoll(room), remainMs);
            }
          }
          console.log(`[LudoRedis] recovered room ${roomId} from Redis after restart`);
        }
      }

      if (!room) {
        socket.emit(socketEvents.server.ERROR, { message: "Room not found." });
        return;
      }

      // Verify the reconnecting user actually belongs to a seat in this room
      const claimedUid = payload.user_id ?? payload.userId ?? socket.data.userId;
      const seat = claimedUid
        ? (room.seats ?? []).find(s => s && String(s.userId ?? s.user_id ?? '') === String(claimedUid))
        : null;
      if (!seat) {
        _acViolation(socket, 'reconnect:not_in_room', { roomId, claimedUid });
        socket.emit(socketEvents.server.ERROR, { message: "Not a participant in this room." });
        return;
      }

      // Mark seat as reconnected
      seat.isConnected = true;
      _persist(room);

      // Bind identity so subsequent event handlers can verify it
      socket.data.userId = String(claimedUid);
      socket.join(room.roomId);
      socket.data.roomId = room.roomId;
      syncSocketSeatContext(socket, room);
      loadRoomChatHistory(socket, room).catch(() => {});
      emitSnapshot(room);

      // If a server-driven game is in progress, send full board state for reconciliation
      const gs = _gameState(room);
      if (gs && !gs.over) {
        const timerRemainingMs = room._gsTimerExpiresAt
          ? Math.max(0, room._gsTimerExpiresAt - Date.now())
          : null;
        socket.emit(socketEvents.server.GAME_STATE, {
          room_id:            room.roomId,
          tokens:             gs.tokens,
          current_seat:       gs.current,
          dice_value:         gs.diceValue ?? null,
          rolled:             gs.rolled ?? false,
          finished_seats:     [...gs.finished],
          timer_remaining_ms: timerRemainingMs,
          // Nonce chain restored so the reconnected player can act immediately
          turn_nonce: !gs.rolled ? gs.turnNonce : null,
          roll_nonce: gs.rolled  ? gs.rollNonce : null,
        });
      }
    });

    socket.on(socketEvents.client.COMPLETE_MATCH, async (payload = {}) => {
      payload = normalizePayload(payload);
      if (!_acCheckRate(socket, 'complete_match')) return;
      if (!_acValidateIdentity(socket, payload.user_id ?? payload.userId)) return;
      await completeMatch(socket, payload, false);
    });

    socket.on("ludo.tournament.match_complete", async (payload = {}) => {
      payload = normalizePayload(payload);
      if (!_acCheckRate(socket, 'complete_match')) return;
      if (!_acValidateIdentity(socket, payload.user_id ?? payload.userId)) return;
      await completeMatch(socket, payload, false);
    });

    socket.on(socketEvents.client.CANCEL_MATCH, async (payload = {}) => {
      payload = normalizePayload(payload);
      if (!_acCheckRate(socket, 'cancel_match')) return;
      if (!_acValidateIdentity(socket, payload.user_id ?? payload.userId)) return;
      await completeMatch(socket, payload, true);
    });

    socket.on(socketEvents.client.ROLL_DICE, (payload = {}) => {
      payload = normalizePayload(payload);

      // ── Anti-cheat gate ──────────────────────────────────────────────────
      if (!_acCheckRate(socket, 'roll_dice')) return;
      const claimedUserId = payload.user_id ?? payload.userId;
      if (!_acValidateIdentity(socket, claimedUserId)) return;

      const roomId = payload.room_id ?? payload.roomId ?? socket.data.roomId;
      if (!_acValidateRoom(socket, roomId)) return;

      const room   = roomId ? rooms.get(roomId) : null;
      const gs     = room ? _gameState(room) : null;
      if (!gs || gs.over) return;

      const seat = _seatForSocket(room, socket, claimedUserId);
      if (!seat) {
        _acViolation(socket, 'roll_dice:unknown_seat', { claimedUserId });
        return;
      }
      const seatIndex = (seat.seatNo ?? 1) - 1;
      if (seatIndex !== gs.current) {
        _acViolation(socket, 'roll_dice:wrong_turn', { seat: seatIndex, current: gs.current });
        return;
      }
      if (gs.rolled) {
        _acViolation(socket, 'roll_dice:duplicate', { seatIndex });
        return;
      }
      if (!_acValidateTurnNonce(socket, gs, payload.turn_nonce ?? payload.turnNonce)) return;
      // ── End anti-cheat gate ───────────────────────────────────────────────

      if (!cluster.isOwner(roomId)) {
        // Forward to the owner node via pub/sub; owner re-validates and executes
        cluster.publishCommand(roomId, 'roll_dice', {
          userId:     String(claimedUserId),
          seatIndex,
          turnNonce:  payload.turn_nonce ?? payload.turnNonce,
        }).catch(() => {});
        return;
      }
      _doRoll(room, seatIndex);
    });

    socket.on(socketEvents.client.MOVE_TOKEN, (payload = {}) => {
      payload = normalizePayload(payload);

      // ── Anti-cheat gate ──────────────────────────────────────────────────
      if (!_acCheckRate(socket, 'move_token')) return;
      const claimedUserId = payload.user_id ?? payload.userId;
      if (!_acValidateIdentity(socket, claimedUserId)) return;

      const roomId = payload.room_id ?? payload.roomId ?? socket.data.roomId;
      if (!_acValidateRoom(socket, roomId)) return;

      const room   = roomId ? rooms.get(roomId) : null;
      const gs     = room ? _gameState(room) : null;
      if (!gs || gs.over) return;

      const seat = _seatForSocket(room, socket, claimedUserId);
      if (!seat) {
        _acViolation(socket, 'move_token:unknown_seat', { claimedUserId });
        return;
      }
      const seatIndex = (seat.seatNo ?? 1) - 1;
      if (seatIndex !== gs.current) {
        _acViolation(socket, 'move_token:wrong_turn', { seat: seatIndex, current: gs.current });
        return;
      }
      if (!gs.rolled || gs.diceValue == null) {
        _acViolation(socket, 'move_token:no_roll', { seatIndex });
        return;
      }
      if (!_acValidateRollNonce(socket, gs, payload.roll_nonce ?? payload.rollNonce)) return;

      const tokenIndex = payload.token_index ?? payload.tokenIndex ?? 0;
      if (typeof tokenIndex !== 'number' || tokenIndex < 0 || tokenIndex >= TOKENS_PER_PLAYER) {
        _acViolation(socket, 'move_token:bad_token_index', { tokenIndex });
        return;
      }
      // extra_turn and is_win from client are intentionally ignored — server computes them
      // ── End anti-cheat gate ───────────────────────────────────────────────

      if (!cluster.isOwner(roomId)) {
        cluster.publishCommand(roomId, 'move_token', {
          userId:     String(claimedUserId),
          seatIndex,
          tokenIndex,
          rollNonce:  payload.roll_nonce ?? payload.rollNonce,
        }).catch(() => {});
        return;
      }
      _doMove(room, seatIndex, tokenIndex);
    });

    socket.on("disconnect", () => {
      leaveRoom(socket);
    });
  });

  // ── Startup recovery ──────────────────────────────────────────────────────
  // Restore all active rooms from Redis after a server restart.
  // Timers are re-armed with the remaining time that was persisted.
  // Sockets are not yet connected so we cannot re-join rooms here — that
  // happens on the first reconnect from each player (handled above).
  persistence.loadAllRooms().then(async saved => {
    if (!saved.length) return;
    let recovered = 0;
    for (const { meta, gs, timerRemainingMs } of saved) {
      if (rooms.has(meta.roomId)) continue;   // already in memory
      const room = Object.assign({}, meta);
      room._gs      = gs ?? null;
      room._gsTimer = null;
      room._gsTimerExpiresAt = null;
      rooms.set(room.roomId, room);

      // Attempt atomic ownership claim (SET NX EX).
      // If another live node already owns this room do NOT steal it — restore
      // the room into memory for reconnect serving but skip timer arming.
      const owned = await cluster.claimOwnership(room.roomId).catch(() => false);

      // Re-arm game timer only when we won ownership
      if (owned && room._gs && !room._gs.over && timerRemainingMs !== null) {
        const remainMs = Math.max(200, timerRemainingMs);
        if (gs.rolled && gs.diceValue != null) {
          _setGameTimer(room, () => _missMove(room), remainMs);
        } else {
          _setGameTimer(room, () => _missRoll(room), remainMs);
        }
      }
      recovered++;
    }
    if (recovered > 0) {
      console.log(`[LudoRedis] recovered ${recovered} room(s) from Redis on startup`);
    }
  }).catch(err => {
    console.error('[LudoRedis] startup recovery error:', err.message);
  });

  // ── Cluster command receiver (owner node processes forwarded actions) ─────
  // Non-owner nodes publish roll_dice / move_token commands; this node
  // executes them if it is the owner for that room.
  cluster.onCommand(({ roomId, cmd, payload }) => {
    if (!cluster.isOwner(roomId)) return;
    const room = rooms.get(roomId);
    if (!room) return;
    const { seatIndex } = payload;
    if (cmd === 'roll_dice') {
      _doRoll(room, seatIndex);
    } else if (cmd === 'move_token') {
      _doMove(room, seatIndex, payload.tokenIndex);
    }
  });

  // ── Cluster state-update receiver (non-owner nodes refresh from Redis) ───
  // When the owner persists a state change it publishes a sync notification.
  // Non-owner nodes update their in-memory copy so reconnecting players on
  // those nodes get a fresh snapshot.
  cluster.onStateUpdate(async ({ roomId }) => {
    if (cluster.isOwner(roomId)) return;   // owner already has latest state
    const saved = await persistence.loadRoom(roomId).catch(() => null);
    if (!saved) return;
    const existing = rooms.get(roomId);
    if (!existing) return;   // room not in this node's memory — nothing to refresh
    Object.assign(existing, saved.meta);
    if (saved.gs) {
      existing._gs = saved.gs;
      existing._gsTimerExpiresAt = saved.gs.timerExpiresAt ?? null;
    }
  });
};
