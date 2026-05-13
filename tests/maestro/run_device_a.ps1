# Run Maestro tests on Device A (Windows PowerShell)
# Usage: .\run_device_a.ps1 [-Device "emulator-5554"] [-ApkPath "E:\New Rox APK\ROX 2.1\ROX_LUDO.apk"]

param(
    [string]$Device = "",
    [string]$ApkPath = "E:\New Rox APK\ROX 2.1\ROX_LUDO.apk"
)

$EnvFile = Join-Path $PSScriptRoot ".env"
if (-not (Test-Path $EnvFile)) {
    Write-Error "Missing .env file. Copy .env.example to .env and fill in values."
    exit 1
}

# Load .env variables
Get-Content $EnvFile | Where-Object { $_ -match "^[^#].*=.*" } | ForEach-Object {
    $k, $v = $_ -split "=", 2
    Set-Item -Path "Env:$($k.Trim())" -Value $v.Trim()
}

$DeviceFlag = if ($Device) { "--device $Device" } else { "" }

# Install APK
if (Test-Path $ApkPath) {
    Write-Host "=== Installing APK ===" -ForegroundColor Cyan
    $adbArgs = if ($Device) { "-s $Device install -r `"$ApkPath`"" } else { "install -r `"$ApkPath`"" }
    Invoke-Expression "adb $adbArgs"
}

Write-Host "=== Running Device A suite ===" -ForegroundColor Green

$FlowFile = Join-Path $PSScriptRoot "flows\00_full_multiplayer_suite.yaml"
$OutputDir = Join-Path $PSScriptRoot "test-results"
New-Item -ItemType Directory -Force $OutputDir | Out-Null

Invoke-Expression "maestro $DeviceFlag test ``
    --env PLAYER_A_MOBILE=$env:PLAYER_A_MOBILE ``
    --env PRIVATE_ROOM_CODE=$env:PRIVATE_ROOM_CODE ``
    --format junit ``
    --output `"$OutputDir\device_a_results.xml`" ``
    `"$FlowFile`""

Write-Host "=== Done ===" -ForegroundColor Green
