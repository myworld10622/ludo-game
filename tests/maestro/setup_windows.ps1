# =============================================================================
# Rox Ludo — Maestro Full Setup Script (Windows)
# Run as Administrator in PowerShell:
#   Set-ExecutionPolicy Bypass -Scope Process -Force
#   .\tests\maestro\setup_windows.ps1
# =============================================================================

$ErrorActionPreference = "Stop"

# ── Paths ─────────────────────────────────────────────────────────────────────
$ANDROID_SDK   = "$env:LOCALAPPDATA\Android\Sdk"
$JAVA_HOME     = "C:\Program Files\Unity\Hub\Editor\6000.0.37f1\Editor\Data\PlaybackEngines\AndroidPlayer\OpenJDK"
$PLATFORM_TOOLS = "$ANDROID_SDK\platform-tools"
$EMULATOR_DIR   = "$ANDROID_SDK\emulator"
$CMDLINE_DIR    = "$ANDROID_SDK\cmdline-tools\latest"
$MAESTRO_DIR    = "$env:USERPROFILE\.maestro\bin"

function Log($msg) { Write-Host "`n=== $msg ===" -ForegroundColor Cyan }
function OK($msg)  { Write-Host "  ✓ $msg" -ForegroundColor Green }
function ERR($msg) { Write-Host "  ✗ $msg" -ForegroundColor Red }

# ── Step 1: Set permanent environment variables ───────────────────────────────
Log "Setting environment variables"

[Environment]::SetEnvironmentVariable("JAVA_HOME",        $JAVA_HOME,     "User")
[Environment]::SetEnvironmentVariable("ANDROID_HOME",     $ANDROID_SDK,   "User")
[Environment]::SetEnvironmentVariable("ANDROID_SDK_ROOT", $ANDROID_SDK,   "User")

$currentPath = [Environment]::GetEnvironmentVariable("PATH", "User")
$additions = @(
    "$JAVA_HOME\bin",
    $PLATFORM_TOOLS,
    $EMULATOR_DIR,
    "$CMDLINE_DIR\bin",
    $MAESTRO_DIR
)
foreach ($p in $additions) {
    if ($currentPath -notlike "*$p*") {
        $currentPath = "$currentPath;$p"
    }
}
[Environment]::SetEnvironmentVariable("PATH", $currentPath, "User")

# Apply to current session
$env:JAVA_HOME        = $JAVA_HOME
$env:ANDROID_HOME     = $ANDROID_SDK
$env:ANDROID_SDK_ROOT = $ANDROID_SDK
$env:PATH = "$env:PATH;$JAVA_HOME\bin;$PLATFORM_TOOLS;$EMULATOR_DIR;$CMDLINE_DIR\bin;$MAESTRO_DIR"

OK "JAVA_HOME = $JAVA_HOME"
OK "ANDROID_HOME = $ANDROID_SDK"

# ── Step 2: Verify Java ───────────────────────────────────────────────────────
Log "Verifying Java"
try {
    $jv = & "$JAVA_HOME\bin\java.exe" -version 2>&1
    OK "Java: $($jv | Select-Object -First 1)"
} catch {
    ERR "Java not found at $JAVA_HOME"
    exit 1
}

# ── Step 3: Verify ADB ───────────────────────────────────────────────────────
Log "Verifying ADB"
$adbExe = "$PLATFORM_TOOLS\adb.exe"
if (Test-Path $adbExe) {
    $av = & $adbExe version | Select-Object -First 1
    OK "ADB: $av"
} else {
    ERR "adb.exe not found at $PLATFORM_TOOLS"
    exit 1
}

# ── Step 4: Download cmdline-tools (needed for avdmanager / sdkmanager) ───────
Log "Installing Android cmdline-tools"

$cmdlineZip  = "$env:TEMP\cmdline-tools.zip"
$cmdlineUrl  = "https://dl.google.com/android/repository/commandlinetools-win-13114758_latest.zip"
$cmdlineExtract = "$env:TEMP\cmdline-tools-extract"

if (-not (Test-Path "$CMDLINE_DIR\bin\avdmanager.bat")) {
    Write-Host "  Downloading cmdline-tools (~140 MB)..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri $cmdlineUrl -OutFile $cmdlineZip -UseBasicParsing

    if (Test-Path $cmdlineExtract) { Remove-Item $cmdlineExtract -Recurse -Force }
    Expand-Archive -Path $cmdlineZip -DestinationPath $cmdlineExtract -Force

    # Google zips as cmdline-tools/bin — we need to place it at latest/
    $extracted = "$cmdlineExtract\cmdline-tools"
    New-Item -ItemType Directory -Force "$ANDROID_SDK\cmdline-tools\latest" | Out-Null
    Copy-Item "$extracted\*" "$ANDROID_SDK\cmdline-tools\latest\" -Recurse -Force

    Remove-Item $cmdlineZip -Force
    Remove-Item $cmdlineExtract -Recurse -Force

    OK "cmdline-tools installed"
} else {
    OK "cmdline-tools already present"
}

$avdmanager = "$CMDLINE_DIR\bin\avdmanager.bat"
$sdkmanager = "$CMDLINE_DIR\bin\sdkmanager.bat"

# ── Step 5: Accept licenses & install system image ────────────────────────────
Log "Accepting SDK licenses"
$env:JAVA_HOME = $JAVA_HOME
echo "y`ny`ny`ny`ny`ny`ny`n" | & $sdkmanager --licenses 2>&1 | Out-Null
OK "Licenses accepted"

Log "Installing system image for emulator (API 34, x86_64)"
Write-Host "  This may take 5-10 minutes..." -ForegroundColor Yellow
echo "y" | & $sdkmanager "system-images;android-34;google_apis;x86_64" 2>&1
OK "System image installed"

# ── Step 6: Create AVD ────────────────────────────────────────────────────────
Log "Creating AVD: Pixel6_API34"

$existingAvds = & $avdmanager list avd 2>&1
if ($existingAvds -notlike "*Pixel6_API34*") {
    echo "no" | & $avdmanager create avd `
        --name "Pixel6_API34" `
        --package "system-images;android-34;google_apis;x86_64" `
        --device "pixel_6" `
        --force
    OK "AVD Pixel6_API34 created"
} else {
    OK "AVD Pixel6_API34 already exists"
}

# ── Step 7: Install Maestro ───────────────────────────────────────────────────
Log "Installing Maestro"

$maestroExe = "$MAESTRO_DIR\maestro.bat"
if (-not (Test-Path $maestroExe)) {
    Write-Host "  Downloading Maestro installer..." -ForegroundColor Yellow

    # Maestro Windows installer
    $installerScript = "$env:TEMP\maestro_install.ps1"
    Invoke-WebRequest -Uri "https://get.maestro.mobile.dev" -OutFile $installerScript -UseBasicParsing

    # Run installer
    & powershell -ExecutionPolicy Bypass -File $installerScript

    OK "Maestro installed"
} else {
    OK "Maestro already installed at $maestroExe"
}

# Verify Maestro
try {
    $mv = & "$MAESTRO_DIR\maestro.bat" --version 2>&1 | Select-Object -First 1
    OK "Maestro version: $mv"
} catch {
    Write-Host "  Maestro installed but needs new terminal to activate PATH." -ForegroundColor Yellow
}

# ── Step 8: Setup .env ────────────────────────────────────────────────────────
Log "Setting up test .env"

$envFile = Join-Path $PSScriptRoot ".env"
$envExample = Join-Path $PSScriptRoot ".env.example"

if (-not (Test-Path $envFile)) {
    Copy-Item $envExample $envFile
    OK "Created .env from example — edit it with your player mobile numbers"
} else {
    OK ".env already exists"
}

# ── Summary ───────────────────────────────────────────────────────────────────
Write-Host "`n============================================" -ForegroundColor Green
Write-Host " SETUP COMPLETE" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host @"

Next steps:
1. Close this terminal and open a NEW PowerShell (PATH needs reload)
2. Edit tests\maestro\.env — set PLAYER_A_MOBILE and PLAYER_B_MOBILE
3. Connect your Android device via USB (USB Debugging ON)
   OR start emulator:
   emulator -avd Pixel6_API34 -no-snapshot-load
4. Verify device visible:
   adb devices
5. Run tests:
   cd d:\Live-Code\Live-Rox-Ludo\games
   .\tests\maestro\run_device_a.ps1

"@ -ForegroundColor White
