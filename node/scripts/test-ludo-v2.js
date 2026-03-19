const { io } = require("socket.io-client");

const SERVER_URL = process.env.LUDO_V2_TEST_URL || "http://127.0.0.1:3002/ludo_v2";
const WAIT_MS = Number(process.env.LUDO_V2_WAIT_MS || 12000);

const socket = io(SERVER_URL, {
  transports: ["websocket", "polling"],
  reconnection: false,
  timeout: 5000,
});

const received = [];

function logEvent(name, payload) {
  const entry = {
    event: name,
    payload,
    at: new Date().toISOString(),
  };

  received.push(entry);
  console.log(`[${entry.at}] ${name}`);
  if (payload !== undefined) {
    console.log(JSON.stringify(payload, null, 2));
  }
}

socket.on("connect", () => {
  logEvent("connect", { id: socket.id });

  socket.emit("ludo.queue.join", {
    userId: 999001,
    displayName: "Smoke Test Player",
    roomType: "public",
    playMode: "practice",
    gameMode: "classic",
    maxPlayers: 4,
    entryFee: 0,
    allowBots: true,
  });
});

[
  "ludo.room.waiting",
  "ludo.room.player_joined",
  "ludo.room.bot_joined",
  "ludo.room.countdown",
  "ludo.room.starting",
  "ludo.game.snapshot",
  "ludo.error",
  "disconnect",
  "connect_error",
].forEach((eventName) => {
  socket.on(eventName, (payload) => logEvent(eventName, payload));
});

setTimeout(() => {
  console.log("=== SUMMARY ===");
  console.log(JSON.stringify(received, null, 2));
  socket.close();
  process.exit(0);
}, WAIT_MS);
