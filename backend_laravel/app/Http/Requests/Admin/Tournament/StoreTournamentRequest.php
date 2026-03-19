<?php

namespace App\Http\Requests\Admin\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => ['required', 'exists:games,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tournaments,slug'],
            'code' => ['required', 'string', 'max:255', 'unique:tournaments,code'],
            'type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:16'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'allow_multiple_entries' => ['nullable', 'boolean'],
            'max_entries_per_user' => ['nullable', 'integer', 'min:1'],
            'min_total_entries' => ['nullable', 'integer', 'min:1'],
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
