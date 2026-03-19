<?php

namespace App\Http\Requests\Api\Internal\V1\Ludo;

use Illuminate\Foundation\Http\FormRequest;

class CompleteLudoMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancelled' => ['nullable', 'boolean'],
            'winner' => ['nullable', 'array'],
            'winner.seat_no' => ['nullable', 'integer', 'min:1', 'max:8'],
            'winner.user_id' => ['nullable', 'integer', 'min:1'],
            'placements' => ['nullable', 'array'],
            'placements.*.seat_no' => ['required_with:placements', 'integer', 'min:1', 'max:8'],
            'placements.*.finish_position' => ['nullable', 'integer', 'min:1', 'max:8'],
            'placements.*.score' => ['nullable', 'integer', 'min:0'],
            'placements.*.is_winner' => ['nullable', 'boolean'],
            'placements.*.payout_amount' => ['nullable', 'numeric', 'min:0'],
            'placements.*.stats' => ['nullable', 'array'],
            'result_payload' => ['nullable', 'array'],
        ];
    }
}
