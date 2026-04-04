<?php

namespace App\Http\Requests\Api\V1\Social;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SendFriendRequestByPlayerIdRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id' => ['required', 'string', 'min:3', 'max:100'],
            'source' => ['sometimes', 'string', 'in:room,lobby_search,profile'],
            'source_room_uuid' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:160'],
        ];
    }
}
