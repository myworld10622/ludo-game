<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'tournament_entry_id' => $this->tournament_entry_id,
            'game_match_id' => $this->game_match_id,
            'external_match_uuid' => $this->external_match_uuid,
            'round_no' => $this->round_no,
            'table_no' => $this->table_no,
            'status' => $this->status,
            'meta' => $this->meta,
            'entry' => $this->whenLoaded('entry', fn () => [
                'id' => $this->entry->id,
                'uuid' => $this->entry->uuid,
                'ticket_no' => $this->entry->ticket_no,
                'user_id' => $this->entry->user_id,
            ]),
        ];
    }
}
