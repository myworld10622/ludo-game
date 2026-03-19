'use strict';

class TournamentLudoLaravelSyncService {
  constructor() {
    this.enabled = String(process.env.LUDO_LARAVEL_SYNC_ENABLED || '').toLowerCase() === 'true';
    this.baseUrl = (process.env.LARAVEL_INTERNAL_BASE_URL || '').replace(/\/$/, '');
    this.internalToken = process.env.INTERNAL_API_TOKEN || '';
    this.completeTimeoutMs = Math.max(10000, Number(process.env.LUDO_TOURNAMENT_COMPLETE_TIMEOUT_MS || '60000'));
    this.completeRetries = Math.max(0, Number(process.env.LUDO_TOURNAMENT_COMPLETE_RETRIES || '3'));
    this.completeRetryDelayMs = Math.max(0, Number(process.env.LUDO_TOURNAMENT_COMPLETE_RETRY_DELAY_MS || '500'));
  }

  isConfigured() {
    return this.enabled && !!this.baseUrl && !!this.internalToken;
  }

  async completeTournamentRoom(roomUuid, rankings) {
    if (!this.isConfigured()) {
      return { skipped: true, reason: 'Tournament Laravel sync is not configured.' };
    }

    const url = `${this.baseUrl}/tournaments/ludo/rooms/${roomUuid}/complete`;
    let lastError = null;

    for (let attempt = 0; attempt <= this.completeRetries; attempt += 1) {
      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${this.internalToken}`,
          },
          body: JSON.stringify({ rankings }),
          signal: AbortSignal.timeout(this.completeTimeoutMs),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          throw new Error(
            data?.message ||
              `Tournament Laravel sync failed with status ${response.status}.`
          );
        }

        return data;
      } catch (error) {
        lastError = error;

        if (attempt >= this.completeRetries) {
          break;
        }

        const message = String(error?.message || '').toLowerCase();
        const isRetriable =
          message.includes('fetch failed') ||
          message.includes('aborted due to timeout') ||
          message.includes('timeout') ||
          message.includes('deadlock') ||
          message.includes('lock wait timeout') ||
          message.includes('500') ||
          message.includes('unable to settle tournament ludo match');

        if (!isRetriable) {
          break;
        }

        if (this.completeRetryDelayMs > 0) {
          await new Promise((resolve) => setTimeout(resolve, this.completeRetryDelayMs * (attempt + 1)));
        }
      }
    }

    throw lastError || new Error('Tournament Laravel sync failed.');
  }
}

module.exports = new TournamentLudoLaravelSyncService();
