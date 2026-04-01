<?php

namespace App\Services\Match;

use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LudoMatchmakingService
{
    public function __construct(
        protected WalletService $walletService,
        protected LudoRoomLifecycleService $roomLifecycleService
    ) {
    }

    public function joinPublicQueue(User $user, array $attributes = []): GameRoom
    {
        return DB::transaction(function () use ($user, $attributes) {
            $game = $this->resolveLudoGame();
            $roomType = $attributes['room_type'] ?? 'public';
            $playMode = $attributes['play_mode'] ?? 'cash';
            $gameMode = $attributes['game_mode'] ?? config('platform.ludo.default_game_mode', 'classic');
            $maxPlayers = (int) ($attributes['max_players'] ?? config('platform.ludo.default_max_players', 4));
            $entryFee = (float) ($attributes['entry_fee'] ?? 0);
            $allowBots = (bool) ($attributes['allow_bots'] ?? ($roomType === 'public'
                ? config('platform.ludo.allow_bots_in_public_rooms', true)
                : config('platform.ludo.allow_bots_in_tournaments', false)));

            $existingSeat = GameRoomPlayer::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['joined', 'ready'])
                ->whereHas('room', function ($query) use ($game) {
                    $query->where('game_id', $game->id)->whereIn('status', ['waiting', 'starting']);
                })
                ->with(['room.players', 'room.game', 'user.profile'])
                ->lockForUpdate()
                ->first();

            if ($existingSeat) {
                return $existingSeat->room;
            }

            $queueKey = $this->roomLifecycleService->makeQueueKey(
                $game,
                $playMode,
                number_format($entryFee, 4, '.', ''),
                $maxPlayers
            );

            $room = GameRoom::query()
                ->where('game_id', $game->id)
                ->where('room_type', $roomType)
                ->where('play_mode', $playMode)
                ->where('game_mode', $gameMode)
                ->where('queue_key', $queueKey)
                ->where('status', 'waiting')
                ->where('current_players', '<', $maxPlayers)
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if (! $room) {
                $room = $this->roomLifecycleService->buildWaitingRoom($game, [
                    'queue_key' => $queueKey,
                    'room_type' => $roomType,
                    'play_mode' => $playMode,
                    'max_players' => $maxPlayers,
                    'entry_fee' => $entryFee,
                    'allow_bots' => $allowBots,
                    'game_mode' => $gameMode,
                    'meta' => [
                        'source' => 'laravel_matchmaking',
                    ],
                ]);
                $room->fill_bots_at = now()->addSeconds($room->bot_fill_after_seconds);
                $room->save();
            }

            $takenSeats = $room->players()->pluck('seat_no')->all();
            $seatNo = $this->nextAvailableSeat($maxPlayers, $takenSeats);

            if ($seatNo === null) {
                throw new HttpException(409, 'Room is already full.');
            }

            $walletTransaction = null;
            if ($entryFee > 0 && $playMode === 'cash') {
                $walletTransaction = $this->walletService->hold(
                    user: $user,
                    amount: $entryFee,
                    referenceType: GameRoom::class,
                    referenceId: $room->id,
                    description: 'Ludo room reservation hold',
                    currency: 'INR',
                    gameId: $game->id,
                    meta: [
                        'room_uuid' => $room->room_uuid,
                        'room_type' => $room->room_type,
                        'game_mode' => $room->game_mode,
                    ],
                );
            }

            GameRoomPlayer::query()->create([
                'game_room_id' => $room->id,
                'user_id' => $user->id,
                'wallet_transaction_id' => $walletTransaction?->id,
                'seat_no' => $seatNo,
                'player_type' => 'human',
                'status' => 'joined',
                'is_host' => $room->current_real_players === 0,
                'reconnect_token' => (string) Str::uuid(),
                'joined_at' => now(),
                'last_seen_at' => now(),
                'meta' => [
                    'username' => (string) ($user->user_code ?: $user->username),
                ],
            ]);

            $room->forceFill([
                'current_players' => $room->current_players + 1,
                'current_real_players' => $room->current_real_players + 1,
            ])->save();

            return $room->fresh(['game', 'players.user.profile']);
        });
    }

    public function getRoomSnapshot(string $roomUuid): GameRoom
    {
        return GameRoom::query()
            ->with(['game', 'players.user.profile'])
            ->where('room_uuid', $roomUuid)
            ->firstOrFail();
    }

    protected function resolveLudoGame(): Game
    {
        return Game::query()
            ->where('slug', 'ludo')
            ->firstOrFail();
    }

    protected function nextAvailableSeat(int $maxPlayers, array $takenSeats): ?int
    {
        for ($seat = 1; $seat <= $maxPlayers; $seat++) {
            if (! in_array($seat, $takenSeats, true)) {
                return $seat;
            }
        }

        return null;
    }
}
