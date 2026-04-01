const { v4: uuidv4 } = require("uuid");
const LudoRoomEngineService = require("../services/ludoRoomEngineService");
const LudoLaravelSyncService = require("../services/ludoLaravelSyncService");
const tournamentLudoLaravelSyncService = require("../services/tournamentLudoLaravelSyncService");
const tournamentLudoRoomService = require("../services/tournamentLudoRoomService");
const tournamentMatchResultService = require("../services/tournamentMatchResultService");
const { roomStates, playerTypes, socketEvents } = require("../constants/ludoRoom");

module.exports = function (namespace) {
  const engine = new LudoRoomEngineService();
  const laravelSync = new LudoLaravelSyncService();
  const rooms = new Map();
  const roomTimers = new Map();
  const roomStartRetryTimers = new Map();
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

  async function startRoom(room, startedWithBots) {
    if (!room) {
      return false;
    }

    if (room.startPromise) {
      return room.startPromise;
    }

    if (room.state === roomStates.STARTING || room.state === roomStates.PLAYING || room.state === roomStates.COMPLETED) {
      return true;
    }

    room.startPromise = (async () => {
      room.fillBotsAt = null;
      clearRoomTimer(room.roomId);
      clearRoomStartRetryTimer(room.roomId);
      room.state = roomStates.STARTING;
      emitSnapshot(room);

      if (laravelSync.isEnabled()) {
        try {
          const match = await runStartSync(() => laravelSync.notifyMatchStarted(room));
          if (match?.match_uuid) {
            room.matchUuid = match.match_uuid;
          }
        } catch (error) {
          console.error(error.message);
          scheduleStartRetry(room, startedWithBots);
          namespace.to(room.roomId).emit(socketEvents.server.ERROR, {
            message: "Unable to persist Ludo match start.",
          });
          return false;
        }
      }

      room.startRetryCount = 0;
      room.state = roomStates.PLAYING;
      room.startedAt = new Date();
      namespace.to(room.roomId).emit(socketEvents.server.STARTING, {
        room_id: room.roomId,
        started_with_bots: startedWithBots,
        match_uuid: room.matchUuid ?? null,
        seats: room.seats,
      });
      emitSnapshot(room);
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

      namespace.to(mergedRoom.roomId).emit("ludo.tournament.room_claimed", serializeRoom(mergedRoom));
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
      socket.emit(socketEvents.server.ROOM_WAITING, serializeRoom(room));
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

    namespace.to(room.roomId).emit(socketEvents.server.PLAYER_JOINED, {
      room_id: room.roomId,
      seat,
    });
    socket.emit(socketEvents.server.ROOM_WAITING, serializeRoom(room));
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
    const removed = before - room.seats.length;

    if (removed > 0) {
      room.realPlayers = Math.max(0, room.realPlayers - removed);
      room.currentPlayers = Math.max(0, room.currentPlayers - removed);
    }

    socket.leave(roomId);
    socket.data.roomId = null;
    socket.data.tournamentEntryUuid = null;

    if (room.currentPlayers === 0) {
      clearRoomTimer(roomId);
      rooms.delete(roomId);
      return;
    }

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
    clearRoomTimer(room.roomId);
    clearRoomStartRetryTimer(room.roomId);

    if (room.settlementPromise) {
      await room.settlementPromise;
      return;
    }

    room.settlementPromise = (async () => {
      room.state = cancelled ? roomStates.CANCELLED : roomStates.SETTLEMENT_PENDING;
      emitSnapshot(room);

      const placements = Array.isArray(payload.placements) ? payload.placements : [];
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

    socket.on(socketEvents.client.RECONNECT, (payload = {}) => {
      payload = normalizePayload(payload);
      const roomId = payload.roomId ?? payload.room_id;
      const room = roomId ? rooms.get(roomId) : null;

      if (!room) {
        socket.emit(socketEvents.server.ERROR, {
          message: "Room not found.",
        });
        return;
      }

      socket.join(room.roomId);
      socket.data.roomId = room.roomId;
      emitSnapshot(room);
    });

    socket.on(socketEvents.client.COMPLETE_MATCH, async (payload = {}) => {
      payload = normalizePayload(payload);
      await completeMatch(socket, payload, false);
    });

    socket.on("ludo.tournament.match_complete", async (payload = {}) => {
      payload = normalizePayload(payload);
      await completeMatch(socket, payload, false);
    });

    socket.on(socketEvents.client.CANCEL_MATCH, async (payload = {}) => {
      payload = normalizePayload(payload);
      await completeMatch(socket, payload, true);
    });

    socket.on("disconnect", () => {
      leaveRoom(socket);
    });
  });
};
