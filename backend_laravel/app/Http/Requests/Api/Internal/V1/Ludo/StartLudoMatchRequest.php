<?php

namespace App\Http\Requests\Api\Internal\V1\Ludo;

use Illuminate\Foundation\Http\FormRequest;

class StartLudoMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'string', 'max:100'],
            'node_room_id' => ['nullable', 'string', 'max:100'],
            'node_namespace' => ['nullable', 'string', 'max:100'],
            'server_seed' => ['nullable', 'string', 'max:120'],
            'prize_pool' => ['nullable', 'numeric', 'min:0'],
            'turn_state' => ['nullable', 'array'],
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.seat_no' => ['nullable', 'integer', 'min:1', 'max:8'],
            'seats.*.seatNo' => ['nullable', 'integer', 'min:1', 'max:8'],
            'seats.*.user_id' => ['nullable', 'integer', 'min:1'],
            'seats.*.userId' => ['nullable', 'integer', 'min:1'],
            'seats.*.player_type' => ['nullable', 'string', 'in:human,bot'],
            'seats.*.playerType' => ['nullable', 'string', 'in:human,bot'],
            'seats.*.display_name' => ['nullable', 'string', 'max:120'],
            'seats.*.displayName' => ['nullable', 'string', 'max:120'],
            'seats.*.bot_code' => ['nullable', 'string', 'max:120'],
            'seats.*.botCode' => ['nullable', 'string', 'max:120'],
        ];
    }
}
