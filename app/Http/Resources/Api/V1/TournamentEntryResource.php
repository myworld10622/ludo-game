<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => (string) ($this->uuid ?? $this->entry_uuid ?? $this->id),
            'tournament_id' => $this->tournament_id,
            'user_id' => $this->user_id,
            'entry_no' => $this->entry_no,
            'ticket_no' => $this->ticket_no,
            'entry_index_for_user' => $this->entry_index_for_user,
            'status' => $this->status,
            'entry_fee' => $this->entry_fee,
            'joined_at' => optional($this->joined_at ?? $this->created_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}
