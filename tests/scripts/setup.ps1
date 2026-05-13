# setup.ps1 — One-shot setup for Windows (PowerShell)
# Run from the project root:
#   cd d:\Live-Code\Live-Rox-Ludo\games\tests
#   .\scripts\setup.ps1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location (Join-Path $ScriptDir "..")

Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan
Write-Host " Ludo V2 Test Suite — Windows Setup"    -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan

# ── Node.js dependencies ──────────────────────────────────────────────────
Write-Host "`n▶ Installing Node.js dependencies..." -ForegroundColor Yellow
npm install
if ($LASTEXITCODE -ne 0) { Write-Warning "npm install had errors" }

Write-Host "`n▶ Installing Playwright..." -ForegroundColor Yellow
npx playwright install chromium 2>$null
if ($LASTEXITCODE -ne 0) { Write-Warning "Playwright install had errors" }

# ── Python / Locust ───────────────────────────────────────────────────────
Write-Host "`n▶ Checking Python..." -ForegroundColor Yellow
$py = $null
if (Get-Command python -ErrorAction SilentlyContinue) { $py = "python" }
elseif (Get-Command python3 -ErrorAction SilentlyContinue) { $py = "python3" }

if ($py) {
  Write-Host "  Python found: $py"
  Write-Host "▶ Installing Locust + socket.io client..." -ForegroundColor Yellow
  & $py -m pip install --quiet locust "python-socketio[client]" gevent
  if ($LASTEXITCODE -ne 0) {
    Write-Warning "pip install failed. Run manually: pip install locust python-socketio[client] gevent"
  }
} else {
  Write-Warning "Python not found — Locust tests unavailable. Install from https://python.org"
}

# ── Appium ────────────────────────────────────────────────────────────────
Write-Host "`n▶ Installing Appium 2 globally..." -ForegroundColor Yellow
npm install -g appium
if ($LASTEXITCODE -eq 0) {
  Write-Host "▶ Installing UiAutomator2 driver..." -ForegroundColor Yellow
  appium driver install uiautomator2
} else {
  Write-Warning "Appium install failed"
}

# ── Android SDK / AVD ─────────────────────────────────────────────────────
Write-Host "`n▶ Checking Android SDK..." -ForegroundColor Yellow
if (Get-Command adb -ErrorAction SilentlyContinue) {
  Write-Host "  adb found" -ForegroundColor Green
  adb devices
} else {
  Write-Warning "adb not found in PATH. Add Android SDK platform-tools to your PATH."
  Write-Host "  Download Android Studio: https://developer.android.com/studio"
}

# Check/create AVD
if (Get-Command avdmanager -ErrorAction SilentlyContinue) {
  $avds = & avdmanager list avd 2>&1 | Select-String "Name:"
  if ($avds) {
    Write-Host "  Existing AVDs:`n$avds" -ForegroundColor Green
  } else {
    Write-Host "  No AVD found. Creating Pixel 5 / API 33..." -ForegroundColor Yellow
    Write-Host "  Run manually if sdkmanager is available:"
    Write-Host '  sdkmanager "system-images;android-33;google_apis;x86_64"'
    Write-Host '  avdmanager create avd -n "Pixel5_API33" -k "system-images;android-33;google_apis;x86_64" --device "pixel_5"'
  }
}

# ── .env ──────────────────────────────────────────────────────────────────
if (-not (Test-Path ".env")) {
  if (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env"
    Write-Host "`n▶ Created .env from .env.example" -ForegroundColor Green
    Write-Warning "Edit tests\.env with your LUDO_SERVER_URL before running tests."
  }
}

New-Item -ItemType Directory -Force "reports\playwright" | Out-Null
New-Item -ItemType Directory -Force "reports\appium"     | Out-Null

Write-Host "`n═══════════════════════════════════════" -ForegroundColor Cyan
Write-Host " Setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host " Commands:" -ForegroundColor White
Write-Host "   npm test                        Playwright protocol tests"
Write-Host "   npm run test:all                All specs + HTML report"
Write-Host "   npm run bot:run                 Automated 2P gameplay bots"
Write-Host "   node bots/BotOrchestrator.js --players 4 --games 5 --verbose"
Write-Host "   node utils/networkProxy.js --latency 200 --verbose"
Write-Host "   npm run locust:ui               Locust load test UI (http://localhost:8089)"
Write-Host "   npm run appium:test             Appium Android UI tests"
Write-Host ""
Write-Host " Android emulator start (once AVD is created):" -ForegroundColor Yellow
Write-Host '   emulator -avd Pixel5_API33 -no-snapshot-save'
Write-Host "═══════════════════════════════════════" -ForegroundColor Cyan
