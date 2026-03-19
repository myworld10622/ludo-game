<?php

namespace App\Services\Match;

use App\Models\Game;
use App\Models\GameRoom;
use Illuminate\Support\Str;

class LudoRoomLifecycleService
{
    public function __construct(
        protected LudoBotSeatPolicy $botSeatPolicy
    ) {
    }

    public function makeQueueKey(Game $game, string $mode, string $stake, int $maxPlayers): string
    {
        return implode(':', [$game->slug, $mode, $stake, $maxPlayers]);
    }

    public function buildWaitingRoom(Game $game, array $attributes = []): GameRoom
    {
        return new GameRoom([
            'room_uuid' => (string) Str::uuid(),
            'game_id' => $game->id,
            'queue_key' => $attributes['queue_key'] ?? $this->makeQueueKey(
                $game,
                $attributes['play_mode'] ?? 'cash',
                (string) ($attributes['entry_fee'] ?? '0'),
                (int) ($attributes['max_players'] ?? 4)
            ),
            'room_type' => $attributes['room_type'] ?? 'public',
            'play_mode' => $attributes['play_mode'] ?? 'cash',
            'status' => 'waiting',
            'max_players' => $attributes['max_players'] ?? 4,
            'min_real_players' => $attributes['min_real_players'] ?? config('platform.ludo.min_real_players_to_start', 1),
            'entry_fee' => $attributes['entry_fee'] ?? 0,
            'prize_pool' => $attributes['prize_pool'] ?? 0,
            'allow_bots' => $attributes['allow_bots'] ?? config('platform.ludo.allow_bots_in_public_rooms', true),
            'bot_fill_after_seconds' => $attributes['bot_fill_after_seconds'] ?? config('platform.ludo.bot_fill_after_seconds', 8),
            'game_mode' => $attributes['game_mode'] ?? config('platform.ludo.default_game_mode', 'classic'),
            'node_namespace' => $attributes['node_namespace'] ?? config('platform.ludo.socket_namespace', '/ludo'),
            'settings' => $attributes['settings'] ?? [],
            'meta' => $attributes['meta'] ?? [],
        ]);
    }

    public function buildBotFillDecision(GameRoom $room): array
    {
        return $this->botSeatPolicy->buildSeatPlan(
            $room->max_players,
            $room->current_real_players,
            $room->allow_bots,
            $room->min_real_players
        );
    }
}
