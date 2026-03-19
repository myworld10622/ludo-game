<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Validation\Rule;

class TournamentStoreRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'prize_slabs' => collect($this->input('prize_slabs', []))
                ->filter(fn ($slab) => filled($slab['rank_from'] ?? null) || filled($slab['rank_to'] ?? null) || filled($slab['prize_amount'] ?? null))
                ->values()
                ->all(),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('tournaments', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('tournaments', 'slug')],
            'status' => ['required', Rule::in(['draft', 'published', 'entry_open', 'entry_locked', 'seeding', 'running', 'cancelled', 'completed'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'tournament_type' => ['required', 'string', 'max:30'],
            'entry_fee' => ['required', 'numeric', 'min:0'],
            'max_entries_per_user' => ['required', 'integer', 'min:1'],
            'max_total_entries' => ['nullable', 'integer', 'min:1'],
            'min_players' => ['nullable', 'integer', 'min:2'],
            'max_players' => ['nullable', 'integer', 'min:2'],
            'match_size' => ['nullable', 'integer', Rule::in([2, 4])],
            'advance_count' => ['nullable', 'integer', 'min:1'],
            'bracket_size' => ['nullable', 'integer', 'min:1'],
            'bye_count' => ['nullable', 'integer', 'min:0'],
            'seeding_strategy' => ['nullable', Rule::in(['random', 'ranked', 'segmented'])],
            'bot_fill_policy' => ['nullable', Rule::in(['fill_after_timeout', 'real_only', 'never_fill'])],
            'platform_fee' => ['nullable', 'numeric', 'min:0'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after_or_equal:registration_starts_at'],
            'starts_at' => ['required', 'date', 'after_or_equal:registration_starts_at'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'prize_slabs' => ['required', 'array', 'min:1'],
            'prize_slabs.*.rank_from' => ['required', 'integer', 'min:1'],
            'prize_slabs.*.rank_to' => ['required', 'integer', 'min:1'],
            'prize_slabs.*.prize_type' => ['required', 'string', 'max:30'],
            'prize_slabs.*.prize_amount' => ['required', 'numeric', 'min:0'],
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
