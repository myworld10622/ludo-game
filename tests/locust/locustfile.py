"""
locustfile.py — Ludo V2 load tests.

Scenarios:
  LudoMatchmakingUser   — Join queue, wait for room start, disconnect.
  LudoFullGameUser      — Play a complete 2-player game bot vs bot.
  LudoReconnectUser     — Join, play 3 turns, disconnect, reconnect, continue.

Run:
  # Headless, 20 users, 2 spawn/s, 60s
  locust -f locustfile.py --headless -u 20 -r 2 --run-time 60s

  # UI mode
  locust -f locustfile.py
  # Open http://localhost:8089
"""

import time
import uuid
import random
import logging

from locust import User, task, between, events
from ludo_client import LudoSocketClient

logger = logging.getLogger("locust.ludo")

SERVER_URL = "http://localhost:3002"

# ── Shared room registry: allows multiple users to join the same room ─────────
# In headless mode rooms are paired up by a simple counter.
import threading
_room_pool:  list = []
_room_lock   = threading.Lock()
_room_cursor = 0

def get_or_create_room(max_players: int = 2) -> str:
    global _room_cursor
    with _room_lock:
        # Pair up users: every max_players users share a room_uuid
        idx   = _room_cursor // max_players
        _room_cursor += 1
        if idx >= len(_room_pool):
            _room_pool.append(str(uuid.uuid4()))
        return _room_pool[idx]


# ── Event reporting ───────────────────────────────────────────────────────────

def report(env, name: str, response_time_ms: float, success: bool, error=None):
    events.request.fire(
        request_type   = "SOCKET",
        name           = name,
        response_time  = response_time_ms,
        response_length= 0,
        exception      = error,
    )


# ── User classes ──────────────────────────────────────────────────────────────

class LudoMatchmakingUser(User):
    """
    Measure matchmaking latency: time from join_queue → ludo.room.starting.
    """
    wait_time = between(1, 3)

    def on_start(self):
        self.uid = random.randint(100000, 999999)

    @task
    def matchmaking_flow(self):
        client = LudoSocketClient(user_id=self.uid, server_url=SERVER_URL)

        t0 = time.time()
        connected = client.connect(timeout=8)
        connect_ms = (time.time() - t0) * 1000
        report(None, "socket_connect", connect_ms, connected)

        if not connected:
            return

        room_uuid = get_or_create_room(max_players=2)
        t1 = time.time()
        client.join_queue(room_uuid, max_players=2)

        try:
            client.wait_for("ludo.room.starting", timeout=20)
            matchmaking_ms = (time.time() - t1) * 1000
            report(None, "matchmaking_latency", matchmaking_ms, True)
        except TimeoutError as e:
            report(None, "matchmaking_latency", 20000, False, e)
        finally:
            client.disconnect()


class LudoFullGameUser(User):
    """
    Play a complete 2-player game. Measures:
      • time-to-first-turn
      • time-per-turn-action (roll + move round trip)
      • time-to-result (full game duration)
    """
    wait_time = between(2, 5)

    def on_start(self):
        self.uid = random.randint(100000, 999999)

    @task
    def full_game(self):
        client = LudoSocketClient(user_id=self.uid, server_url=SERVER_URL)

        if not client.connect(timeout=8):
            report(None, "socket_connect", 8000, False)
            return

        room_uuid = get_or_create_room(max_players=2)
        client.join_queue(room_uuid, max_players=2)

        try:
            client.wait_for("ludo.room.starting", timeout=20)
        except TimeoutError:
            report(None, "game_start_timeout", 20000, False)
            client.disconnect()
            return

        t_start = time.time()

        try:
            first_turn = client.wait_for("ludo.game.turn_started", timeout=12)
        except TimeoutError:
            report(None, "first_turn_latency", 12000, False)
            client.disconnect()
            return

        first_turn_ms = (time.time() - t_start) * 1000
        report(None, "first_turn_latency", first_turn_ms, True)

        turn_count  = 0
        max_turns   = 200
        game_result = None

        while turn_count < max_turns:
            try:
                turn = client.wait_for("ludo.game.turn_started", timeout=20)
            except TimeoutError:
                break

            si = turn.get("seat_index")
            if si != client.seat_index:
                # Not my turn — wait for the move result
                try:
                    moved = client.wait_for("ludo.game.token_moved",
                        predicate=lambda d: d.get("seat_index") == si,
                        timeout=20)
                    if moved.get("is_win"):
                        game_result = client.wait_for("ludo.game.result", timeout=10)
                        break
                except TimeoutError:
                    break
                continue

            # My turn
            t_action = time.time()
            client.roll_dice()
            try:
                dice = client.wait_for("ludo.game.dice_rolled",
                    predicate=lambda d: d.get("seat_index") == si,
                    timeout=8)
            except TimeoutError:
                break

            if dice.get("has_moves"):
                ti = client.select_token(dice.get("legal_tokens", []), dice.get("dice_value", 1))
                client.move_token(ti)

            try:
                moved = client.wait_for("ludo.game.token_moved",
                    predicate=lambda d: d.get("seat_index") == si,
                    timeout=8)
                action_ms = (time.time() - t_action) * 1000
                report(None, "turn_action_latency", action_ms, True)

                if moved.get("is_win"):
                    game_result = client.wait_for("ludo.game.result", timeout=10)
                    break
            except TimeoutError:
                break

            turn_count += 1

        if game_result:
            total_ms = (time.time() - t_start) * 1000
            report(None, "full_game_latency", total_ms, True)
        else:
            report(None, "full_game_latency", (time.time() - t_start) * 1000, False)

        client.disconnect()


class LudoReconnectUser(User):
    """
    Simulate a reconnecting player.
    Joins, plays 3 turns, disconnects, reconnects, verifies game.state.
    """
    wait_time = between(3, 8)

    def on_start(self):
        self.uid = random.randint(100000, 999999)

    @task
    def reconnect_flow(self):
        client = LudoSocketClient(user_id=self.uid, server_url=SERVER_URL)

        if not client.connect(timeout=8):
            return

        room_uuid = get_or_create_room(max_players=2)
        client.join_queue(room_uuid, max_players=2)

        try:
            client.wait_for("ludo.room.starting", timeout=20)
            client.wait_for("ludo.game.turn_started", timeout=12)
        except TimeoutError:
            client.disconnect()
            return

        # Play 3 turns
        for _ in range(3):
            try:
                turn = client.wait_for("ludo.game.turn_started", timeout=20)
                si   = turn.get("seat_index")
                if si == client.seat_index:
                    client.roll_dice()
                    dice = client.wait_for("ludo.game.dice_rolled",
                        predicate=lambda d: d.get("seat_index") == si, timeout=8)
                    if dice.get("has_moves"):
                        ti = client.select_token(dice.get("legal_tokens", []), dice.get("dice_value", 1))
                        client.move_token(ti)
                client.wait_for("ludo.game.token_moved",
                    predicate=lambda d: d.get("seat_index") == si, timeout=8)
            except TimeoutError:
                break

        room_id = client.room_id
        client.disconnect()

        # Brief pause — simulating network dropout
        time.sleep(0.5)

        # Reconnect
        t_recon = time.time()
        client2 = LudoSocketClient(user_id=self.uid, server_url=SERVER_URL)
        if not client2.connect(timeout=8):
            report(None, "reconnect_connect", 8000, False)
            return

        client2.reconnect_session(room_id)
        try:
            gs = client2.wait_for("ludo.game.state", timeout=8)
            recon_ms = (time.time() - t_recon) * 1000
            report(None, "reconnect_state_latency", recon_ms, True)
        except TimeoutError as e:
            report(None, "reconnect_state_latency", 8000, False, e)

        client2.disconnect()
