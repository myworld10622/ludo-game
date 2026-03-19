<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tournament_id' => $this->tournament_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name ?? null,
                'mobile' => $this->user->mobile ?? null,
            ]),
            'entry_no' => $this->entry_no,
            'ticket_no' => $this->ticket_no,
            'entry_index_for_user' => $this->entry_index_for_user,
            'status' => $this->status,
            'entry_fee' => $this->entry_fee,
            'joined_at' => optional($this->joined_at)->toIso8601String(),
            'checked_in_at' => optional($this->checked_in_at)->toIso8601String(),
            'eliminated_at' => optional($this->eliminated_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'meta' => $this->meta,
        ];
    }
}
