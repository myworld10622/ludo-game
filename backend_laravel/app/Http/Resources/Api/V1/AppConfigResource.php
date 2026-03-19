<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AppConfigResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        /** @var Collection $games */
        $games = collect($this->resource['enabled_games'] ?? []);

        return [
            'app_version' => $this->resource['app_version'] ?? config('app.public_version'),
            'enabled_games' => $games->map(fn ($game) => [
                'code' => $game['code'] ?? null,
                'name' => $game['name'] ?? null,
                'slug' => $game['slug'] ?? null,
                'launch_type' => $game['launch_type'] ?? null,
            ])->values()->all(),
            'maintenance' => $this->resource['maintenance'] ?? [
                'api_enabled' => false,
                'gameplay_enabled' => false,
            ],
            'features' => [
                'tournaments_enabled' => (bool) ($this->resource['tournaments_enabled'] ?? false),
            ],
            'tournament_feature_availability' => (bool) ($this->resource['tournaments_enabled'] ?? false),
        ];
    }
}
