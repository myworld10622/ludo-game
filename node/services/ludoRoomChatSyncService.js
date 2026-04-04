class LudoRoomChatSyncService {
  constructor(config = {}) {
    this.config = {
      enabled: config.enabled ?? process.env.LUDO_LARAVEL_SYNC_ENABLED === "true",
      baseUrl:
        config.baseUrl ??
        process.env.LARAVEL_INTERNAL_BASE_URL ??
        "http://127.0.0.1:8000/api/internal/v1",
      internalToken: config.internalToken ?? process.env.INTERNAL_API_TOKEN ?? "",
      timeoutMs: Math.max(5000, Number(config.timeoutMs ?? process.env.LUDO_CHAT_SYNC_TIMEOUT_MS ?? "15000")),
    };
  }

  isEnabled() {
    return Boolean(this.config.enabled && this.config.baseUrl && this.config.internalToken);
  }

  async fetchRoomMessages(roomId, limit = 50) {
    if (!this.isEnabled() || !roomId) {
      return [];
    }

    const response = await fetch(
      `${this.config.baseUrl}/ludo/rooms/${encodeURIComponent(roomId)}/messages?limit=${Math.max(1, Math.min(100, Number(limit) || 50))}`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
          "X-Internal-Token": this.config.internalToken,
        },
        signal: AbortSignal.timeout(this.config.timeoutMs),
      }
    );

    if (!response.ok) {
      const body = await response.text();
      throw new Error(`Laravel room-message history sync failed: ${response.status} ${body}`);
    }

    const payload = await response.json();
    return Array.isArray(payload?.data) ? payload.data : [];
  }

  async createRoomMessage(roomId, messagePayload = {}) {
    if (!this.isEnabled() || !roomId) {
      return null;
    }

    const response = await fetch(`${this.config.baseUrl}/ludo/rooms/${encodeURIComponent(roomId)}/messages`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Internal-Token": this.config.internalToken,
      },
      body: JSON.stringify(messagePayload),
      signal: AbortSignal.timeout(this.config.timeoutMs),
    });

    if (!response.ok) {
      const body = await response.text();
      throw new Error(`Laravel room-message create sync failed: ${response.status} ${body}`);
    }

    const payload = await response.json();
    return payload?.data ?? null;
  }
}

module.exports = LudoRoomChatSyncService;
