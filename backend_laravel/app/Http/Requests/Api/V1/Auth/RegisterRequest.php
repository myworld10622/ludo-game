<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'referral_code' => ['nullable', 'string', 'max:32', Rule::exists('users', 'referral_code')],
            'profile.first_name' => ['nullable', 'string', 'max:100'],
            'profile.last_name' => ['nullable', 'string', 'max:100'],
            'profile.date_of_birth' => ['nullable', 'date'],
            'profile.gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'profile.country_code' => ['nullable', 'string', 'max:5'],
            'profile.state' => ['nullable', 'string', 'max:100'],
            'profile.city' => ['nullable', 'string', 'max:100'],
            'profile.avatar_url' => ['nullable', 'url', 'max:2048'],
            'profile.language' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('email') && ! $this->filled('mobile')) {
                $validator->errors()->add('identity', 'Either email or mobile is required.');
            }
        });
    }
}
