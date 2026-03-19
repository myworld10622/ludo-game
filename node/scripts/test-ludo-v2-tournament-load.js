require("dotenv").config({ path: require("path").resolve(__dirname, "..", ".env") });

const fs = require("fs");
const path = require("path");
const { io } = require("socket.io-client");

const SOCKET_BASE_URL = process.env.LUDO_TEST_SOCKET_URL || "http://127.0.0.1:3002";
const SOCKET_NAMESPACE = process.env.LUDO_TEST_SOCKET_NAMESPACE || "/ludo_v2";
const FIXTURE_PATH = process.env.LUDO_LOAD_FIXTURE_PATH || "";
const CONCURRENCY = Math.max(1, Number(process.env.LUDO_LOAD_CONCURRENCY || "10"));
const AUTO_COMPLETE = String(process.env.LUDO_LOAD_AUTO_COMPLETE || "false").toLowerCase() === "true";
const CLIENT_TIMEOUT_MS = Number(process.env.LUDO_LOAD_CLIENT_TIMEOUT_MS || "30000");
const RECLAIM_DELAY_MS = Number(process.env.LUDO_LOAD_RECLAIM_DELAY_MS || "1000");
const MAX_RECLAIM_RETRIES = Math.max(0, Number(process.env.LUDO_LOAD_MAX_RECLAIM_RETRIES || "10"));
const START_STAGGER_MS = Math.max(0, Number(process.env.LUDO_LOAD_START_STAGGER_MS || "75"));
const BATCH_SIZE = Math.max(0, Number(process.env.LUDO_LOAD_BATCH_SIZE || "0"));
const BATCH_PAUSE_MS = Math.max(0, Number(process.env.LUDO_LOAD_BATCH_PAUSE_MS || "2000"));
const PROGRESS_LOG_INTERVAL_MS = Math.max(0, Number(process.env.LUDO_LOAD_PROGRESS_LOG_INTERVAL_MS || "15000"));

function readFixture() {
  if (!FIXTURE_PATH) {
    throw new Error("Set LUDO_LOAD_FIXTURE_PATH to a fixture JSON file.");
  }

  const absolutePath = path.resolve(FIXTURE_PATH);
  return JSON.parse(fs.readFileSync(absolutePath, "utf8"));
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function resolveWinnerSeat(snapshot) {
  const seats = snapshot?.seats || [];
  const sortedSeats = [...seats].sort((a, b) => Number(a.seatNo) - Number(b.seatNo));
  const humanSeats = sortedSeats.filter(
    (seat) => String(seat?.playerType || "human").toLowerCase() === "human"
  );

  return humanSeats[0] || sortedSeats[0] || null;
}

function createCompletionPayload(snapshot) {
  const seats = snapshot?.seats || [];
  const winnerSeat = resolveWinnerSeat(snapshot);

  if (!winnerSeat) {
    throw new Error("No seat data available to build completion payload.");
  }

  const placements = [...seats]
    .sort((a, b) => Number(a.seatNo) - Number(b.seatNo))
    .map((seat, index) => ({
      seat_no: Number(seat.seatNo),
      final_rank: Number(seat.seatNo) === Number(winnerSeat.seatNo) ? 1 : index + 2,
      score: Number(seat.seatNo) === Number(winnerSeat.seatNo) ? 1 : 0,
      is_winner: Number(seat.seatNo) === Number(winnerSeat.seatNo),
    }));

  return {
    winner: {
      seat_no: Number(winnerSeat.seatNo),
      user_id:
        String(winnerSeat.playerType || "human").toLowerCase() === "human"
          ? Number(winnerSeat.userId ?? 0)
          : null,
    },
    placements,
    seatResults: placements,
  };
}

function resolveLocalSeat(snapshot, entry) {
  const seats = snapshot?.seats || [];
  const entryId = Number(entry.id ?? entry.tournament_entry_id ?? 0);
  const userId = Number(entry.user_id ?? 0);
  const ticketNo = String(entry.ticket_no ?? "").trim();

  const byTicketNo = seats.find((seat) => {
    const seatTicketNo = String(
      seat?.meta?.ticket_no ??
      seat?.meta?.ticketNo ??
      ""
    ).trim();

    return (
      String(seat?.playerType || "human").toLowerCase() === "human" &&
      ticketNo !== "" &&
      seatTicketNo !== "" &&
      seatTicketNo === ticketNo
    );
  });

  if (byTicketNo) {
    return byTicketNo;
  }

  const byEntryId = seats.find((seat) => {
    const seatEntryId = Number(
      seat?.meta?.tournament_entry_id ??
      seat?.meta?.tournamentEntryId ??
      0
    );

    return (
      String(seat?.playerType || "human").toLowerCase() === "human" &&
      seatEntryId > 0 &&
      seatEntryId === entryId
    );
  });

  if (byEntryId) {
    return byEntryId;
  }

  return (
    seats.find((seat) => {
      const seatUserId = Number(seat?.userId ?? 0);

      return (
        String(seat?.playerType || "human").toLowerCase() === "human" &&
        seatUserId === userId
      );
    }) || null
  );
}

async function runClient(fixture, entry) {
  return new Promise((resolve, reject) => {
    if (!entry.access_token) {
      reject(new Error(`Missing access_token for entry ${entry.entry_uuid}`));
      return;
    }

    const socket = io(`${SOCKET_BASE_URL}${SOCKET_NAMESPACE}`, {
      transports: ["websocket", "polling"],
      reconnection: false,
      timeout: 10000,
    });

    const startedAt = Date.now();
    let latestSnapshot = null;
    let currentRoomId = null;
    let currentRoomCompleted = false;
    let currentMatchWon = false;
    let awaitingNextRoundClaim = false;
    let reclaimRetryCount = 0;
    let completedMatches = 0;
    let finished = false;

    function finish(result) {
      if (finished) {
        return;
      }

      finished = true;
      clearTimeout(timeout);
      socket.close();
      resolve({
        entry_uuid: entry.entry_uuid,
        user_id: entry.user_id,
        duration_ms: Date.now() - startedAt,
        ...result,
      });
    }

    function fail(error) {
      if (finished) {
        return;
      }

      finished = true;
      clearTimeout(timeout);
      socket.close();
      reject(error);
    }

    function claimRoom() {
      socket.emit("ludo.tournament.claim_room", {
        userId: Number(entry.user_id),
        accessToken: entry.access_token,
        tournamentUuid: fixture.tournament_uuid,
        tournamentEntryUuid: entry.entry_uuid,
      });
    }

    function scheduleReclaim() {
      if (reclaimRetryCount >= MAX_RECLAIM_RETRIES) {
        finish({
          room_id: currentRoomId,
          status: "advanced_unclaimed",
          completed_matches: completedMatches,
          reclaim_retries: reclaimRetryCount,
        });
        return;
      }

      reclaimRetryCount += 1;
      setTimeout(() => {
        if (finished) {
          return;
        }

        currentRoomId = null;
        currentRoomCompleted = false;
        latestSnapshot = null;
        claimRoom();
      }, RECLAIM_DELAY_MS);
    }

    const timeout = setTimeout(() => {
      fail(new Error(`Timeout for entry ${entry.entry_uuid}`));
    }, CLIENT_TIMEOUT_MS);

    socket.on("connect", () => {
      claimRoom();
    });

    socket.on("ludo.tournament.room_claimed", (payload) => {
      latestSnapshot = payload;
      currentRoomId = payload?.room_id ?? null;
      currentRoomCompleted = false;
      currentMatchWon = false;
      awaitingNextRoundClaim = false;
      if (!AUTO_COMPLETE) {
        finish({
          room_id: payload?.room_id ?? null,
          duration_ms: Date.now() - startedAt,
          status: "claimed",
        });
      }
    });

    socket.on("ludo.room.starting", (payload) => {
      latestSnapshot = payload;
      if (AUTO_COMPLETE) {
        const winnerSeat = resolveWinnerSeat(payload);
        const localSeat = resolveLocalSeat(payload, entry);

        currentMatchWon =
          !!winnerSeat &&
          !!localSeat &&
          Number(winnerSeat.seatNo) === Number(localSeat.seatNo);

        if (currentMatchWon && !currentRoomCompleted) {
          currentRoomCompleted = true;
          socket.emit("ludo.tournament.match_complete", createCompletionPayload(payload));
        }
      }
    });

    socket.on("ludo.game.result", (payload) => {
      completedMatches += 1;
      const tournamentStatus = String(payload?.settlement?.data?.status ?? "").toLowerCase();

      if (tournamentStatus === "completed") {
        finish({
          room_id: payload?.room_id ?? latestSnapshot?.room_id ?? currentRoomId ?? null,
          status: currentMatchWon ? "completed_winner" : "completed_eliminated",
          tournament_status: payload?.settlement?.data?.status ?? null,
          completed_matches: completedMatches,
        });
        return;
      }

      if (currentMatchWon) {
        awaitingNextRoundClaim = true;
        socket.emit("ludo.room.leave");
        scheduleReclaim();
        return;
      }

      finish({
        room_id: payload?.room_id ?? latestSnapshot?.room_id ?? null,
        status: "eliminated",
        tournament_status: payload?.settlement?.data?.status ?? null,
        completed_matches: completedMatches,
      });
    });

    socket.on("ludo.tournament.room_claim_failed", (payload) => {
      const message = String(payload?.message || "");
      const normalizedMessage = message.toLowerCase();

      if (
        AUTO_COMPLETE &&
        awaitingNextRoundClaim &&
        normalizedMessage.includes("assignment not found")
      ) {
        scheduleReclaim();
        return;
      }

      if (
        AUTO_COMPLETE &&
        normalizedMessage.includes("assignment not found") &&
        completedMatches > 0
      ) {
        finish({
          room_id: currentRoomId,
          status: "eliminated",
          tournament_status: "running",
          completed_matches: completedMatches,
          reclaim_retries: reclaimRetryCount,
        });
        return;
      }

      fail(new Error(`Claim failed for ${entry.entry_uuid}: ${JSON.stringify(payload)}`));
    });

    socket.on("ludo.error", (payload) => {
      fail(new Error(`Socket error for ${entry.entry_uuid}: ${JSON.stringify(payload)}`));
    });

    socket.on("connect_error", (error) => {
      fail(error);
    });
  });
}

async function runPool(items, concurrency, worker) {
  const results = [];
  const queue = [...items];
  let launchIndex = 0;

  async function consume() {
    while (queue.length > 0) {
      const item = queue.shift();
      if (!item) {
        return;
      }

      const currentLaunchIndex = launchIndex;
      launchIndex += 1;

      if (START_STAGGER_MS > 0 && currentLaunchIndex > 0) {
        await new Promise((resolve) => setTimeout(resolve, START_STAGGER_MS));
      }

      try {
        results.push(await worker(item));
      } catch (error) {
        results.push({
          entry_uuid: item.entry_uuid,
          user_id: item.user_id,
          status: "failed",
          error: error.message || String(error),
        });
      }
    }
  }

  await Promise.all(Array.from({ length: concurrency }, consume));
  return results;
}

async function runBatches(items, concurrency, worker) {
  if (BATCH_SIZE <= 0 || BATCH_SIZE >= items.length) {
    return runPool(items, concurrency, worker);
  }

  return Promise.all(
    items.map(async (item, index) => {
      const batchIndex = Math.floor(index / BATCH_SIZE);
      const positionInBatch = index % BATCH_SIZE;
      const batchDelay = batchIndex * BATCH_PAUSE_MS;
      const staggerDelay = positionInBatch * START_STAGGER_MS;

      if (batchDelay + staggerDelay > 0) {
        await sleep(batchDelay + staggerDelay);
      }

      try {
        return await worker(item);
      } catch (error) {
        return {
          entry_uuid: item.entry_uuid,
          user_id: item.user_id,
          status: "failed",
          error: error.message || String(error),
        };
      }
    })
  );
}

async function main() {
  const fixture = readFixture();
  const entries = fixture.entries || [];

  if (entries.length === 0) {
    throw new Error("Fixture contains no entries.");
  }

  const startedAt = Date.now();
  let settledCount = 0;
  let lastLoggedSettledCount = 0;
  const progressTimer = PROGRESS_LOG_INTERVAL_MS > 0
    ? setInterval(() => {
        if (settledCount === lastLoggedSettledCount) {
          return;
        }

        lastLoggedSettledCount = settledCount;
        console.error(
          `[progress] settled ${settledCount}/${entries.length} clients after ${Date.now() - startedAt}ms`
        );
      }, PROGRESS_LOG_INTERVAL_MS)
    : null;

  const trackedWorker = async (entry) => {
    try {
      return await runClient(fixture, entry);
    } finally {
      settledCount += 1;
    }
  };

  const results = await runBatches(entries, CONCURRENCY, trackedWorker);
  if (progressTimer) {
    clearInterval(progressTimer);
  }
  const durationMs = Date.now() - startedAt;

  const summary = {
    tournament_uuid: fixture.tournament_uuid,
    total_entries: entries.length,
    concurrency: CONCURRENCY,
    auto_complete: AUTO_COMPLETE,
    batch_size: BATCH_SIZE,
    batch_pause_ms: BATCH_PAUSE_MS,
    progress_log_interval_ms: PROGRESS_LOG_INTERVAL_MS,
    duration_ms: durationMs,
    claimed: results.filter((item) => item.status === "claimed").length,
    completed: results.filter((item) => item.status === "completed").length,
    completed_winner: results.filter((item) => item.status === "completed_winner").length,
    completed_eliminated: results.filter((item) => item.status === "completed_eliminated").length,
    eliminated: results.filter((item) => item.status === "eliminated").length,
    advanced_unclaimed: results.filter((item) => item.status === "advanced_unclaimed").length,
    advanced_unassigned: results.filter((item) => item.status === "advanced_unassigned").length,
    failed: results.filter((item) => item.status === "failed").length,
  };

  console.log(JSON.stringify({ summary, results }, null, 2));
}

main().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
