'use strict';

/**
 * LudoGameEngine — Server-side authoritative Ludo game logic.
 *
 * Security principle (document Section 15):
 *  - Client sends INTENT (roll dice, move token X).
 *  - Server validates and decides outcome.
 *  - Token positions tracked server-side; client is display-only.
 *  - Dice generated server-side with cryptographically secure random.
 */

const crypto = require('crypto');

// ── Board Constants ───────────────────────────────────────────────────────────

const TOTAL_SQUARES   = 52;   // Main path squares
const HOME_STRETCH    = 6;    // Home column squares per player
const TOKENS_PER_PLAYER = 4;
const SAFE_SQUARES    = new Set([1, 9, 14, 22, 27, 35, 40, 48]); // Classic Ludo safe squares
const HOME_POSITION   = 100;  // Sentinel value for "at home"
const BASE_POSITION   = -1;   // Sentinel value for "at base (not entered)"

// Player start positions on the main path (where token enters after rolling 6)
const PLAYER_START = { 1: 1, 2: 14, 3: 27, 4: 40 };
// Home stretch start square (player-specific)
const HOME_STRETCH_ENTRY = { 1: 51, 2: 12, 3: 25, 4: 38 };

class LudoGameEngine {

  /**
   * Initialize a fresh game state for a match.
   *
   * @param {string[]} playerIds   - Array of user IDs (or 'bot_X' for bots)
   * @param {number}   turnTimeSec - Seconds per turn
   */
  createGameState(playerIds, turnTimeSec = 30) {
    const players = {};

    playerIds.forEach((id, idx) => {
      const slot = idx + 1; // 1-indexed
      players[id] = {
        id,
        slot,
        tokens: [
          { id: 1, pos: BASE_POSITION },
          { id: 2, pos: BASE_POSITION },
          { id: 3, pos: BASE_POSITION },
          { id: 4, pos: BASE_POSITION },
        ],
        finishedTokens: 0,
        isFinished:     false,
        finishPosition: null,
      };
    });

    return {
      players,
      turnOrder:        playerIds.slice(), // rotates each turn
      currentTurnIdx:   0,
      currentPlayerId:  playerIds[0],
      diceValue:        null,
      validMoves:       [],    // token IDs that can move
      turnStartedAt:    Date.now(),
      turnTimeSec,
      consecutiveSixes: 0,
      finishOrder:      [],    // player IDs in finish order
      isComplete:       false,
    };
  }

  // ── Dice Roll ─────────────────────────────────────────────────────────────

  /**
   * Generate a cryptographically secure dice roll (1–6).
   * Document Section 15: "Dice rolls generated server-side using cryptographically secure random"
   */
  rollDice() {
    const buf = crypto.randomBytes(1);
    return (buf[0] % 6) + 1;
  }

  /**
   * Process a dice roll request from a player.
   * Returns { valid, diceValue, validMoves, error }
   */
  processDiceRoll(state, playerId) {
    if (state.isComplete) {
      return { valid: false, error: 'Game is already complete.' };
    }

    if (state.currentPlayerId !== playerId) {
      return { valid: false, error: 'Not your turn.' };
    }

    if (state.diceValue !== null) {
      return { valid: false, error: 'Dice already rolled this turn. Move a token first.' };
    }

    // Check turn timeout
    const elapsed = (Date.now() - state.turnStartedAt) / 1000;
    if (elapsed > state.turnTimeSec + 5) { // +5s grace
      return { valid: false, error: 'Turn timed out.' };
    }

    const diceValue  = this.rollDice();
    const validMoves = this.getValidMoves(state, playerId, diceValue);

    state.diceValue  = diceValue;
    state.validMoves = validMoves;

    // Track consecutive sixes
    if (diceValue === 6) {
      state.consecutiveSixes++;
    } else {
      state.consecutiveSixes = 0;
    }

    // Three consecutive sixes → forfeit turn (anti-cheat)
    if (state.consecutiveSixes >= 3) {
      state.consecutiveSixes = 0;
      this.advanceTurn(state);
      return {
        valid:       true,
        diceValue,
        validMoves:  [],
        forcedSkip:  true,
        message:     'Three sixes in a row — turn forfeited.',
      };
    }

    // No valid moves → auto-advance turn
    if (validMoves.length === 0) {
      this.advanceTurn(state);
      return { valid: true, diceValue, validMoves: [], autoSkip: true };
    }

    return { valid: true, diceValue, validMoves };
  }

  // ── Token Move ────────────────────────────────────────────────────────────

  /**
   * Validate and apply a token move.
   * Document Section 15: "Token positions tracked server-side; client position is display-only"
   *
   * Returns { valid, from, to, event, captured, error }
   * event: 'move' | 'capture' | 'safe' | 'home'
   */
  processMove(state, playerId, tokenId) {
    if (state.currentPlayerId !== playerId) {
      return { valid: false, error: 'Not your turn.' };
    }

    if (state.diceValue === null) {
      return { valid: false, error: 'Roll dice first.' };
    }

    if (!state.validMoves.includes(tokenId)) {
      return { valid: false, error: 'Invalid token move.' };
    }

    const player = state.players[playerId];
    const token  = player.tokens.find((t) => t.id === tokenId);

    if (!token) {
      return { valid: false, error: 'Token not found.' };
    }

    const diceValue = state.diceValue;
    const from      = token.pos;
    let to;
    let event = 'move';
    let captured = null;

    // ── Calculate new position ────────────────────────────────────────────────
    if (from === BASE_POSITION) {
      // Enter board on 6
      if (diceValue !== 6) {
        return { valid: false, error: 'Need a 6 to enter the board.' };
      }
      to = PLAYER_START[player.slot];
    } else {
      to = this.calculateNewPosition(from, diceValue, player.slot);
    }

    // ── Home ──────────────────────────────────────────────────────────────────
    if (to === HOME_POSITION) {
      token.pos = HOME_POSITION;
      player.finishedTokens++;
      event = 'home';

      if (player.finishedTokens === TOKENS_PER_PLAYER) {
        player.isFinished = true;
        player.finishPosition = state.finishOrder.length + 1;
        state.finishOrder.push(playerId);

        // Check if game is complete (all but one player finished)
        if (state.finishOrder.length >= Object.keys(state.players).length - 1) {
          this.concludeGame(state);
        }
      }
    } else {
      // ── Capture check ─────────────────────────────────────────────────────
      if (!SAFE_SQUARES.has(to)) {
        const capturedPlayer = this.checkCapture(state, playerId, to);
        if (capturedPlayer) {
          captured = capturedPlayer;
          event    = 'capture';
        }
      } else {
        event = 'safe';
      }

      token.pos = to;
    }

    // ── Turn advance ──────────────────────────────────────────────────────────
    const gotBonus = (diceValue === 6 || event === 'capture');
    if (!gotBonus) {
      this.advanceTurn(state);
    } else {
      // Bonus turn: reset dice but keep same player
      state.diceValue   = null;
      state.validMoves  = [];
      state.turnStartedAt = Date.now();
    }

    return { valid: true, from, to, event, captured, bonusTurn: gotBonus };
  }

  // ── Forfeit (timeout) ─────────────────────────────────────────────────────

  processForfeit(state, playerId) {
    if (state.currentPlayerId !== playerId) {
      return false;
    }
    this.advanceTurn(state);
    return true;
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  getValidMoves(state, playerId, diceValue) {
    const player  = state.players[playerId];
    const validIds = [];

    for (const token of player.tokens) {
      if (token.pos === HOME_POSITION) continue; // Already home

      if (token.pos === BASE_POSITION) {
        if (diceValue === 6) validIds.push(token.id);
        continue;
      }

      const newPos = this.calculateNewPosition(token.pos, diceValue, player.slot);
      if (newPos !== null) {
        validIds.push(token.id);
      }
    }

    return validIds;
  }

  calculateNewPosition(currentPos, diceValue, playerSlot) {
    const homeEntry = HOME_STRETCH_ENTRY[playerSlot];
    const newPos    = currentPos + diceValue;

    // Check if entering home stretch
    if (currentPos <= homeEntry && newPos > homeEntry) {
      const overshoot    = newPos - homeEntry;
      const homeStretchPos = 52 + (playerSlot - 1) * HOME_STRETCH + overshoot;
      const maxHomePos   = 52 + (playerSlot - 1) * HOME_STRETCH + HOME_STRETCH;

      if (homeStretchPos > maxHomePos) return null; // Overshoot home
      if (homeStretchPos === maxHomePos) return HOME_POSITION; // Exactly home
      return homeStretchPos;
    }

    // Wrap around main path
    const wrapped = ((newPos - 1) % TOTAL_SQUARES) + 1;
    return wrapped;
  }

  checkCapture(state, attackerId, targetPos) {
    for (const [pid, player] of Object.entries(state.players)) {
      if (pid === attackerId) continue;

      for (const token of player.tokens) {
        if (token.pos === targetPos && !SAFE_SQUARES.has(targetPos)) {
          // Send token back to base
          token.pos = BASE_POSITION;
          return pid; // Return captured player ID
        }
      }
    }
    return null;
  }

  advanceTurn(state) {
    state.diceValue       = null;
    state.validMoves      = [];
    state.consecutiveSixes = 0;

    // Skip finished players
    const activePlayers = state.turnOrder.filter((id) => !state.players[id]?.isFinished);
    if (activePlayers.length <= 1) {
      this.concludeGame(state);
      return;
    }

    const nextIdx       = (state.currentTurnIdx + 1) % activePlayers.length;
    state.currentTurnIdx  = nextIdx;
    state.currentPlayerId = activePlayers[nextIdx];
    state.turnStartedAt   = Date.now();
  }

  concludeGame(state) {
    // Add remaining (unfinished) player as last place
    for (const [pid, player] of Object.entries(state.players)) {
      if (!player.isFinished) {
        player.finishPosition = state.finishOrder.length + 1;
        state.finishOrder.push(pid);
      }
    }
    state.isComplete = true;
  }

  /**
   * Build the result payload to send to Laravel.
   */
  buildMatchResult(state, roomId) {
    const results = [];

    for (const [pid, player] of Object.entries(state.players)) {
      if (pid.startsWith('bot_')) {
        results.push({
          user_id:         null,
          slot:            player.slot,
          score:           player.finishedTokens,
          finish_position: player.finishPosition ?? 99,
          result:          player.finishPosition === 1 ? 'win' : 'loss',
        });
      } else {
        results.push({
          user_id:         parseInt(pid),
          slot:            player.slot,
          score:           player.finishedTokens,
          finish_position: player.finishPosition ?? 99,
          result:          player.finishPosition === 1 ? 'win' : 'loss',
        });
      }
    }

    return { roomId, results };
  }
}

module.exports = new LudoGameEngine();
