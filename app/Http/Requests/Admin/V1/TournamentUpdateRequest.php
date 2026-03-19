<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Validation\Rule;

class TournamentUpdateRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('prize_slabs')) {
            $this->merge([
                'prize_slabs' => collect($this->input('prize_slabs', []))
                    ->filter(fn ($slab) => filled($slab['rank_from'] ?? null) || filled($slab['rank_to'] ?? null) || filled($slab['prize_amount'] ?? null))
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tournamentId = $this->route('tournament')?->id;

        return [
            'game_id' => ['sometimes', 'integer', 'exists:games,id'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('tournaments', 'code')->ignore($tournamentId)],
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:150', Rule::unique('tournaments', 'slug')->ignore($tournamentId)],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'entry_open', 'entry_locked', 'seeding', 'running', 'cancelled', 'completed'])],
            'visibility' => ['sometimes', Rule::in(['public', 'private'])],
            'tournament_type' => ['sometimes', 'string', 'max:30'],
            'entry_fee' => ['sometimes', 'numeric', 'min:0'],
            'max_entries_per_user' => ['sometimes', 'integer', 'min:1'],
            'max_total_entries' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'min_players' => ['sometimes', 'integer', 'min:2'],
            'max_players' => ['sometimes', 'nullable', 'integer', 'min:2'],
            'match_size' => ['sometimes', 'integer', Rule::in([2, 4])],
            'advance_count' => ['sometimes', 'integer', 'min:1'],
            'bracket_size' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'bye_count' => ['sometimes', 'integer', 'min:0'],
            'seeding_strategy' => ['sometimes', Rule::in(['random', 'ranked', 'segmented'])],
            'bot_fill_policy' => ['sometimes', Rule::in(['fill_after_timeout', 'real_only', 'never_fill'])],
            'platform_fee' => ['sometimes', 'numeric', 'min:0'],
            'prize_pool' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'registration_starts_at' => ['sometimes', 'nullable', 'date'],
            'registration_ends_at' => ['sometimes', 'nullable', 'date'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'prize_slabs' => ['sometimes', 'array', 'min:1'],
            'prize_slabs.*.rank_from' => ['required_with:prize_slabs', 'integer', 'min:1'],
            'prize_slabs.*.rank_to' => ['required_with:prize_slabs', 'integer', 'min:1'],
            'prize_slabs.*.prize_type' => ['required_with:prize_slabs', 'string', 'max:30'],
            'prize_slabs.*.prize_amount' => ['required_with:prize_slabs', 'numeric', 'min:0'],
            'prize_slabs.*.currency' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('prize_slabs', []) as $index => $slab) {
                if (($slab['rank_to'] ?? 0) < ($slab['rank_from'] ?? 0)) {
                    $validator->errors()->add("prize_slabs.$index.rank_to", 'The rank_to value must be greater than or equal to rank_from.');
                }
            }
        });
    }
}
