<?php

namespace App\Http\Requests\Admin\V1;

use Illuminate\Validation\Rule;

class GameUpdateRequest extends AdminFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gameId = $this->route('game')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:50', 'alpha_dash', Rule::unique('games', 'code')->ignore($gameId)],
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('games', 'slug')->ignore($gameId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_visible' => ['sometimes', 'boolean'],
            'tournaments_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'launch_type' => ['sometimes', Rule::in(['internal', 'external', 'node_room'])],
            'client_route' => ['sometimes', 'nullable', 'string', 'max:150'],
            'socket_namespace' => ['sometimes', 'nullable', 'string', 'max:150'],
            'icon_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'banner_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
