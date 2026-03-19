require("dotenv").config({ path: require("path").resolve(__dirname, "..", ".env") });

const { io } = require("socket.io-client");

const SOCKET_BASE_URL = process.env.LUDO_TEST_SOCKET_URL || "http://127.0.0.1:3002";
const SOCKET_NAMESPACE = process.env.LUDO_TEST_SOCKET_NAMESPACE || "/ludo_v2";
const TEST_USER_ID = Number(process.env.LUDO_TEST_USER_ID || "4");
const TEST_USER_NAME = process.env.LUDO_TEST_USER_NAME || "Betzono";
const TEST_USER_TOKEN = process.env.LUDO_TEST_TOKEN || "";
const TOURNAMENT_UUID = process.env.LUDO_TEST_TOURNAMENT_UUID || "";
const TOURNAMENT_ENTRY_UUID = process.env.LUDO_TEST_TOURNAMENT_ENTRY_UUID || "";
const CLAIM_RETRY_DELAY_MS = Number(process.env.LUDO_TEST_TOURNAMENT_RECLAIM_DELAY_MS || "750");
const TEST_TIMEOUT_MS = Number(process.env.LUDO_TEST_TOURNAMENT_TIMEOUT_MS || "45000");

let socket;
let latestSnapshot = null;
let latestStart = null;
let claimedRoomIds = [];
let resultCount = 0;
let finalResult = null;
let hasTriggeredReclaim = false;

function log(step, payload) {
  const at = new Date().toISOString();
  console.log(`[${at}] ${step}`);
  if (payload !== undefined) {
    console.log(JSON.stringify(payload, null, 2));
  }
}

function claimRoom() {
  socket.emit("ludo.tournament.claim_room", {
    userId: TEST_USER_ID,
    accessToken: TEST_USER_TOKEN,
    tournamentUuid: TOURNAMENT_UUID,
    tournamentEntryUuid: TOURNAMENT_ENTRY_UUID,
  });
}

function buildWinnerPayload() {
  const seats = latestStart?.seats || latestSnapshot?.seats || [];
  const localSeat =
    seats.find(
      (seat) =>
        Number(seat?.userId ?? 0) === TEST_USER_ID &&
        String(seat?.playerType || "human").toLowerCase() === "human"
    ) || seats[0];

  if (!localSeat) {
    throw new Error("No seat data available to build tournament completion payload.");
  }

  const placements = [...seats]
    .sort((a, b) => Number(a.seatNo) - Number(b.seatNo))
    .map((seat, index) => ({
      seat_no: Number(seat.seatNo),
      final_rank: Number(seat.seatNo) === Number(localSeat.seatNo) ? 1 : index + 2,
      score: Number(seat.seatNo) === Number(localSeat.seatNo) ? 1 : 0,
      is_winner: Number(seat.seatNo) === Number(localSeat.seatNo),
    }));

  return {
    winner: {
      seat_no: Number(localSeat.seatNo),
      user_id:
        String(localSeat.playerType || "human").toLowerCase() === "human"
          ? TEST_USER_ID
          : null,
    },
    placements,
    seatResults: placements,
  };
}

async function main() {
  if (!TEST_USER_TOKEN) {
    throw new Error("Missing LUDO_TEST_TOKEN.");
  }

  if (!TOURNAMENT_UUID || !TOURNAMENT_ENTRY_UUID) {
    throw new Error("Set LUDO_TEST_TOURNAMENT_UUID and LUDO_TEST_TOURNAMENT_ENTRY_UUID before running the test.");
  }

  await new Promise((resolve, reject) => {
    const timeout = setTimeout(() => {
      reject(new Error("Timed out waiting for tournament round progression."));
    }, TEST_TIMEOUT_MS);

    socket = io(`${SOCKET_BASE_URL}${SOCKET_NAMESPACE}`, {
      transports: ["websocket", "polling"],
      reconnection: false,
      timeout: 10000,
    });

    socket.on("connect", () => {
      log("socket.connect", { id: socket.id });
      claimRoom();
    });

    socket.on("ludo.tournament.room_claimed", (payload) => {
      latestSnapshot = payload;
      if (payload?.room_id && !claimedRoomIds.includes(payload.room_id)) {
        claimedRoomIds.push(payload.room_id);
      }
      log("ludo.tournament.room_claimed", payload);
    });

    socket.on("ludo.game.snapshot", (payload) => {
      latestSnapshot = payload;
      log("ludo.game.snapshot", payload);
    });

    socket.on("ludo.room.bot_joined", (payload) => {
      log("ludo.room.bot_joined", payload);
    });

    socket.on("ludo.room.starting", (payload) => {
      latestStart = payload;
      if (payload?.room_id && !claimedRoomIds.includes(payload.room_id)) {
        claimedRoomIds.push(payload.room_id);
      }

      log("ludo.room.starting", payload);

      const completionPayload = buildWinnerPayload();
      log("ludo.tournament.match_complete.emit", completionPayload);
      socket.emit("ludo.tournament.match_complete", completionPayload);
    });

    socket.on("ludo.game.result", (payload) => {
      resultCount += 1;
      finalResult = payload;
      log("ludo.game.result", payload);

      const tournamentStatus = String(payload?.settlement?.data?.status || "").toLowerCase();

      if (tournamentStatus === "running" && !hasTriggeredReclaim) {
        hasTriggeredReclaim = true;
        socket.emit("ludo.room.leave");
        setTimeout(() => {
          log("ludo.tournament.claim_room.reclaim", {
            tournament_uuid: TOURNAMENT_UUID,
            tournament_entry_uuid: TOURNAMENT_ENTRY_UUID,
          });
          claimRoom();
        }, CLAIM_RETRY_DELAY_MS);
        return;
      }

      if (tournamentStatus === "completed" && claimedRoomIds.length >= 2 && resultCount >= 2) {
        clearTimeout(timeout);
        resolve();
      }
    });

    socket.on("ludo.tournament.room_claim_failed", (payload) => {
      clearTimeout(timeout);
      reject(new Error(`Tournament room claim failed: ${JSON.stringify(payload)}`));
    });

    socket.on("ludo.error", (payload) => {
      clearTimeout(timeout);
      reject(new Error(`Ludo error: ${JSON.stringify(payload)}`));
    });

    socket.on("connect_error", (error) => {
      clearTimeout(timeout);
      reject(error);
    });

    socket.on("disconnect", (reason) => {
      log("socket.disconnect", { reason });
    });
  });

  log("tournament.round_progression.success", {
    claimed_room_ids: claimedRoomIds,
    result_count: resultCount,
    final_tournament_status: finalResult?.settlement?.data?.status ?? null,
  });
}

main()
  .then(() => {
    if (socket) {
      socket.close();
    }
    process.exit(0);
  })
  .catch((error) => {
    console.error(error.message || error);
    if (socket) {
      socket.close();
    }
    process.exit(1);
  });
