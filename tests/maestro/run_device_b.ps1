# Run Maestro tests on Device B (Windows PowerShell)
param(
    [string]$Device = "",
    [string]$RoomCode = ""
)

$EnvFile = Join-Path $PSScriptRoot ".env"
if (-not (Test-Path $EnvFile)) {
    Write-Error "Missing .env file."
    exit 1
}

Get-Content $EnvFile | Where-Object { $_ -match "^[^#].*=.*" } | ForEach-Object {
    $k, $v = $_ -split "=", 2
    Set-Item -Path "Env:$($k.Trim())" -Value $v.Trim()
}

# Override room code if passed as parameter
if ($RoomCode) { $env:PRIVATE_ROOM_CODE = $RoomCode }

$DeviceFlag = if ($Device) { "--device $Device" } else { "" }

Write-Host "=== Running Device B suite ===" -ForegroundColor Green

$FlowFile = Join-Path $PSScriptRoot "flows\00_full_multiplayer_suite_b.yaml"
$OutputDir = Join-Path $PSScriptRoot "test-results"
New-Item -ItemType Directory -Force $OutputDir | Out-Null

Invoke-Expression "maestro $DeviceFlag test ``
    --env PLAYER_B_MOBILE=$env:PLAYER_B_MOBILE ``
    --env PRIVATE_ROOM_CODE=$env:PRIVATE_ROOM_CODE ``
    --format junit ``
    --output `"$OutputDir\device_b_results.xml`" ``
    `"$FlowFile`""

Write-Host "=== Done ===" -ForegroundColor Green
