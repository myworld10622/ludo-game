# Ludo V2 — Automated Multiplayer Test Suite

End-to-end, protocol-level, load, and UI tests for the Ludo V2 Socket.IO game server.

---

## Folder Structure

```
tests/
├── package.json
├── playwright.config.js
├── .env.example                  ← copy to .env and configure
│
├── playwright/
│   ├── helpers/
│   │   ├── GameClient.js         ← Socket.IO client wrapper with waitFor / nonce tracking
│   │   ├── assertions.js         ← Domain assertions (kills, safe zones, sync, winner)
│   │   └── testRoom.js           ← createRoom / teardownRoom / playFullGame helpers
│   └── specs/
│       ├── 01_2player_game.spec.js     ← 2P connect, sync, full game
│       ├── 02_4player_game.spec.js     ← 4P seat assignment, sync, placements
│       ├── 03_turn_order.spec.js       ← Rotation, timeout, turn advance
│       ├── 04_kills_safezones.spec.js  ← Kill logic, safe squares, home column
│       ├── 05_three_sixes.spec.js      ← Forfeit on 3 consecutive sixes
│       ├── 06_extra_turn.spec.js       ← Extra turn on 6 / kill / entry
│       ├── 07_reconnect.spec.js        ← Disconnect, reconnect, board sync, nonce restore
│       ├── 08_network_conditions.spec.js ← Duplicate packets, stale nonce, rate limit, spoof
│       └── 09_settlement.spec.js       ← Auto-settle, winner token positions, placements
│
├── bots/
│   ├── GameBot.js                ← Single-player bot (random / optimal / scripted strategies)
│   └── BotOrchestrator.js        ← CLI: spawn N bots, run M games, print stats
│
├── appium/
│   ├── appium.config.js          ← WebdriverIO config
│   ├── capabilities.js           ← Android device capabilities
│   ├── pages/
│   │   ├── LobbyPage.js          ← Lobby screen page object
│   │   └── GamePage.js           ← Game board page object
│   └── specs/
│       ├── 01_matchmaking.spec.js
│       └── 02_gameplay.spec.js
│
├── locust/
│   ├── locustfile.py             ← Load test scenarios
│   └── ludo_client.py            ← Python socket.io client
│
├── utils/
│   └── networkProxy.js           ← TCP proxy for latency/drop/duplicate injection
│
├── scripts/
│   ├── setup.js                  ← Post-install .env + reports/ creation
│   ├── setup.sh                  ← Linux/macOS full setup
│   ├── setup.ps1                 ← Windows PowerShell full setup
│   └── android_setup.sh          ← Create + boot Android AVD
│
└── reports/
    ├── playwright/               ← HTML report (npm run report)
    └── appium/                   ← Allure report
```

---

## Quick Start

### 1 — Install

```bash
# Windows
cd d:\Live-Code\Live-Rox-Ludo\games\tests
.\scripts\setup.ps1

# Linux / macOS / WSL
cd tests/
bash scripts/setup.sh
```

### 2 — Configure

```bash
cp .env.example .env
# Edit .env:
#   LUDO_SERVER_URL=http://localhost:3002   (or your server IP)
#   MOCK_LARAVEL=1                          (skip DB calls in pure socket tests)
```

### 3 — Start the server

```bash
# From project root:
cd node && node server.js
```

### 4 — Run tests

```bash
# All Playwright specs
npm test

# Single spec
npm run test:2p
npm run test:reconnect
npm run test:kills

# All specs + open HTML report
npm run test:all && npm run report
```

---

## Test Suites

### Playwright (Socket.IO Protocol Tests)

| Script | What it tests |
|--------|--------------|
| `npm run test:2p` | 2-player game: connect, sync, full game, winner |
| `npm run test:4p` | 4-player game: seats, sync, placements |
| `npm run test:turns` | Turn order rotation, roll/move timeout |
| `npm run test:kills` | Kill logic, safe squares, home column immunity |
| `npm run test:threesixes` | 3-consecutive-sixes forfeit |
| `npm run test:extraturn` | Extra turn on 6 / kill / yard entry |
| `npm run test:reconnect` | Disconnect → reconnect → board state sync |
| `npm run test:network` | Duplicate packets, stale nonce, identity spoof, rate limit |
| `npm run test:settlement` | Auto-settle, winner detection, placements |

These tests require **only the Node.js server** — no database, no Unity client.
Set `MOCK_LARAVEL=1` in `.env` to skip Laravel sync calls.

### Bot Orchestrator

```bash
# 2-player game, 1 run
npm run bot:run

# 4-player, 5 games, verbose output, optimal strategy
node bots/BotOrchestrator.js --players 4 --games 5 --strategy optimal --verbose

# Random strategy, 10 games
node bots/BotOrchestrator.js --players 2 --games 10 --strategy random
```

Output includes win distribution by seat and average turns per game.

### Network Proxy (Latency / Packet Loss)

```bash
# Start proxy with 200ms latency + 10% packet drop
node utils/networkProxy.js --port 3099 --target 3002 --latency 200 --drop 0.10 --verbose

# In .env, set LUDO_SERVER_URL=http://localhost:3099
# Then run tests — all traffic flows through the proxy
npm test

# Or use programmatically in a test:
const { NetworkProxy } = require('./utils/networkProxy');
const proxy = new NetworkProxy({ proxyPort: 3099 });
await proxy.start();
proxy.simulateLag(200, 50);
// ... run tests ...
proxy.clearConditions();
await proxy.stop();
```

### Locust Load Tests

```bash
# Install Python deps (once)
pip install locust "python-socketio[client]" gevent

# Headless: 20 users, 2/s ramp, 60s run
npm run locust:run

# Interactive UI at http://localhost:8089
npm run locust:ui

# Custom run
cd locust && locust -f locustfile.py \
  --headless -u 50 -r 5 --run-time 120s \
  --host http://localhost:3002

# Run specific user class
locust -f locustfile.py LudoReconnectUser --headless -u 10 -r 1 --run-time 30s
```

Metrics reported:
- `socket_connect` — WebSocket connection latency
- `matchmaking_latency` — time from join_queue to room.starting
- `first_turn_latency` — time from room.starting to first turn_started
- `turn_action_latency` — time from roll_dice to token_moved
- `full_game_latency` — total game duration
- `reconnect_state_latency` — time from reconnect to game.state received

### Appium (Android UI Tests)

#### Prerequisites

1. Install Android Studio with SDK platform-tools
2. Add to PATH: `%LOCALAPPDATA%\Android\Sdk\platform-tools`
3. Start an emulator or plug in a physical device

```bash
# Auto-create and start Pixel 5 emulator (Linux/WSL)
bash scripts/android_setup.sh

# Or start manually (Windows)
%LOCALAPPDATA%\Android\Sdk\emulator\emulator.exe -avd Pixel5_API33
```

4. Start Appium server

```bash
appium --port 4723
```

5. Set APK path in `.env`:
```
ANDROID_APK_PATH=../build/ludo.apk
```

6. Run tests

```bash
npm run appium:test
```

#### AVD Setup (Windows Manual)

```powershell
# Install system image
sdkmanager "system-images;android-33;google_apis;x86_64"

# Create AVD
avdmanager create avd -n "Pixel5_API33" `
  -k "system-images;android-33;google_apis;x86_64" `
  --device "pixel_5"

# Start emulator
$env:LOCALAPPDATA\Android\Sdk\emulator\emulator.exe -avd Pixel5_API33 -no-snapshot-save
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `LUDO_SERVER_URL` | `http://localhost:3002` | Server to test against |
| `LUDO_NAMESPACE` | `/ludo_v2` | Socket.IO namespace |
| `TEST_USER_1_ID` | `10001` | First test user ID |
| `TEST_USER_2_ID` | `10002` | Second test user ID |
| `TEST_USER_3_ID` | `10003` | Third test user ID |
| `TEST_USER_4_ID` | `10004` | Fourth test user ID |
| `MOCK_LARAVEL` | `1` | Skip Laravel sync (pure socket test) |
| `PROXY_PORT` | `3099` | Port for NetworkProxy |
| `CONNECT_TIMEOUT` | `8000` | WebSocket connect timeout (ms) |
| `GAME_START_TIMEOUT` | `15000` | Time to wait for room.starting (ms) |
| `TURN_TIMEOUT` | `20000` | Time to wait for next event (ms) |
| `APPIUM_SERVER_URL` | `http://localhost:4723` | Appium server |
| `ANDROID_APK_PATH` | `../build/ludo.apk` | Path to Unity APK |
| `ANDROID_DEVICE_NAME` | `emulator-5554` | Device identifier |

---

## What Each Test Validates

| Test | Validates |
|------|-----------|
| 2-player game | Connect, starting, sync, winner, placements |
| 4-player game | 4-seat assignment, player starts (0/13/26/39), 4-way sync |
| Turn order | 0→1→2→3 rotation, roll timeout, move timeout, turn advance |
| Kills + safe zones | kill → extra turn, killed token = -1, 8 safe squares, home col immunity |
| Three sixes | Token forfeited to -1, no extra turn, turn advances |
| Extra turn | Six → extra turn + same seat, kill → extra turn, entry from yard |
| Reconnect | Board sync on reconnect, nonce restored, reconnected player can act |
| Network conditions | Duplicate packets rejected, stale nonce rejected, rate limit, identity spoof, room spoof, fake settlement blocked |
| Settlement | Auto-settle on win, token positions at 56, one RESULT, synchronized winner |

---

## CI Integration

```yaml
# .github/workflows/ludo-tests.yml
- name: Run Ludo protocol tests
  working-directory: tests
  run: |
    npm ci
    npm test
  env:
    LUDO_SERVER_URL: http://localhost:3002
    MOCK_LARAVEL: 1
```
