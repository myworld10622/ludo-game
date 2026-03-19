<?php

namespace App\Services\Tournament;

use Closure;
use Illuminate\Support\Facades\Cache;

class TournamentLifecycleLockService
{
    public function withRoundLock(int $tournamentId, int $roundNo, Closure $callback, int $seconds = 60): mixed
    {
        $key = sprintf('tournament:lifecycle:%d:round:%d', $tournamentId, $roundNo);
        $store = Cache::store()->getStore();

        if (method_exists($store, 'lock')) {
            return Cache::lock($key, $seconds)->get($callback);
        }

        return $callback();
    }
}
