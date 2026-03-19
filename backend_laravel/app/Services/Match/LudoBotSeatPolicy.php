<?php

namespace App\Services\Match;

class LudoBotSeatPolicy
{
    public function buildSeatPlan(
        int $maxPlayers,
        int $realPlayers,
        bool $allowBots,
        int $minRealPlayers
    ): array {
        $realPlayers = max(0, min($realPlayers, $maxPlayers));
        $botPlayers = 0;
        $shouldStart = false;

        if ($realPlayers >= $maxPlayers) {
            $shouldStart = true;
        } elseif ($allowBots && $realPlayers >= $minRealPlayers) {
            $botPlayers = max(0, $maxPlayers - $realPlayers);
            $shouldStart = true;
        }

        return [
            'max_players' => $maxPlayers,
            'real_players' => $realPlayers,
            'bot_players' => $botPlayers,
            'should_start' => $shouldStart,
            'started_with_bots' => $botPlayers > 0,
        ];
    }
}
