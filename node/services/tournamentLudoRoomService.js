'use strict';

class TournamentLudoRoomService {
  constructor() {
    this.apiBaseUrl = (process.env.LARAVEL_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/$/, '');
    this.claimTimeoutMs = Math.max(10000, Number(process.env.LUDO_TOURNAMENT_CLAIM_TIMEOUT_MS || '60000'));
    this.claimRetries = Math.max(0, Number(process.env.LUDO_TOURNAMENT_CLAIM_RETRIES || '3'));
    this.claimRetryDelayMs = Math.max(0, Number(process.env.LUDO_TOURNAMENT_CLAIM_RETRY_DELAY_MS || '500'));
  }

  async claimRoom(accessToken, tournamentUuid, tournamentEntryUuid) {
    const url = `${this.apiBaseUrl}/v1/tournaments/${tournamentUuid}/claim-room`;
    let lastError = null;

    for (let attempt = 0; attempt <= this.claimRetries; attempt += 1) {
      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${accessToken}`,
          },
          body: JSON.stringify({ tournament_entry_uuid: tournamentEntryUuid }),
          signal: AbortSignal.timeout(this.claimTimeoutMs),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          throw new Error(
            data?.message ||
              `Tournament room claim failed with status ${response.status}.`
          );
        }

        return data;
      } catch (error) {
        lastError = error;

        if (attempt >= this.claimRetries) {
          break;
        }

        const message = String(error?.message || '').toLowerCase();
        const isTimeout = message.includes('aborted due to timeout') || message.includes('timeout');
        if (!isTimeout) {
          break;
        }

        if (this.claimRetryDelayMs > 0) {
          await new Promise((resolve) => setTimeout(resolve, this.claimRetryDelayMs * (attempt + 1)));
        }
      }
    }

    throw lastError || new Error('Tournament room claim failed.');
  }

  buildRankingsFromSeatResults(roomSnapshot, seatResults) {
    const playersBySeat = new Map();
    const seats = (roomSnapshot && roomSnapshot.seats) || [];
    const players = (roomSnapshot && roomSnapshot.players) || [];

    for (const seat of seats) {
      playersBySeat.set(Number(seat.seatNo ?? seat.seat_no), {
        meta: seat.meta || {},
      });
    }

    for (const player of players) {
      const seatNo = Number(player.seat_no ?? player.seatNo);
      if (!playersBySeat.has(seatNo)) {
        playersBySeat.set(seatNo, player);
      }
    }

    return (seatResults || []).map((seatResult) => {
      const seatNo = Number(seatResult.seat_no);
      const roomPlayer = playersBySeat.get(seatNo);
      const tournamentEntryId =
        roomPlayer &&
        roomPlayer.meta &&
        (roomPlayer.meta.tournament_entry_id || roomPlayer.meta.tournamentEntryId);

      return {
        tournament_entry_id: tournamentEntryId,
        final_rank: Number(seatResult.final_rank),
        score: Number(seatResult.score || 0),
      };
    }).filter((row) => !!row.tournament_entry_id);
  }
}

module.exports = new TournamentLudoRoomService();
