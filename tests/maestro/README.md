# Maestro Multiplayer Test Suite — Rox Ludo

End-to-end APK tests for the Ludo multiplayer gameplay using [Maestro](https://maestro.mobile.dev).

## Folder Structure

```
tests/maestro/
├── flows/
│   ├── 00_full_multiplayer_suite.yaml    # Master suite — Device A (host)
│   ├── 00_full_multiplayer_suite_b.yaml  # Master suite — Device B (joiner)
│   ├── 01_login.yaml
│   ├── 02_private_room_host.yaml
│   ├── 03_private_room_join.yaml
│   ├── 04_turn_and_dice_validation.yaml
│   ├── 05_move_validation.yaml
│   ├── 06_reconnect_test.yaml
│   ├── 07_airplane_mode_test.yaml
│   ├── 08_minimize_restore_test.yaml
│   ├── 09_voice_chat_test.yaml
│   ├── 10_screen_rotation_test.yaml
│   └── 11_stuck_turn_detection.yaml
├── utils/
│   ├── AccessibilityIds.cs               # Per-component accessibility ID setter
│   └── UnityAccessibilityBridge.cs       # Central ID registration at startup
├── .env.example                          # Copy to .env and fill in
├── run_device_a.ps1                      # Windows: run Device A suite
├── run_device_b.ps1                      # Windows: run Device B suite
├── run_device_a.sh                       # macOS/Linux: run Device A suite
└── run_device_b.sh                       # macOS/Linux: run Device B suite
```

## Install Maestro

```powershell
# Windows (PowerShell — requires Java 11+)
iex "$(iwr 'https://get.maestro.mobile.dev' -UseBasicParsing)"

# macOS
curl -Ls "https://get.maestro.mobile.dev" | bash

# Verify
maestro --version
```

## Android Emulator Setup

```bash
# List available AVDs
emulator -list-avds

# Create a Pixel 6 emulator (API 34) via Android Studio AVD Manager, then:
emulator -avd Pixel_6_API_34 -no-snapshot-load

# Or use existing connected physical device:
adb devices   # lists connected devices
```

## Configure Test Credentials

```powershell
# Windows
Copy-Item tests\maestro\.env.example tests\maestro\.env
# Edit tests\maestro\.env and fill in PLAYER_A_MOBILE, PLAYER_B_MOBILE
```

## Run Tests

### Two-Device Multiplayer (recommended)

Open two terminals:

**Terminal 1 — Device A (host):**
```powershell
cd d:\Live-Code\Live-Rox-Ludo\games
.\tests\maestro\run_device_a.ps1 -Device "emulator-5554"
```

**Terminal 2 — Device B (joiner):**
```powershell
# Wait for Device A to create the room, then pass the room code:
.\tests\maestro\run_device_b.ps1 -Device "emulator-5556" -RoomCode "ABC123"
```

### Single Flow (quick test)

```bash
maestro test tests/maestro/flows/10_screen_rotation_test.yaml
maestro test tests/maestro/flows/04_turn_and_dice_validation.yaml
```

### Run with Video Recording

```bash
maestro test --format junit --output results.xml \
  --video tests/maestro/flows/04_turn_and_dice_validation.yaml
```

## Unity Accessibility ID Setup

For Maestro to find UI elements, add accessibility IDs to Unity GameObjects:

### Option A — Per Component
1. Add `MaestroAccessibilityId.cs` to Unity project (`Assets/_Project/`)
2. Attach the component to each Button/Panel you want testable
3. Set the `elementId` field in Inspector to match the IDs in the YAML files

### Option B — Central Bridge (recommended)
1. Add `UnityAccessibilityBridge.cs` to Unity project
2. Add to a persistent GameObject (e.g., `GameManager`)
3. Rename your GameObjects to match the keys in the `IdMap` dictionary

### Required accessibility IDs

| Accessibility ID | Unity GameObject Name |
|---|---|
| `btn_ludo_classic` | `LudoClassicButton` |
| `btn_private_table` | `PrivateTableButton` |
| `btn_join_private_table` | `JoinPrivateTableButton` |
| `panel_waiting_board` | `WaitingBoardPanel` |
| `panel_game_board` | `GameBoardPanel` |
| `btn_roll_dice` | `RollDiceButton` |
| `panel_dice_result` | `DiceResultPanel` |
| `btn_move_token_0` | `MoveToken0Button` |
| `btn_mic` | `MicButton` |
| `panel_reconnecting` | `ReconnectingPanel` |

## Screenshots & Video on Failure

Screenshots are saved automatically by `takeScreenshot` commands in each flow.
Output location: `tests/maestro/screenshots/`

Maestro Studio (interactive debugger):
```bash
maestro studio
```

## CI Integration (GitHub Actions)

```yaml
- name: Run Maestro tests
  uses: mobile-dev-inc/action-maestro-cloud@v1
  with:
    api-key: ${{ secrets.MAESTRO_CLOUD_API_KEY }}
    app-file: app.apk
    workspace: tests/maestro
```
