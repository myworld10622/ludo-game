#!/usr/bin/env bash
# Run all Maestro tests on Device B (joiner / Player B)
# Usage: ./run_device_b.sh [device-id]
# Run this AFTER Device A has created the room and you know the PRIVATE_ROOM_CODE.

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

echo "=== Running Device B (Joiner) suite ==="
maestro $DEVICE_FLAG test \
  --env PLAYER_B_MOBILE="$PLAYER_B_MOBILE" \
  --env PRIVATE_ROOM_CODE="$PRIVATE_ROOM_CODE" \
  --format junit \
  --output test-results/device_b_results.xml \
  "$(dirname "$0")/flows/00_full_multiplayer_suite_b.yaml"

echo "=== Done. Results in tests/maestro/test-results/device_b_results.xml ==="
