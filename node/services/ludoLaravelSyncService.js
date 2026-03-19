class LudoLaravelSyncService {
  constructor(config = {}) {
    this.config = {
      enabled: config.enabled ?? process.env.LUDO_LARAVEL_SYNC_ENABLED === "true",
      baseUrl: config.baseUrl ?? process.env.LARAVEL_INTERNAL_BASE_URL ?? "http://127.0.0.1:8000/api/internal/v1",
      internalToken: config.internalToken ?? process.env.INTERNAL_API_TOKEN ?? "",
      startTimeoutMs: Math.max(10000, Number(config.startTimeoutMs ?? process.env.LUDO_MATCH_START_TIMEOUT_MS ?? "60000")),
      startRetries: Math.max(0, Number(config.startRetries ?? process.env.LUDO_MATCH_START_RETRIES ?? "3")),
      startRetryDelayMs: Math.max(0, Number(config.startRetryDelayMs ?? process.env.LUDO_MATCH_START_RETRY_DELAY_MS ?? "500")),
    };
  }

  isEnabled() {
    return Boolean(this.config.enabled && this.config.baseUrl && this.config.internalToken);
  }

  async notifyMatchStarted(room) {
    if (!this.isEnabled() || !room?.roomId) {
      return null;
    }

    let lastError = null;

    for (let attempt = 0; attempt <= this.config.startRetries; attempt += 1) {
      try {
        const response = await fetch(`${this.config.baseUrl}/ludo/rooms/${room.roomId}/start`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-Internal-Token": this.config.internalToken,
          },
          body: JSON.stringify({
            room_id: room.roomId,
            node_room_id: room.roomId,
            node_namespace: "/ludo_v2",
            prize_pool: room.prizePool ?? 0,
            seats: room.seats ?? [],
          }),
          signal: AbortSignal.timeout(this.config.startTimeoutMs),
        });

        if (!response.ok) {
          const body = await response.text();
          throw new Error(`Laravel match-start sync failed: ${response.status} ${body}`);
        }

        const payload = await response.json();
        return payload?.data ?? null;
      } catch (error) {
        lastError = error;
        const message = String(error?.message || "").toLowerCase();
        const isRetriable =
          message.includes("fetch failed") ||
          message.includes("timeout") ||
          message.includes("aborted due to timeout") ||
          message.includes("deadlock") ||
          message.includes("lock wait timeout") ||
          message.includes("unable to persist ludo match start") ||
          message.includes("500");

        if (!isRetriable || attempt >= this.config.startRetries) {
          break;
        }

        if (this.config.startRetryDelayMs > 0) {
          await new Promise((resolve) => setTimeout(resolve, this.config.startRetryDelayMs * (attempt + 1)));
        }
      }
    }

    throw lastError || new Error("Laravel match-start sync failed.");
  }

  async notifyMatchCompleted(matchUuid, payload = {}) {
    if (!this.isEnabled() || !matchUuid) {
      return null;
    }

    const response = await fetch(`${this.config.baseUrl}/ludo/matches/${matchUuid}/complete`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Internal-Token": this.config.internalToken,
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      const body = await response.text();
      throw new Error(`Laravel match-complete sync failed: ${response.status} ${body}`);
    }

    const completePayload = await response.json();
    return completePayload?.data ?? null;
  }
}

module.exports = LudoLaravelSyncService;
