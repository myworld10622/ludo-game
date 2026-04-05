<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(['google', 'facebook', 'instagram'])],
            'provider_user_id' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'string', 'max:1000'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
