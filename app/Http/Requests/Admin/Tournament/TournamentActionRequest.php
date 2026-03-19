<?php

namespace App\Http\Requests\Admin\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class TournamentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'meta' => ['nullable', 'array'],
            'queued' => ['nullable', 'boolean'],
            'round_no' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
