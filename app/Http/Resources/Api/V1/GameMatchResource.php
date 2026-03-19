<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'match_uuid' => $this->match_uuid,
            'status' => $this->status,
            'mode' => $this->mode,
            'max_players' => $this->max_players,
            'real_players' => $this->real_players,
            'bot_players' => $this->bot_players,
            'entry_fee' => (string) $this->entry_fee,
            'prize_pool' => (string) $this->prize_pool,
            'node_namespace' => $this->node_namespace,
            'node_room_id' => $this->node_room_id,
            'winner_user_id' => $this->winner_user_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'room' => $this->whenLoaded('room', function () {
                return [
                    'room_uuid' => $this->room?->room_uuid,
                    'status' => $this->room?->status,
                ];
            }),
            'players' => $this->whenLoaded('players', function () {
                return $this->players->map(function ($player) {
                    return [
                        'seat_no' => $player->seat_no,
                        'user_id' => $player->user_id,
                        'player_type' => $player->player_type,
                        'bot_code' => $player->bot_code,
                        'finish_position' => $player->finish_position,
                        'score' => $player->score,
                        'is_winner' => $player->is_winner,
                        'payout_amount' => (string) $player->payout_amount,
                        'status' => $player->status,
                    ];
                })->values()->all();
            }),
        ];
    }
}
