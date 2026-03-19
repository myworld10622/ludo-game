# Ludo Foundation

This folder documents the intended transition of Ludo from the current HTTP-proxy socket flow to a room-engine architecture.

## Current State

- `sockets/ludoSocket.js` proxies most gameplay calls to backend HTTP endpoints.
- Room timing exists, but room ownership is not fully Node-authoritative.
- Bot-like timeout behavior exists through `autochaal`, but seat-fill orchestration is not first-class.

## Foundation Files

- `constants/ludoRoom.js`: canonical room states and socket event names
- `services/ludoRoomEngineService.js`: additive room and bot-fill policy skeleton

These files are not wired into production flow yet. They exist to guide the next implementation phase safely.
