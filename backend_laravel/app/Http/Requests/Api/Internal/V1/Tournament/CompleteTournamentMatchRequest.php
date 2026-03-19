<?php

namespace App\Http\Requests\Api\Internal\V1\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTournamentMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rankings' => ['required', 'array', 'min:1'],
            'rankings.*.tournament_entry_id' => ['required', 'integer', 'exists:tournament_entries,id'],
            'rankings.*.final_rank' => ['required', 'integer', 'min:1'],
            'rankings.*.score' => ['nullable', 'numeric'],
        ];
    }
}
