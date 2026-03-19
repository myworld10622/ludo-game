require("dotenv").config({ path: require("path").resolve(__dirname, "..", ".env") });

const { io } = require("socket.io-client");
const mysql = require("mysql2/promise");

const LARAVEL_BASE_URL = process.env.LUDO_TEST_LARAVEL_BASE_URL || "http://127.0.0.1:8000";
const SOCKET_BASE_URL = process.env.LUDO_TEST_SOCKET_URL || "http://127.0.0.1:3002";
const SOCKET_NAMESPACE = process.env.LUDO_TEST_SOCKET_NAMESPACE || "/ludo_v2";
const TEST_USER_ID = Number(process.env.LUDO_TEST_USER_ID || "4");
const TEST_USER_NAME = process.env.LUDO_TEST_USER_NAME || "Betzono";
const TEST_USER_TOKEN = process.env.LUDO_TEST_TOKEN || "";
const TEST_MODE = (process.env.LUDO_TEST_MODE || "cash").toLowerCase();
const ENTRY_FEE = Number(process.env.LUDO_TEST_ENTRY_FEE || "50");
const MAX_PLAYERS = Number(process.env.LUDO_TEST_MAX_PLAYERS || "4");
const GAME_MODE = process.env.LUDO_TEST_GAME_MODE || "classic";
const ALLOW_BOTS = (process.env.LUDO_TEST_ALLOW_BOTS || "true").toLowerCase() === "true";
const COMPLETE_AS_WINNER = (process.env.LUDO_TEST_COMPLETE_AS_WINNER || "true").toLowerCase() === "true";
const BOT_WAIT_BUFFER_MS = Number(process.env.LUDO_TEST_WAIT_MS || "30000");
const DB_CONFIG = {
  host: process.env.DB_HOST || "127.0.0.1",
  port: Number(process.env.DB_PORT || "3306"),
  user: process.env.DB_USERNAME || "root",
  password: process.env.DB_PASSWORD || "",
  database: process.env.DB_DATABASE || "games_backend",
};

let db;
let socket;
let roomId = null;
let latestSnapshot = null;
let latestStart = null;
let completed = false;

function log(step, payload) {
  const at = new Date().toISOString();
  console.log(`[${at}] ${step}`);
  if (payload !== undefined) {
    console.log(JSON.stringify(payload, null, 2));
  }
}

async function fetchJson(url, options = {}) {
  const response = await fetch(url, options);
  const text = await response.text();

  let json = null;
  try {
    json = text ? JSON.parse(text) : null;
  } catch (error) {
    json = { raw: text };
  }

  if (!response.ok) {
    throw new Error(`HTTP ${response.status} for ${url}: ${JSON.stringify(json)}`);
  }

  return json;
}

async function ensureFunds() {
  if (TEST_MODE !== "cash") {
    return;
  }

  await db.execute(
    "UPDATE wallets SET balance = GREATEST(balance, ?) WHERE user_id = ?",
    [Math.max(ENTRY_FEE * 5, 500), TEST_USER_ID]
  );
}

async function getWalletSnapshot() {
  const [rows] = await db.execute(
    "SELECT user_id, balance, currency FROM wallets WHERE user_id = ?",
    [TEST_USER_ID]
  );

  return rows[0] || null;
}

async function getRecentTransactions() {
  const [rows] = await db.execute(
    `SELECT id, transaction_uuid, type, direction, status, amount, balance_before, balance_after, reference_type, reference_id, description
     FROM wallet_transactions
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 10`,
    [TEST_USER_ID]
  );

  return rows;
}

async function getLatestRoom() {
  const [rows] = await db.execute(
    `SELECT id, room_uuid, status, entry_fee, current_players, current_real_players, current_bot_players, created_at, started_at, completed_at
     FROM game_rooms
     WHERE room_uuid = ?
     LIMIT 1`,
    [roomId]
  );

  return rows[0] || null;
}

async function getLatestMatch() {
  const [rows] = await db.execute(
    `SELECT id, match_uuid, game_room_id, status, winner_user_id, prize_pool, started_at, completed_at
     FROM game_matches
     WHERE game_room_id = (SELECT id FROM game_rooms WHERE room_uuid = ? LIMIT 1)
     ORDER BY id DESC
     LIMIT 1`,
    [roomId]
  );

  return rows[0] || null;
}

async function getLatestMatchPlayers(matchId) {
  const [rows] = await db.execute(
    `SELECT seat_no, user_id, player_type, status, is_winner, payout_amount, finish_position, score
     FROM game_match_players
     WHERE game_match_id = ?
     ORDER BY seat_no`,
    [matchId]
  );

  return rows;
}

async function joinQueue() {
  if (!TEST_USER_TOKEN) {
    throw new Error(
      "Missing LUDO_TEST_TOKEN. Set a valid Sanctum token for the test user before running this script."
    );
  }

  const payload = {
    room_type: "public",
    play_mode: TEST_MODE === "cash" ? "cash" : "practice",
    game_mode: GAME_MODE,
    max_players: MAX_PLAYERS,
    entry_fee: TEST_MODE === "cash" ? ENTRY_FEE : 0,
    allow_bots: ALLOW_BOTS,
  };

  const response = await fetchJson(`${LARAVEL_BASE_URL}/api/v1/ludo/queue/join`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${TEST_USER_TOKEN}`,
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });

  log("queue.join.response", response);
  roomId = response?.data?.room_uuid || null;
  if (!roomId) {
    throw new Error("Queue join did not return a room_uuid.");
  }
}

function buildCompletionPayload() {
  const seats = latestStart?.seats || latestSnapshot?.seats || [];
  const orderedSeats = [...seats].sort((a, b) => a.seatNo - b.seatNo);
  const winnerSeat = COMPLETE_AS_WINNER ? orderedSeats[0] : orderedSeats[orderedSeats.length - 1];

  const placements = orderedSeats.map((seat, index) => {
    const isWinner = seat.seatNo === winnerSeat.seatNo;
    return {
      seat_no: seat.seatNo,
      finish_position: isWinner ? 1 : index + 2,
      score: isWinner ? 1 : 0,
      is_winner: isWinner,
      payout_amount: isWinner && seat.playerType === "human" ? ENTRY_FEE * MAX_PLAYERS : 0,
      stats: {
        username: seat.displayName,
        player_type: seat.playerType,
        bot_code: seat.botCode || null,
      },
    };
  });

  return {
    winner: {
      seat_no: winnerSeat.seatNo,
      user_id: winnerSeat.playerType === "human" ? winnerSeat.userId : null,
    },
    placements,
  };
}

async function connectAndRunLifecycle() {
  await new Promise((resolve, reject) => {
    let timeout = setTimeout(() => {
      reject(new Error("Timed out waiting for Ludo v2 lifecycle to complete."));
    }, BOT_WAIT_BUFFER_MS);

    socket = io(`${SOCKET_BASE_URL}${SOCKET_NAMESPACE}`, {
      transports: ["websocket", "polling"],
      reconnection: false,
      timeout: 10000,
    });

    socket.on("connect", () => {
      log("socket.connect", { id: socket.id });
      socket.emit("ludo.queue.join", {
        userId: TEST_USER_ID,
        displayName: TEST_USER_NAME,
        roomUuid: roomId,
        roomType: "public",
        playMode: TEST_MODE === "cash" ? "cash" : "practice",
        gameMode: GAME_MODE,
        maxPlayers: MAX_PLAYERS,
        entryFee: TEST_MODE === "cash" ? ENTRY_FEE : 0,
        allowBots: ALLOW_BOTS,
      });
    });

    socket.on("ludo.room.waiting", (payload) => {
      latestSnapshot = payload;
      log("ludo.room.waiting", payload);
    });

    socket.on("ludo.room.bot_joined", (payload) => {
      log("ludo.room.bot_joined", payload);
    });

    socket.on("ludo.game.snapshot", (payload) => {
      latestSnapshot = payload;
      log("ludo.game.snapshot", payload);
    });

    socket.on("ludo.room.starting", (payload) => {
      latestStart = payload;
      log("ludo.room.starting", payload);

      const completionPayload = buildCompletionPayload();
      log("ludo.match.complete.emit", completionPayload);
      socket.emit("ludo.match.complete", completionPayload);
    });

    socket.on("ludo.game.result", (payload) => {
      completed = true;
      log("ludo.game.result", payload);
      clearTimeout(timeout);
      resolve();
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
}

async function printVerification() {
  const wallet = await getWalletSnapshot();
  const transactions = await getRecentTransactions();
  const room = await getLatestRoom();
  const match = await getLatestMatch();
  const matchPlayers = match ? await getLatestMatchPlayers(match.id) : [];

  log("verification.wallet", wallet);
  log("verification.transactions", transactions);
  log("verification.room", room);
  log("verification.match", match);
  log("verification.match_players", matchPlayers);
}

async function main() {
  db = await mysql.createConnection(DB_CONFIG);

  log("config", {
    LARAVEL_BASE_URL,
    SOCKET_BASE_URL,
    SOCKET_NAMESPACE,
    TEST_USER_ID,
    TEST_USER_NAME,
    TEST_MODE,
    ENTRY_FEE,
    MAX_PLAYERS,
    GAME_MODE,
    ALLOW_BOTS,
    COMPLETE_AS_WINNER,
  });

  await ensureFunds();
  log("before.wallet", await getWalletSnapshot());
  log("before.transactions", await getRecentTransactions());

  await joinQueue();
  await connectAndRunLifecycle();
  await new Promise((resolve) => setTimeout(resolve, 2000));
  await printVerification();

  if (!completed) {
    throw new Error("Ludo result event was not received.");
  }
}

main()
  .then(async () => {
    if (socket) {
      socket.close();
    }
    if (db) {
      await db.end();
    }
    process.exit(0);
  })
  .catch(async (error) => {
    console.error(error.stack || error.message);
    if (socket) {
      socket.close();
    }
    if (db) {
      await db.end();
    }
    process.exit(1);
  });
