<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'game_id' => $this->game_id,
            'game' => $this->whenLoaded('game', fn () => [
                'id' => $this->game->id,
                'game' => $this->game->game ?? $this->game->code ?? null,
                'name' => $this->game->name,
            ]),
            'slug' => $this->slug,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,
            'currency' => $this->currency,
            'entry_fee' => $this->entry_fee,
            'allow_multiple_entries' => $this->allow_multiple_entries,
            'max_entries_per_user' => $this->max_entries_per_user,
            'min_total_entries' => $this->min_total_entries,
            'max_total_entries' => $this->max_total_entries,
            'ticket_prefix' => $this->ticket_prefix,
            'current_total_entries' => $this->current_total_entries,
            'current_active_entries' => $this->current_active_entries,
            'entry_open_at' => optional($this->entry_open_at)->toIso8601String(),
            'entry_close_at' => optional($this->entry_close_at)->toIso8601String(),
            'start_at' => optional($this->start_at)->toIso8601String(),
            'end_at' => optional($this->end_at)->toIso8601String(),
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'cancelled_at' => optional($this->cancelled_at)->toIso8601String(),
            'rules' => $this->rules,
            'meta' => $this->meta,
            'prizes' => TournamentPrizeResource::collection($this->whenLoaded('prizes')),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
