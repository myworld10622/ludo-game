<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

class GameResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_visible' => (bool) $this->is_visible,
            'tournaments_enabled' => (bool) $this->tournaments_enabled,
            'sort_order' => $this->sort_order,
            'launch_type' => $this->launch_type,
            'client_route' => $this->client_route,
            'socket_namespace' => $this->socket_namespace,
            'icon_url' => $this->icon_url,
            'banner_url' => $this->banner_url,
            'metadata' => $this->metadata,
            'published_at' => optional($this->published_at)?->toIso8601String(),
        ];
    }
}
