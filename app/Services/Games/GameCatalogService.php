<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GameCatalogService
{
    public function listAdminGames(int $perPage = 20): LengthAwarePaginator
    {
        return Game::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function createGame(array $payload): Game
    {
        return Game::query()->create($this->normalizePayload($payload));
    }

    public function updateGame(Game $game, array $payload): Game
    {
        $game->fill($this->normalizePayload($payload))->save();

        return $game->fresh();
    }

    public function visibleGames(): Collection
    {
        return Game::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function homePayload(?User $user): array
    {
        return [
            'visible_games' => $this->visibleGames(),
            'wallet_summary' => [
                'balance' => $user?->wallets()->where('wallet_type', 'cash')->sum('balance') ?? 0,
                'locked_balance' => $user?->wallets()->where('wallet_type', 'cash')->sum('locked_balance') ?? 0,
                'currency' => 'INR',
            ],
            'shortcuts' => [
                'deposit_enabled' => true,
                'withdraw_enabled' => true,
                'history_enabled' => true,
                'rewards_enabled' => true,
                'support_enabled' => true,
            ],
        ];
    }

    protected function normalizePayload(array $payload): array
    {
        $normalized = Arr::only($payload, [
            'code',
            'name',
            'slug',
            'description',
            'is_active',
            'is_visible',
            'tournaments_enabled',
            'sort_order',
            'launch_type',
            'client_route',
            'socket_namespace',
            'icon_url',
            'banner_url',
            'metadata',
            'published_at',
        ]);

        if (isset($normalized['code']) && empty($normalized['slug'])) {
            $normalized['slug'] = Str::slug($normalized['code']);
        }

        if (isset($normalized['is_visible'])) {
            $normalized['published_at'] = $normalized['is_visible'] ? ($normalized['published_at'] ?? now()) : null;
        }

        return $normalized;
    }
}
