"""
ludo_client.py — Socket.IO client wrapper for Python/Locust.

Drives one virtual Ludo player through a full or partial game session.
Tracks:
  • time-to-connect
  • time-to-room-start (matchmaking latency)
  • time-per-turn
  • time-to-result (full game latency)

Requires:  pip install python-socketio[client] gevent
"""

import time
import json
import uuid
import logging
import random
import threading
from typing import Optional, Callable

import socketio

logger = logging.getLogger(__name__)

SERVER_URL = "http://localhost:3002"
NAMESPACE  = "/ludo_v2"

# Board constants (mirrors server)
TOKEN_HOME_POS  = 56
BOARD_RING_SIZE = 52
SAFE_ABS        = {0, 8, 13, 21, 26, 34, 39, 47}
PLAYER_STARTS   = {2: [0, 26], 3: [0, 13, 26], 4: [0, 13, 26, 39]}


class LudoSocketClient:
    """
    Thin Socket.IO client for one Ludo player.

    Thread-safe: events are received on the socketio background thread;
    actions are called from the Locust user thread.
    """

    def __init__(
        self,
        user_id: int,
        server_url: str = SERVER_URL,
        namespace:  str = NAMESPACE,
        verbose:    bool = False,
    ):
        self.user_id    = user_id
        self.server_url = server_url
        self.namespace  = namespace
        self.verbose    = verbose

        self.sio         = socketio.Client(reconnection=False, logger=verbose)
        self.room_id:    Optional[str] = None
        self.seat_index: Optional[int] = None
        self.turn_nonce: Optional[str] = None
        self.roll_nonce: Optional[str] = None
        self.tokens:     Optional[list] = None  # 2D: tokens[seat][ti] = position

        # Event mailbox: event_name → list of received payloads
        self._mailbox:  dict[str, list] = {}
        self._lock      = threading.Lock()
        self._connected = threading.Event()

        self._register_handlers()

    # ── Registration ────────────────────────────────────────────────────────

    def _register_handlers(self):
        sio = self.sio

        @sio.event(namespace=self.namespace)
        def connect():
            self._connected.set()
            self._push("__connected", {})

        @sio.event(namespace=self.namespace)
        def disconnect():
            self._push("__disconnected", {})

        @sio.on("ludo.room.waiting", namespace=self.namespace)
        def on_waiting(data):
            d = self._parse(data)
            if d.get("room_id"):
                self.room_id = d["room_id"]
            self._update_seat(d)
            self._push("ludo.room.waiting", d)

        @sio.on("ludo.room.starting", namespace=self.namespace)
        def on_starting(data):
            d = self._parse(data)
            if d.get("room_id"):
                self.room_id = d["room_id"]
            self._update_seat(d)
            self._push("ludo.room.starting", d)

        @sio.on("ludo.game.snapshot", namespace=self.namespace)
        def on_snapshot(data):
            d = self._parse(data)
            if d.get("room_id"):
                self.room_id = d["room_id"]
            self._update_seat(d)
            self._push("ludo.game.snapshot", d)

        @sio.on("ludo.game.turn_started", namespace=self.namespace)
        def on_turn(data):
            d = self._parse(data)
            if d.get("seat_index") == self.seat_index:
                self.turn_nonce = d.get("turn_nonce")
                self.roll_nonce = None
            self._push("ludo.game.turn_started", d)

        @sio.on("ludo.game.dice_rolled", namespace=self.namespace)
        def on_dice(data):
            d = self._parse(data)
            if d.get("seat_index") == self.seat_index:
                self.roll_nonce = d.get("roll_nonce")
                self.turn_nonce = None
            self._push("ludo.game.dice_rolled", d)

        @sio.on("ludo.game.token_moved", namespace=self.namespace)
        def on_moved(data):
            d = self._parse(data)
            if d.get("tokens"):
                self.tokens = d["tokens"]
            self._push("ludo.game.token_moved", d)

        @sio.on("ludo.game.turn_missed", namespace=self.namespace)
        def on_missed(data):
            self._push("ludo.game.turn_missed", self._parse(data))

        @sio.on("ludo.game.result", namespace=self.namespace)
        def on_result(data):
            self._push("ludo.game.result", self._parse(data))

        @sio.on("ludo.game.state", namespace=self.namespace)
        def on_state(data):
            d = self._parse(data)
            if d.get("tokens"):
                self.tokens = d["tokens"]
            if d.get("turn_nonce"):
                self.turn_nonce = d["turn_nonce"]
            if d.get("roll_nonce"):
                self.roll_nonce = d["roll_nonce"]
            self._push("ludo.game.state", d)

        @sio.on("ludo.error", namespace=self.namespace)
        def on_error(data):
            self._push("ludo.error", self._parse(data))

    # ── Internal helpers ────────────────────────────────────────────────────

    def _parse(self, data):
        if isinstance(data, str):
            try:
                return json.loads(data)
            except Exception:
                return {}
        return data or {}

    def _push(self, event: str, payload: dict):
        with self._lock:
            if event not in self._mailbox:
                self._mailbox[event] = []
            self._mailbox[event].append(payload)

    def _update_seat(self, snapshot: dict):
        seats = snapshot.get("seats") or []
        for s in seats:
            if s and s.get("userId") == self.user_id:
                self.seat_index = max(0, s.get("seatNo", 1) - 1)
                return

    def _drain(self, event: str):
        """Return and clear all queued payloads for event."""
        with self._lock:
            payloads = self._mailbox.pop(event, [])
        return payloads

    # ── Connection ──────────────────────────────────────────────────────────

    def connect(self, timeout: float = 8.0) -> bool:
        try:
            self.sio.connect(
                self.server_url,
                namespaces=[self.namespace],
                transports=["websocket"],
                wait_timeout=timeout,
            )
            return self._connected.wait(timeout=timeout)
        except Exception as e:
            logger.warning(f"Connect failed uid={self.user_id}: {e}")
            return False

    def disconnect(self):
        try:
            self.sio.disconnect()
        except Exception:
            pass

    # ── Actions ─────────────────────────────────────────────────────────────

    def emit(self, event: str, data: dict):
        try:
            self.sio.emit(event, json.dumps(data), namespace=self.namespace)
        except Exception as e:
            logger.warning(f"Emit failed uid={self.user_id} event={event}: {e}")

    def join_queue(self, room_uuid: str, max_players: int = 2, allow_bots: bool = False):
        self.emit("ludo.queue.join", {
            "userId":      self.user_id,
            "displayName": f"LoadBot_{self.user_id}",
            "roomUuid":    room_uuid,
            "roomType":    "public",
            "playMode":    "practice",
            "gameMode":    "CLASSIC",
            "maxPlayers":  max_players,
            "entryFee":    0,
            "allowBots":   allow_bots,
        })

    def roll_dice(self):
        self.emit("ludo.game.roll_dice", {
            "room_id":    self.room_id,
            "user_id":    self.user_id,
            "turn_nonce": self.turn_nonce or "",
        })

    def move_token(self, token_index: int):
        self.emit("ludo.game.move_token", {
            "room_id":     self.room_id,
            "user_id":     self.user_id,
            "token_index": token_index,
            "roll_nonce":  self.roll_nonce or "",
        })

    def reconnect_session(self, room_id: str = None):
        self.emit("ludo.session.reconnect", {
            "room_id": room_id or self.room_id,
            "user_id": self.user_id,
        })

    # ── Wait helpers (blocking, for scripted flows) ──────────────────────────

    def wait_for(self, event: str, timeout: float = 20.0,
                 predicate: Optional[Callable] = None):
        deadline = time.time() + timeout
        while time.time() < deadline:
            payloads = self._drain(event)
            for p in payloads:
                if predicate is None or predicate(p):
                    return p
                # Put back non-matching ones
                self._push(event, p)
            time.sleep(0.05)
        raise TimeoutError(f"Timeout waiting for {event} uid={self.user_id}")

    def select_token(self, legal_tokens: list, dice_value: int) -> int:
        """Simple greedy: pick farthest-ahead token."""
        if not legal_tokens:
            return 0
        if not self.tokens or self.seat_index is None:
            return legal_tokens[0]

        def position(ti):
            try:
                return self.tokens[self.seat_index][ti]
            except Exception:
                return -1

        return max(legal_tokens, key=position)
