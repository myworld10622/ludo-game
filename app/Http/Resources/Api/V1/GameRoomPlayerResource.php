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
            'display_name' => $this->user?->profile?->first_name ?: $this->user?->username ?: $this->bot_code,
            'joined_at' => optional($this->joined_at)->toIso8601String(),
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
        ];
    }
}
