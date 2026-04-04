<?php

namespace App\Http\Requests\Api\V1\Ludo;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ListRoomMessagesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
