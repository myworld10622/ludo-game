<?php

namespace App\Http\Requests\Api\V1\Agora;

use App\Http\Requests\Api\V1\ApiFormRequest;

class IssueAgoraTokenRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'max:64'],
            'uid' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
