<?php

namespace App\Http\Requests\Api\V1\Social;

use App\Http\Requests\Api\V1\ApiFormRequest;

class RespondFriendRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['sometimes', 'string', 'in:accept,reject'],
        ];
    }
}
