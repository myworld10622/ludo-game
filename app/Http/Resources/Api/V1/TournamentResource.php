<?php

namespace App\Http\Resources\Api\V1;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $entryOpenAt = $this->entry_open_at ?? $this->registration_starts_at;
        $entryCloseAt = $this->entry_close_at ?? $this->registration_ends_at;
        $now = CarbonImmutable::now();
        $isOpenByStatus = in_array((string) $this->status, ['published', 'entry_open'], true);
        $isPastOpenAt = ! $entryOpenAt || $now->greaterThanOrEqualTo(CarbonImmutable::parse($entryOpenAt));
        $isBeforeCloseAt = ! $entryCloseAt || $now->lessThanOrEqualTo(CarbonImmutable::parse($entryCloseAt));
        $canJoin = $isOpenByStatus && $isPastOpenAt && $isBeforeCloseAt;

        return [
            'id' => $this->id,
            'uuid' => (string) ($this->uuid ?? $this->id),
            'game_id' => $this->game_id,
            'game' => $this->whenLoaded('game', fn () => [
                'id' => $this->game->id,
                'game' => $this->game->code ?? $this->game->slug ?? $this->game->name,
                'name' => $this->game->name,
                'slug' => $this->game->slug,
            ]),
            'slug' => $this->slug,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type ?? $this->tournament_type,
            'status' => $this->status,
            'currency' => $this->currency,
            'entry_fee' => $this->entry_fee,
            'allow_multiple_entries' => $this->allow_multiple_entries ?? $this->allow_re_entry ?? false,
            'max_entries_per_user' => $this->max_entries_per_user,
            'min_total_entries' => $this->min_total_entries ?? $this->min_players,
            'max_total_entries' => $this->max_total_entries,
            'match_size' => $this->match_size ?? data_get($this->rules, 'players_per_match') ?? data_get($this->meta, 'max_players') ?? 4,
            'advance_count' => $this->advance_count ?? data_get($this->rules, 'advance_count') ?? data_get($this->meta, 'advance_count') ?? 1,
            'bracket_size' => $this->bracket_size,
            'bye_count' => $this->bye_count ?? 0,
            'seeding_strategy' => $this->seeding_strategy ?? 'random',
            'bot_fill_policy' => $this->bot_fill_policy ?? 'fill_after_timeout',
            'ticket_prefix' => $this->ticket_prefix ?? strtoupper(substr((string) $this->code, 0, 8)),
            'current_total_entries' => $this->current_total_entries ?? 0,
            'current_active_entries' => $this->current_active_entries ?? $this->current_total_entries ?? 0,
            'entry_open_at' => optional($this->entry_open_at ?? $this->registration_starts_at)->toIso8601String(),
            'entry_close_at' => optional($this->entry_close_at ?? $this->registration_ends_at)->toIso8601String(),
            'can_join' => $canJoin,
            'start_at' => optional($this->start_at ?? $this->starts_at)->toIso8601String(),
            'end_at' => optional($this->end_at ?? $this->ends_at)->toIso8601String(),
            'rules' => $this->rules,
            'meta' => $this->meta ?? $this->metadata,
            'prizes' => \App\Http\Resources\Admin\TournamentPrizeResource::collection($this->whenLoaded('prizes')),
        ];
    }
}
