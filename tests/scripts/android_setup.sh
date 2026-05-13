#!/usr/bin/env bash
# android_setup.sh — Create and boot an Android emulator for Appium tests
# Usage: bash tests/scripts/android_setup.sh
set -e

AVD_NAME="${AVD_NAME:-Pixel5_API33}"
API_LEVEL="${API_LEVEL:-33}"
ABI="${ABI:-x86_64}"
SYSTEM_IMAGE="system-images;android-${API_LEVEL};google_apis;${ABI}"

echo "═══════════════════════════════════════"
echo " Android Emulator Setup"
echo "═══════════════════════════════════════"
echo "  AVD: $AVD_NAME  API: $API_LEVEL  ABI: $ABI"

# Check sdkmanager
if ! command -v sdkmanager &>/dev/null; then
  echo "ERROR: sdkmanager not found. Install Android Studio or command-line tools."
  echo "  https://developer.android.com/tools/sdkmanager"
  exit 1
fi

# Accept licenses
echo "" | sdkmanager --licenses > /dev/null 2>&1 || true

# Install system image
echo ""
echo "▶ Installing system image: $SYSTEM_IMAGE"
sdkmanager "$SYSTEM_IMAGE"

# Create AVD
echo ""
echo "▶ Creating AVD '$AVD_NAME'..."
echo "no" | avdmanager create avd \
  -n "$AVD_NAME" \
  -k "$SYSTEM_IMAGE" \
  --device "pixel_5" \
  --force 2>/dev/null || echo "  (AVD may already exist)"

# List AVDs
echo ""
echo "Available AVDs:"
avdmanager list avd | grep -E "Name:|Path:" | head -20

echo ""
echo "▶ Starting emulator in background..."
nohup emulator -avd "$AVD_NAME" \
  -no-snapshot-save \
  -no-audio \
  -no-window \
  -gpu swiftshader_indirect \
  > /tmp/emulator.log 2>&1 &

EMULATOR_PID=$!
echo "  Emulator PID: $EMULATOR_PID"

echo "▶ Waiting for emulator to be ready (up to 120s)..."
adb wait-for-device
TIMEOUT=120
ELAPSED=0
while [ $ELAPSED -lt $TIMEOUT ]; do
  BOOT=$(adb shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')
  if [ "$BOOT" = "1" ]; then
    break
  fi
  sleep 5
  ELAPSED=$((ELAPSED + 5))
  echo "  Waiting... ${ELAPSED}s"
done

if [ "$BOOT" = "1" ]; then
  echo "✓ Emulator booted successfully."
  echo ""
  echo "Connected devices:"
  adb devices
  echo ""
  echo "You can now run: npm run appium:test"
else
  echo "ERROR: Emulator did not boot within ${TIMEOUT}s."
  echo "Check /tmp/emulator.log for details."
  exit 1
fi
