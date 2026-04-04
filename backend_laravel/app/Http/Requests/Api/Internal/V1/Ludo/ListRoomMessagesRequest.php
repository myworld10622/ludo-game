<?php

namespace App\Http\Requests\Api\Internal\V1\Ludo;

use Illuminate\Foundation\Http\FormRequest;

class ListRoomMessagesRequest extends FormRequest
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
