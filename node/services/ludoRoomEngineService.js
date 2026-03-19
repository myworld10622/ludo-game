const { roomStates, playerTypes } = require("../constants/ludoRoom");

class LudoRoomEngineService {
  constructor(config = {}) {
    this.config = {
      botFillAfterSeconds: config.botFillAfterSeconds ?? 8,
      allowBotsInPublicRooms: config.allowBotsInPublicRooms ?? true,
      minRealPlayersToStart: config.minRealPlayersToStart ?? 1,
      defaultMaxPlayers: config.defaultMaxPlayers ?? 4,
    };
  }

  buildWaitingRoom(overrides = {}) {
    const maxPlayers = overrides.maxPlayers ?? this.config.defaultMaxPlayers;
    const realPlayers = overrides.realPlayers ?? 0;
    const botPlayers = overrides.botPlayers ?? 0;

    return {
      roomId: overrides.roomId ?? null,
      gameSlug: "ludo",
      roomType: overrides.roomType ?? "public",
      playMode: overrides.playMode ?? "cash",
      state: roomStates.WAITING,
      maxPlayers,
      realPlayers,
      botPlayers,
      currentPlayers: realPlayers + botPlayers,
      allowBots: overrides.allowBots ?? this.config.allowBotsInPublicRooms,
      minRealPlayers: overrides.minRealPlayers ?? this.config.minRealPlayersToStart,
      botFillAfterSeconds:
        overrides.botFillAfterSeconds ?? this.config.botFillAfterSeconds,
      seats: overrides.seats ?? [],
      entryFee: overrides.entryFee ?? 0,
      createdAt: overrides.createdAt ?? new Date().toISOString(),
    };
  }

  shouldFillBots(room, now = Date.now()) {
    if (!room.allowBots) {
      return false;
    }

    if (room.realPlayers < room.minRealPlayers) {
      return false;
    }

    if (room.currentPlayers >= room.maxPlayers) {
      return false;
    }

    if (!room.fillBotsAt) {
      return false;
    }

    return now >= new Date(room.fillBotsAt).getTime();
  }

  buildNextBotSeat(room) {
    if (room.currentPlayers >= room.maxPlayers) {
      return null;
    }

    const occupiedSeats = new Set((room.seats ?? []).map((seat) => seat.seatNo));
    let seatNo = 1;
    while (occupiedSeats.has(seatNo) && seatNo <= room.maxPlayers) {
      seatNo += 1;
    }

    if (seatNo > room.maxPlayers) {
      return null;
    }

    const nextBotIndex = room.botPlayers + 1;
    return {
      seatNo,
      playerType: playerTypes.BOT,
      botCode: `ludo_bot_${room.roomId ?? "pending"}_${nextBotIndex}`,
      displayName: `Bot ${nextBotIndex}`,
    };
  }

  buildStartDecision(room) {
    const canStartWithHumans = room.realPlayers >= room.maxPlayers;
    const canStartWithBots =
      room.allowBots && room.realPlayers >= room.minRealPlayers;

    if (canStartWithHumans) {
      return {
        shouldStart: true,
        shouldFillBots: false,
        startedWithBots: false,
      };
    }

    if (canStartWithBots) {
      const botSeat = this.buildNextBotSeat(room);

      return {
        shouldStart: room.currentPlayers >= room.maxPlayers,
        shouldFillBots: Boolean(botSeat),
        startedWithBots: room.botPlayers > 0 || Boolean(botSeat),
        botSeat,
      };
    }

    return {
      shouldStart: false,
      shouldFillBots: false,
      startedWithBots: false,
      botSeats: [],
    };
  }
}

module.exports = LudoRoomEngineService;
