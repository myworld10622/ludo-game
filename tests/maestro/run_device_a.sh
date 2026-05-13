#!/usr/bin/env bash
# Run all Maestro tests on Device A (host / Player A)
# Usage: ./run_device_a.sh [device-id]
#
# Prerequisites:
#   brew install maestro          # macOS
#   choco install maestro-cli     # Windows (via Chocolatey)
#   export ANDROID_HOME=...       # or set in .bashrc
#
# For physical devices: enable USB debugging, connect via USB
# For emulators: start with  emulator -avd Pixel_6_API_34

set -e

DEVICE="${1:-}"
ENV_FILE="$(dirname "$0")/.env"

if [ ! -f "$ENV_FILE" ]; then
  echo "ERROR: $ENV_FILE not found. Copy .env.example to .env and fill in values."
  exit 1
fi

set -a; source "$ENV_FILE"; set +a

DEVICE_FLAG=""
if [ -n "$DEVICE" ]; then
  DEVICE_FLAG="--device $DEVICE"
fi

APK_PATH="${APK_PATH:-E:/New Rox APK/ROX 2.1/ROX_LUDO.apk}"

echo "=== Installing APK (if needed) ==="
if [ -f "$APK_PATH" ]; then
  adb ${DEVICE:+-s "$DEVICE"} install -r "$APK_PATH" && echo "APK installed" || echo "APK install skipped (already up to date)"
fi

echo "=== Running Device A (Host) suite ==="
maestro $DEVICE_FLAG test \
  --env PLAYER_A_MOBILE="$PLAYER_A_MOBILE" \
  --env PRIVATE_ROOM_CODE="$PRIVATE_ROOM_CODE" \
  --format junit \
  --output test-results/device_a_results.xml \
  "$(dirname "$0")/flows/00_full_multiplayer_suite.yaml"

echo "=== Done. Results in tests/maestro/test-results/device_a_results.xml ==="
