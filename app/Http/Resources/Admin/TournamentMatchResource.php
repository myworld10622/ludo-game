<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'match_uuid' => $this->match_uuid,
            'tournament_id' => $this->tournament_id,
            'game_id' => $this->game_id,
            'round_no' => $this->round_no,
            'match_no' => $this->match_no,
            'bracket_position' => $this->bracket_position,
            'stage' => $this->stage,
            'status' => $this->status,
            'winner_entry_id' => $this->winner_entry_id,
            'max_players' => $this->max_players,
            'table_fee' => $this->table_fee,
            'node_room_id' => $this->node_room_id,
            'external_match_ref' => $this->external_match_ref,
            'scheduled_at' => optional($this->scheduled_at)->toIso8601String(),
            'started_at' => optional($this->started_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'settings' => $this->settings,
            'meta' => $this->meta,
            'entries' => TournamentMatchEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
