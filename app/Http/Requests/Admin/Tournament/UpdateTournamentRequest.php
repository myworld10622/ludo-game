<?php

namespace App\Http\Requests\Admin\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tournament = $this->route('tournament');
        $tournamentId = $tournament?->id;

        return [
            'game_id' => ['sometimes', 'exists:games,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('tournaments', 'slug')->ignore($tournamentId)],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('tournaments', 'code')->ignore($tournamentId)],
            'type' => ['sometimes', 'string', 'max:64'],
            'status' => ['sometimes', 'string', 'max:64'],
            'currency' => ['sometimes', 'string', 'max:16'],
            'entry_fee' => ['sometimes', 'numeric', 'min:0'],
            'allow_multiple_entries' => ['sometimes', 'boolean'],
            'max_entries_per_user' => ['sometimes', 'integer', 'min:1'],
            'min_total_entries' => ['sometimes', 'integer', 'min:1'],
            'max_total_entries' => ['nullable', 'integer', 'min:1'],
            'ticket_prefix' => ['nullable', 'string', 'max:32'],
            'entry_open_at' => ['nullable', 'date'],
            'entry_close_at' => ['nullable', 'date'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'rules' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'prizes' => ['nullable', 'array'],
            'prizes.*.rank_from' => ['required_with:prizes', 'integer', 'min:1'],
            'prizes.*.rank_to' => ['required_with:prizes', 'integer', 'min:1'],
            'prizes.*.prize_type' => ['nullable', 'string', 'max:32'],
            'prizes.*.prize_amount' => ['nullable', 'numeric', 'min:0'],
            'prizes.*.prize_percent' => ['nullable', 'numeric', 'min:0'],
            'prizes.*.meta' => ['nullable', 'array'],
        ];
    }
}
