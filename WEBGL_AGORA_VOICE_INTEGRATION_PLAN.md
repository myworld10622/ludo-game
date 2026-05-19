# WebGL Agora Voice Integration Plan

## Goal

Add working voice chat for `ROX Ludo` WebGL without breaking:

- Android APK voice
- iOS/native voice
- private table flow
- existing voice UI in Unity

This plan is only for the remaining WebGL voice work.  
The WebGL loading crash and WebGL mobile sizing issues are treated separately.

## Current Status

### Already working

- WebGL app loads and opens correctly.
- WebGL portrait sizing is working on mobile.
- Private table flow is working.
- Voice UI already exists in Unity:
  - mic icon
  - status label
  - leave button
  - mute/speaker buttons
  - room open / room close hooks
- Backend Agora token API already exists.
- Room/channel naming logic already exists.

### Already fixed separately

- WebGL runtime crash after loading scene.
- WebGL private table board landscape sizing path.

### Still not available on WebGL

- Real voice connection using current Unity Agora path.

## Root Problem

The current implementation uses the `Agora Unity RTC SDK` native runtime path.  
That path works for APK because the plugin ships native Android/iOS/desktop binaries.

But the current package in this repo does **not** provide a proper WebGL runtime path for actual voice connection.

What we found in the repo:

- Agora plugin contains native binaries for:
  - Android
  - iOS
  - Windows
  - macOS
- There is no equivalent real WebGL runtime integration in the current project package.
- Because of that, WebGL can show the voice UI, request token, and even reach the init flow, but it cannot be trusted to establish real voice using the same native Unity path as APK.

## What Must Change

Do **not** try to force the current Unity native Agora runtime to work on WebGL.

Instead:

- keep current Unity Agora implementation for APK/native builds
- add a **WebGL-only** voice implementation using **Agora Web SDK**
- bridge Unity UI to JavaScript for WebGL only

## Required Architecture

### Native platforms

Keep current code path:

- `AgoraVoiceManager.cs`
- current Unity Agora SDK
- current token API

### WebGL platform

Use a separate path:

- Agora Web SDK in browser JavaScript
- WebGL bridge functions exposed via `.jslib` or template JS
- Unity only sends commands and receives callbacks

## Final WebGL Flow

### Join flow

1. User enters Ludo room.
2. Unity detects WebGL build.
3. Unity requests token from existing backend API.
4. Unity calls JS bridge:
   - `RoxAgoraWebJoin(appId, channel, token, uid)`
5. JavaScript uses Agora Web SDK:
   - create client
   - create microphone audio track
   - join channel
   - publish local audio track
6. JS notifies Unity:
   - connected
   - failed
   - user joined
   - user left

### Leave flow

1. Unity calls:
   - `RoxAgoraWebLeave()`
2. JS unpublishes track, leaves client, stops mic track.
3. JS notifies Unity voice disconnected.

### Mute flow

1. Unity calls:
   - `RoxAgoraWebSetMuted(true/false)`
2. JS enables/disables local microphone track.
3. UI updates in Unity.

## Files Expected To Change

### Unity C#

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Voice/AgoraVoiceManager.cs`
  - split native vs WebGL path
  - keep token API logic reusable
  - route WebGL calls to JS bridge

- `unity/Assets/_Project/Games/LudoClassic/ScriptOffline/Socket/LudoV2MatchmakingBridge.cs`
  - keep room open / room close hooks
  - no business logic rewrite expected

### WebGL bridge

- `unity/Assets/Plugins/WebGL/RoxWebGlBridge.jslib`
  - add Agora Web SDK bridge methods
  - join / leave / mute / event callback support

Potential new file:

- `unity/Assets/Plugins/WebGL/agora-webgl-voice.js`
  - dedicated Agora Web SDK logic
  - cleaner than putting everything directly in `.jslib`

### Web template

- `unity/Assets/WebGLTemplates/Better2020/index.html`
  - include Agora Web SDK script
  - initialize bridge globals safely

## Backend Reuse

No backend rewrite should be needed if existing token API already returns:

- `appId`
- `token`
- `channel`
- `uid`

Need only verify Web SDK expects the same:

- integer UID vs string UID
- token expiry / renew handling
- channel naming compatibility

## Scope Split

### Stage 1: MVP WebGL Voice

Target:

- connect to voice room
- publish local mic
- hear remote users
- mute / unmute
- leave room cleanly
- usable status label in Unity

Not required in MVP:

- speaker toggle parity with APK
- device switching UI
- advanced reconnect polish
- per-user talking indicators

### Stage 2: Hardening

- reconnect after tab suspend / resume
- token renewal
- autoplay handling improvements
- better browser error messages
- browser compatibility cleanup

## Risks

### 1. Browser mic permission timing

Web browsers allow mic access only under specific user gesture / permission conditions.

Risk:

- room loads but mic track creation fails

Mitigation:

- request mic in WebGL path before join
- show explicit failure state in Unity

### 2. Autoplay / audio policy

Some browsers block audio playback until user interaction.

Risk:

- join succeeds but remote audio seems silent

Mitigation:

- ensure initial user interaction before starting voice
- bind audio start after click if needed

### 3. UID type mismatch

Agora Web SDK is stricter about UID handling in some paths.

Risk:

- join failure or silent remote issues

Mitigation:

- verify existing backend UID format
- normalize to exact type expected by Web SDK

### 4. Breaking APK voice

Risk:

- WebGL refactor accidentally impacts Android path

Mitigation:

- isolate all new logic inside `#if UNITY_WEBGL && !UNITY_EDITOR`
- do not replace current native branch

## What Must Not Be Changed

- Android APK Agora flow
- backend token response contract unless absolutely required
- existing room naming rules unless Agora Web SDK forces a change
- existing private table join business logic

## Testing Checklist

### WebGL functional

1. Open WebGL on mobile Chrome.
2. Join private table from WebGL.
3. Enter Ludo board.
4. Start voice from WebGL side.
5. Verify mic permission prompt appears if needed.
6. Verify WebGL user can hear APK user.
7. Verify APK user can hear WebGL user.
8. Test mute/unmute.
9. Test leave room.

### Cross-device

1. APK host + WebGL joiner
2. WebGL host + APK joiner
3. WebGL + WebGL if supported later

### Regression

1. APK voice still connects
2. APK private table still works
3. WebGL board still switches to correct layout

## Estimated Time

### If scope stays tight

- MVP implementation: `2 to 4 working days`

### If full hardening is required

- production-safe version: `4 to 6 working days`

## Recommended Implementation Order

1. Add Agora Web SDK to WebGL template.
2. Add JS bridge methods for join / leave / mute.
3. Update `AgoraVoiceManager.cs` WebGL branch to use JS bridge.
4. Reuse existing backend token API.
5. Add Unity callback handlers for connection state.
6. Test with APK ↔ WebGL private table.
7. Only after MVP works, improve reconnect and browser edge cases.

## Final Recommendation

Do not keep patching the current WebGL voice under the native Unity Agora runtime.

Correct path is:

- native builds keep current Agora Unity SDK
- WebGL gets separate Agora Web SDK bridge

That keeps APK safe and gives the highest chance of stable WebGL voice.
