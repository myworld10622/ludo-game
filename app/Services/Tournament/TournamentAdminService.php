<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentAdminService
{
    public function __construct(
        private readonly TournamentRulesService $rulesService
    ) {
    }

    public function create(array $payload, ?int $adminId = null): Tournament
    {
        $this->rulesService->validateDefinition($payload);
        $this->rulesService->validatePrizeRules($payload['prizes'] ?? []);

        return DB::transaction(function () use ($payload, $adminId): Tournament {
            $tournament = Tournament::create([
                'uuid' => (string) Str::uuid(),
                'game_id' => $payload['game_id'],
                'slug' => $payload['slug'],
                'name' => $payload['name'],
                'code' => $payload['code'],
                'type' => $payload['type'] ?? 'standard',
                'status' => $payload['status'] ?? 'draft',
                'currency' => $payload['currency'] ?? 'chips',
                'entry_fee' => $payload['entry_fee'] ?? 0,
                'allow_multiple_entries' => $payload['allow_multiple_entries'] ?? false,
                'max_entries_per_user' => $payload['max_entries_per_user'] ?? 1,
                'min_total_entries' => $payload['min_total_entries'] ?? 2,
                'max_total_entries' => $payload['max_total_entries'] ?? null,
                'ticket_prefix' => $payload['ticket_prefix'] ?? null,
                'entry_open_at' => $payload['entry_open_at'] ?? null,
                'entry_close_at' => $payload['entry_close_at'] ?? null,
                'start_at' => $payload['start_at'] ?? null,
                'end_at' => $payload['end_at'] ?? null,
                'rules' => $payload['rules'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'created_by_admin_id' => $adminId,
                'updated_by_admin_id' => $adminId,
            ]);

            foreach ($payload['prizes'] ?? [] as $prize) {
                $tournament->prizes()->create([
                    'rank_from' => $prize['rank_from'],
                    'rank_to' => $prize['rank_to'],
                    'prize_type' => $prize['prize_type'] ?? 'fixed',
                    'prize_amount' => $prize['prize_amount'] ?? 0,
                    'prize_percent' => $prize['prize_percent'] ?? null,
                    'meta' => $prize['meta'] ?? null,
                ]);
            }

            return $tournament->load(['game', 'prizes']);
        });
    }

    public function update(Tournament $tournament, array $payload, ?int $adminId = null): Tournament
    {
        $mergedPayload = array_merge($tournament->toArray(), $payload);
        $mergedPayload['prizes'] = $payload['prizes'] ?? $tournament->prizes()->get(['rank_from', 'rank_to', 'prize_type', 'prize_amount', 'prize_percent', 'meta'])->toArray();

        $this->rulesService->validateDefinition($mergedPayload);
        $this->rulesService->validatePrizeRules($mergedPayload['prizes'] ?? []);

        return DB::transaction(function () use ($tournament, $payload, $adminId): Tournament {
            $tournament->fill([
                'game_id' => $payload['game_id'] ?? $tournament->game_id,
                'slug' => $payload['slug'] ?? $tournament->slug,
                'name' => $payload['name'] ?? $tournament->name,
                'code' => $payload['code'] ?? $tournament->code,
                'type' => $payload['type'] ?? $tournament->type,
                'status' => $payload['status'] ?? $tournament->status,
                'currency' => $payload['currency'] ?? $tournament->currency,
                'entry_fee' => $payload['entry_fee'] ?? $tournament->entry_fee,
                'allow_multiple_entries' => $payload['allow_multiple_entries'] ?? $tournament->allow_multiple_entries,
                'max_entries_per_user' => $payload['max_entries_per_user'] ?? $tournament->max_entries_per_user,
                'min_total_entries' => $payload['min_total_entries'] ?? $tournament->min_total_entries,
                'max_total_entries' => $payload['max_total_entries'] ?? $tournament->max_total_entries,
                'ticket_prefix' => $payload['ticket_prefix'] ?? $tournament->ticket_prefix,
                'entry_open_at' => $payload['entry_open_at'] ?? $tournament->entry_open_at,
                'entry_close_at' => $payload['entry_close_at'] ?? $tournament->entry_close_at,
                'start_at' => $payload['start_at'] ?? $tournament->start_at,
                'end_at' => $payload['end_at'] ?? $tournament->end_at,
                'rules' => $payload['rules'] ?? $tournament->rules,
                'meta' => $payload['meta'] ?? $tournament->meta,
                'updated_by_admin_id' => $adminId,
            ]);
            $tournament->save();

            if (array_key_exists('prizes', $payload)) {
                $tournament->prizes()->delete();

                foreach ($payload['prizes'] ?? [] as $prize) {
                    $tournament->prizes()->create([
                        'rank_from' => $prize['rank_from'],
                        'rank_to' => $prize['rank_to'],
                        'prize_type' => $prize['prize_type'] ?? 'fixed',
                        'prize_amount' => $prize['prize_amount'] ?? 0,
                        'prize_percent' => $prize['prize_percent'] ?? null,
                        'meta' => $prize['meta'] ?? null,
                    ]);
                }
            }

            return $tournament->load(['game', 'prizes']);
        });
    }
}
