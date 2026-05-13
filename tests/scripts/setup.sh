#!/usr/bin/env bash
# setup.sh — One-shot setup for Linux/macOS/WSL
# Usage: bash tests/scripts/setup.sh
set -e
cd "$(dirname "$0")/.."

echo "═══════════════════════════════════════"
echo " Ludo V2 Test Suite — Setup"
echo "═══════════════════════════════════════"

# ── Node.js dependencies ────────────────────────────────────────────────────
echo ""
echo "▶ Installing Node.js dependencies..."
npm install
npm install --save-dev @playwright/test

echo ""
echo "▶ Installing Playwright (no browser needed, using Node runner)..."
npx playwright install --with-deps chromium 2>/dev/null || true

# ── Python / Locust ─────────────────────────────────────────────────────────
echo ""
echo "▶ Checking Python..."
if command -v python3 &>/dev/null; then
  PY=python3
elif command -v python &>/dev/null; then
  PY=python
else
  echo "  [WARN] Python not found — Locust tests will be unavailable."
  PY=""
fi

if [ -n "$PY" ]; then
  echo "▶ Installing Locust and socket.io client..."
  $PY -m pip install --quiet locust "python-socketio[client]" gevent || \
    echo "  [WARN] pip install failed — run manually: pip install locust python-socketio[client] gevent"
fi

# ── Appium ───────────────────────────────────────────────────────────────────
echo ""
echo "▶ Installing Appium 2..."
npm install -g appium 2>/dev/null || echo "  [WARN] Appium global install failed — try: sudo npm install -g appium"
echo "▶ Installing UiAutomator2 driver..."
appium driver install uiautomator2 2>/dev/null || echo "  [WARN] Appium driver install failed"

# ── Android SDK check ────────────────────────────────────────────────────────
echo ""
echo "▶ Checking Android SDK..."
if command -v adb &>/dev/null; then
  echo "  adb found: $(adb version | head -1)"
  echo "  Connected devices:"
  adb devices
else
  echo "  [WARN] adb not found — Android SDK not in PATH."
  echo "  Install from: https://developer.android.com/studio"
fi

# ── .env ─────────────────────────────────────────────────────────────────────
if [ ! -f .env ]; then
  cp .env.example .env
  echo ""
  echo "▶ Created .env from .env.example"
  echo "  ⚠ Edit tests/.env with your LUDO_SERVER_URL before running tests."
fi

mkdir -p reports/playwright reports/appium

echo ""
echo "═══════════════════════════════════════"
echo " Setup complete!"
echo ""
echo " Commands:"
echo "   npm test                   — Playwright protocol tests"
echo "   npm run test:all           — All specs + HTML report"
echo "   npm run bot:run            — Automated 2P gameplay bots"
echo "   node bots/BotOrchestrator.js --players 4 --games 5 --verbose"
echo "   npm run proxy:start        — Network condition proxy"
echo "   npm run locust:ui          — Locust load test (open http://localhost:8089)"
echo "   npm run appium:test        — Appium Android UI tests"
echo "═══════════════════════════════════════"
