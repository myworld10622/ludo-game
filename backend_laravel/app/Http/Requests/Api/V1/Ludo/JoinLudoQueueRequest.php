<?php

namespace App\Http\Requests\Api\V1\Ludo;

use App\Http\Requests\Api\V1\ApiFormRequest;

class JoinLudoQueueRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'room_type' => ['sometimes', 'string', 'in:public,private,tournament,practice'],
            'play_mode' => ['sometimes', 'string', 'in:cash,practice,tournament'],
            'game_mode' => ['sometimes', 'string', 'max:50'],
            'max_players' => ['sometimes', 'integer', 'in:2,4'],
            'entry_fee' => ['sometimes', 'numeric', 'min:0'],
            'allow_bots' => ['sometimes', 'boolean'],
        ];
    }
}
