<?php

namespace App\Http\Requests\Api\V1\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class JoinTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entries' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
