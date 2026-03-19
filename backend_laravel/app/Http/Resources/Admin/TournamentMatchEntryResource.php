<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_match_id' => $this->tournament_match_id,
            'tournament_entry_id' => $this->tournament_entry_id,
            'user_id' => $this->user_id,
            'seat_no' => $this->seat_no,
            'position' => $this->position,
            'score' => $this->score,
            'is_winner' => $this->is_winner,
            'status' => $this->status,
            'stats' => $this->stats,
            'joined_at' => optional($this->joined_at)->toIso8601String(),
            'finished_at' => optional($this->finished_at)->toIso8601String(),
            'entry' => $this->whenLoaded('tournamentEntry', fn () => [
                'id' => $this->tournamentEntry->id,
                'uuid' => $this->tournamentEntry->uuid,
                'ticket_no' => $this->tournamentEntry->ticket_no,
                'user_id' => $this->tournamentEntry->user_id,
                'status' => $this->tournamentEntry->status,
            ]),
        ];
    }
}
