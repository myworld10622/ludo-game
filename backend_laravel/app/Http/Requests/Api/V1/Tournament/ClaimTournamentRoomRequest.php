<?php

namespace App\Http\Requests\Api\V1\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class ClaimTournamentRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tournament_entry_uuid' => ['required', 'uuid'],
        ];
    }
}
