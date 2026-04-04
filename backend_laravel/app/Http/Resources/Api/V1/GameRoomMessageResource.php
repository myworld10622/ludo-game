<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameRoomMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $displayName = $this->sender_type === 'bot'
            ? (string) ($meta['display_name'] ?? $meta['bot_code'] ?? "Bot {$this->seat_no}")
            : (string) ($this->user?->user_code ?? $this->user?->username ?? $meta['display_name'] ?? "Player {$this->seat_no}");

        return [
            'message_id' => $this->message_uuid,
            'room_id' => $this->room?->room_uuid ?? $meta['room_uuid'] ?? null,
            'match_uuid' => $this->match?->match_uuid,
            'message_type' => $this->message_type,
            'sender_type' => $this->sender_type,
            'message' => $this->content,
            'status' => $this->status,
            'sender' => [
                'user_id' => $this->user_id,
                'seat_no' => $this->seat_no,
                'display_name' => $displayName,
                'player_id' => $this->user?->user_code,
                'avatar' => $this->user?->profile?->avatar_url,
                'bot_code' => $meta['bot_code'] ?? null,
            ],
            'meta' => $meta,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
