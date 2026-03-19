<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Validation\Rule;

class GameStoreRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('games', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('games', 'slug')],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
            'tournaments_enabled' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'launch_type' => ['required', Rule::in(['internal', 'external', 'node_room'])],
            'client_route' => ['nullable', 'string', 'max:150'],
            'socket_namespace' => ['nullable', 'string', 'max:150'],
            'icon_url' => ['nullable', 'url', 'max:2048'],
            'banner_url' => ['nullable', 'url', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
