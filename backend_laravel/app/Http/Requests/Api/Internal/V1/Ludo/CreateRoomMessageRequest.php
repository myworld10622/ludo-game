<?php

namespace App\Http\Requests\Api\Internal\V1\Ludo;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'min:1'],
            'seat_no' => ['nullable', 'integer', 'min:1', 'max:8'],
            'sender_type' => ['required', 'string', 'in:human,bot,system'],
            'message_type' => ['required', 'string', 'in:text,system'],
            'message' => ['required', 'string', 'min:1', 'max:200'],
            'bot_code' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
