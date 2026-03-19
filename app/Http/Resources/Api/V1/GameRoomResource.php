<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $countdownRemaining = null;

        if ($this->fill_bots_at) {
            $countdownRemaining = max(0, now()->diffInSeconds($this->fill_bots_at, false));
        }

        return [
            'id' => $this->id,
            'room_uuid' => $this->room_uuid,
            'game_id' => $this->game_id,
            'game_slug' => $this->game?->slug,
            'queue_key' => $this->queue_key,
            'room_type' => $this->room_type,
            'play_mode' => $this->play_mode,
            'game_mode' => $this->game_mode,
            'status' => $this->status,
            'max_players' => $this->max_players,
            'min_real_players' => $this->min_real_players,
            'current_players' => $this->current_players,
            'current_real_players' => $this->current_real_players,
            'current_bot_players' => $this->current_bot_players,
            'entry_fee' => $this->entry_fee,
            'prize_pool' => $this->prize_pool,
            'allow_bots' => $this->allow_bots,
            'started_with_bots' => $this->started_with_bots,
            'bot_fill_after_seconds' => $this->bot_fill_after_seconds,
            'countdown_remaining' => $countdownRemaining,
            'node_namespace' => $this->node_namespace,
            'node_room_id' => $this->node_room_id,
            'fill_bots_at' => optional($this->fill_bots_at)->toIso8601String(),
            'started_at' => optional($this->started_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'players' => GameRoomPlayerResource::collection($this->whenLoaded('players')),
        ];
    }
}
