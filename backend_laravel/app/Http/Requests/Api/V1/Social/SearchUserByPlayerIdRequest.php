<?php

namespace App\Http\Requests\Api\V1\Social;

use App\Http\Requests\Api\V1\ApiFormRequest;

class SearchUserByPlayerIdRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'playerId' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    public function validationData(): array
    {
        return array_merge($this->all(), [
            'playerId' => $this->route('playerId'),
        ]);
    }
}
