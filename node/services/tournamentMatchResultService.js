'use strict';

/**
 * TournamentMatchResultService
 *
 * After a tournament match ends in Node.js (ludo_v2 socket),
 * this service POSTs the result to Laravel's internal API.
 *
 * Laravel endpoint:
 *   POST /api/internal/v1/tournaments/matches/{matchId}/result
 *   Header: Authorization: Bearer {INTERNAL_SERVER_TOKEN}
 *
 * Payload:
 * {
 *   room_id:     string,
 *   started_at:  ISO string,
 *   ended_at:    ISO string,
 *   results: [
 *     { user_id, slot, score, finish_position, result }
 *   ],
 *   game_log: string | null
 * }
 */
class TournamentMatchResultService {
  constructor() {
    this.apiBaseUrl    = (process.env.LARAVEL_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/$/, '');
    this.internalToken = process.env.INTERNAL_SERVER_TOKEN || '';
    this.timeoutMs     = Math.max(10000, Number(process.env.TOURNAMENT_RESULT_TIMEOUT_MS || '30000'));
    this.maxRetries    = Math.max(0, Number(process.env.TOURNAMENT_RESULT_RETRIES || '3'));
    this.retryDelayMs  = Math.max(500, Number(process.env.TOURNAMENT_RESULT_RETRY_DELAY_MS || '1000'));
  }

  /**
   * Post tournament match result to Laravel.
   *
   * @param {number}  matchId     - tournament_matches.id in Laravel DB
   * @param {string}  roomId      - Node.js socket room ID
   * @param {Date}    startedAt
   * @param {Date}    endedAt
   * @param {Array}   results     - [{user_id, slot, score, finish_position, result}]
   * @param {string|null} gameLog - optional base64 replay
   */
  async postResult({ matchId, roomId, startedAt, endedAt, results, gameLog = null }) {
    const url     = `${this.apiBaseUrl}/internal/v1/tournaments/matches/${matchId}/result`;
    const payload = {
      room_id:    roomId,
      started_at: startedAt instanceof Date ? startedAt.toISOString() : startedAt,
      ended_at:   endedAt instanceof Date ? endedAt.toISOString() : endedAt,
      results:    results.map((r) => ({
        user_id:         r.userId ?? r.user_id ?? null,
        slot:            r.slot,
        score:           r.score ?? 0,
        finish_position: r.finishPosition ?? r.finish_position,
        result:          r.result, // 'win' | 'loss' | 'draw' | 'forfeit' | 'disconnected'
      })),
      game_log: gameLog,
    };

    let lastError = null;

    for (let attempt = 0; attempt <= this.maxRetries; attempt++) {
      try {
        const response = await fetch(url, {
          method:  'POST',
          headers: {
            Accept:        'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${this.internalToken}`,
          },
          body:   JSON.stringify(payload),
          signal: AbortSignal.timeout(this.timeoutMs),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          const msg = data?.message || `HTTP ${response.status}`;
          // Don't retry 409 (already completed) or 422 (validation error)
          if (response.status === 409 || response.status === 422) {
            throw new Error(`[TournamentMatchResult] Non-retryable error: ${msg}`);
          }
          throw new Error(`[TournamentMatchResult] ${msg}`);
        }

        console.log(`[TournamentMatchResult] Match ${matchId} result posted successfully.`);
        return data;

      } catch (error) {
        lastError = error;

        const isNonRetryable = String(error.message).includes('Non-retryable');
        if (isNonRetryable || attempt >= this.maxRetries) {
          break;
        }

        const delay = this.retryDelayMs * Math.pow(2, attempt); // exponential backoff
        console.warn(
          `[TournamentMatchResult] Attempt ${attempt + 1} failed for match ${matchId}. ` +
          `Retrying in ${delay}ms... Error: ${error.message}`
        );
        await new Promise((resolve) => setTimeout(resolve, delay));
      }
    }

    // All retries exhausted — log for manual intervention
    console.error(`[TournamentMatchResult] FAILED to post result for match ${matchId} after ${this.maxRetries + 1} attempts.`, {
      matchId,
      roomId,
      error: lastError?.message,
    });

    throw lastError || new Error(`[TournamentMatchResult] Failed to post match result.`);
  }

  /**
   * Build results array from ludo_v2 seat results + room snapshot.
   * Maps seat positions to user_id, score, and finish_position.
   *
   * @param {Object} roomState    - Node.js in-memory room state
   * @param {Array}  seatResults  - [{seatNo, userId, score, finishPosition, result}]
   */
  buildResultsFromSeatState(roomState, seatResults) {
    return seatResults
      .filter((s) => s.userId != null) // skip bot-only slots with no user
      .map((s) => ({
        user_id:         s.userId,
        slot:            s.seatNo,
        score:           s.score ?? 0,
        finish_position: s.finishPosition ?? s.finish_position,
        result:          s.result ?? (s.finishPosition === 1 ? 'win' : 'loss'),
      }));
  }
}

module.exports = new TournamentMatchResultService();
