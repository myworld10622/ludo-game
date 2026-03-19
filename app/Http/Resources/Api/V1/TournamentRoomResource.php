<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid ?? $this->room_uuid,
            'room_uuid' => $this->room_uuid ?? $this->uuid,
            'game_id' => $this->game_id,
            'mode' => $this->mode ?? $this->play_mode,
            'play_mode' => $this->play_mode ?? $this->mode,
            'status' => $this->status,
            'max_players' => $this->max_players,
            'entry_fee' => $this->entry_fee,
            'current_players' => $this->current_players,
            'current_real_players' => $this->current_real_players,
            'current_bot_players' => $this->current_bot_players,
            'meta' => $this->meta,
            'players' => $this->whenLoaded('players', fn () => $this->players->map(static fn ($player) => [
                'id' => $player->id,
                'user_id' => $player->user_id,
                'seat_no' => $player->seat_no,
                'player_type' => $player->player_type,
                'status' => $player->status,
                'display_name' => $player->display_name ?? ($player->meta['display_name'] ?? null),
                'meta' => $player->meta,
            ])->values()),
        ];
    }
}
