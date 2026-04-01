require("dotenv").config({ path: require("path").resolve(__dirname, "..", ".env") });

const fs = require("fs");
const path = require("path");
const { io } = require("socket.io-client");

const SOCKET_BASE_URL = process.env.LUDO_TEST_SOCKET_URL || "http://127.0.0.1:3002";
const SOCKET_NAMESPACE = process.env.LUDO_TEST_SOCKET_NAMESPACE || "/ludo_v2";
const FIXTURE_PATH = process.env.LUDO_BOT_POLICY_FIXTURE_PATH || "";
const TEST_TIMEOUT_MS = Number(process.env.LUDO_BOT_POLICY_TIMEOUT_MS || "20000");
const START_STAGGER_MS = Number(process.env.LUDO_BOT_POLICY_START_STAGGER_MS || "250");

function readFixture() {
  if (!FIXTURE_PATH) {
    throw new Error("Set LUDO_BOT_POLICY_FIXTURE_PATH to a fixture JSON file.");
  }

  const absolutePath = path.resolve(FIXTURE_PATH);
  return JSON.parse(fs.readFileSync(absolutePath, "utf8"));
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function main() {
  const fixture = readFixture();
  const entries = Array.isArray(fixture.entries) ? fixture.entries : [];

  if (entries.length === 0) {
    throw new Error("Fixture contains no entries.");
  }

  const sockets = [];
  const events = {
    room_claimed: [],
    bot_joined: [],
    room_starting: null,
  };

  let finished = false;
  let resolveDone;
  let rejectDone;

  const done = new Promise((resolve, reject) => {
    resolveDone = resolve;
    rejectDone = reject;
  });

  const timeout = setTimeout(() => {
    if (finished) {
      return;
    }
    finished = true;
    rejectDone(new Error("Timed out waiting for ludo.room.starting."));
  }, TEST_TIMEOUT_MS);

  function closeAll() {
    clearTimeout(timeout);
    sockets.forEach((socket) => {
      try {
        socket.close();
      } catch (_) {}
    });
  }

  function finishSuccess(payload) {
    if (finished) {
      return;
    }

    finished = true;
    resolveDone(payload);
  }

  for (let i = 0; i < entries.length; i += 1) {
    const entry = entries[i];
    const socket = io(`${SOCKET_BASE_URL}${SOCKET_NAMESPACE}`, {
      transports: ["websocket", "polling"],
      reconnection: false,
      timeout: 10000,
    });

    sockets.push(socket);

    socket.on("connect", async () => {
      if (i > 0 && START_STAGGER_MS > 0) {
        await sleep(i * START_STAGGER_MS);
      }

      socket.emit("ludo.tournament.claim_room", {
        userId: Number(entry.user_id),
        accessToken: entry.access_token,
        tournamentUuid: String(fixture.tournament_uuid),
        tournamentEntryUuid: String(entry.entry_uuid),
      });
    });

    socket.on("ludo.tournament.room_claimed", (payload) => {
      events.room_claimed.push({
        user_id: entry.user_id,
        room_id: payload?.room_id ?? null,
        current_players: payload?.current_players ?? null,
        real_players: payload?.real_players ?? null,
        bot_players: payload?.bot_players ?? null,
      });
    });

    socket.on("ludo.room.bot_joined", (payload) => {
      events.bot_joined.push(payload);
    });

    socket.on("ludo.room.starting", (payload) => {
      events.room_starting = payload;
      const seats = Array.isArray(payload?.seats) ? payload.seats : [];
      const humanSeats = seats.filter((seat) => String(seat?.playerType || seat?.player_type || "human").toLowerCase() === "human");
      const botSeats = seats.filter((seat) => String(seat?.playerType || seat?.player_type || "").toLowerCase() === "bot");

      finishSuccess({
        fixture: fixture.name || path.basename(FIXTURE_PATH),
        tournament_uuid: fixture.tournament_uuid,
        room_id: payload?.room_id ?? null,
        started_with_bots: Boolean(payload?.started_with_bots),
        connected_claimants: entries.length,
        bot_joined_events: events.bot_joined.length,
        final_human_seats: humanSeats.length,
        final_bot_seats: botSeats.length,
        final_total_seats: seats.length,
        seats,
        room_claims: events.room_claimed,
      });
    });

    socket.on("ludo.tournament.room_claim_failed", (payload) => {
      if (finished) {
        return;
      }
      finished = true;
      rejectDone(new Error(`Claim failed: ${JSON.stringify(payload)}`));
    });

    socket.on("ludo.error", (payload) => {
      if (finished) {
        return;
      }
      finished = true;
      rejectDone(new Error(`Socket error: ${JSON.stringify(payload)}`));
    });

    socket.on("connect_error", (error) => {
      if (finished) {
        return;
      }
      finished = true;
      rejectDone(error);
    });
  }

  try {
    const result = await done;
    console.log(JSON.stringify(result, null, 2));
  } finally {
    closeAll();
  }
}

main().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
