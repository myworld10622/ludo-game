<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameRoomPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'seat_no' => $this->seat_no,
            'player_type' => $this->player_type,
            'bot_code' => $this->bot_code,
            'status' => $this->status,
            'is_host' => $this->is_host,
            'score' => $this->score,
            'finish_position' => $this->finish_position,
            'payout_amount' => $this->payout_amount,
            'display_name' => $this->player_type === 'bot'
                ? $this->pseudoBotPublicId()
                : ((string) ($this->user?->user_code ?? $this->user?->username ?? "Player {$this->seat_no}")),
            'joined_at' => optional($this->joined_at)->toIso8601String(),
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
        ];
    }

    protected function pseudoBotPublicId(): string
    {
        $seed = 'room-bot|'.$this->id.'|'.$this->seat_no.'|'.$this->bot_code;
        $hash = abs(crc32($seed));

        return (string) (10000000 + ($hash % 90000000));
    }
}
