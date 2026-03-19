<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppConfigResource;
use App\Models\Game;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $enabledGames = Game::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get(['code', 'name', 'slug', 'launch_type'])
            ->toArray();

        return $this->successResponse(
            new AppConfigResource([
                'app_version' => config('app.public_version'),
                'enabled_games' => $enabledGames,
                'maintenance' => [
                    'api_enabled' => config('platform.maintenance.api_enabled', false),
                    'gameplay_enabled' => config('platform.maintenance.gameplay_enabled', false),
                ],
                'tournaments_enabled' => config('platform.features.tournaments_enabled', true),
            ]),
            'App configuration fetched successfully.'
        );
    }
}
