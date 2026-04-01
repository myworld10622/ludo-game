<?php

namespace App\Services\Match;

use App\Models\GameMatch;
use App\Models\GameMatchPlayer;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LudoMatchLifecycleService
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    public function startMatch(GameRoom $room, array $payload = []): GameMatch
    {
        return DB::transaction(function () use ($room, $payload) {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::query()
                ->with(['game', 'players.user.profile'])
                ->whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingMatch = $lockedRoom->matches()
                ->whereIn('status', ['pending', 'starting', 'playing'])
                ->with('players')
                ->latest('id')
                ->first();

            if ($existingMatch) {
                return $existingMatch;
            }

            $seatPayloads = $this->normalizeSeatPayloads($payload['seats'] ?? []);
            if ($seatPayloads === []) {
                $seatPayloads = $this->buildSeatPayloadsFromRoomPlayers($lockedRoom->players);
            }

            $match = GameMatch::query()->create([
                'match_uuid' => (string) Str::uuid(),
                'game_id' => $lockedRoom->game_id,
                'game_room_id' => $lockedRoom->id,
                'status' => 'playing',
                'mode' => $lockedRoom->game_mode ?: 'classic',
                'max_players' => $lockedRoom->max_players,
                'real_players' => collect($seatPayloads)->where('player_type', 'human')->count(),
                'bot_players' => collect($seatPayloads)->where('player_type', 'bot')->count(),
                'entry_fee' => $lockedRoom->entry_fee,
                'prize_pool' => $this->resolvePrizePool($lockedRoom, $payload, $seatPayloads),
                'node_namespace' => $payload['node_namespace'] ?? $lockedRoom->node_namespace ?: config('platform.ludo.socket_namespace', '/ludo_v2'),
                'node_room_id' => $payload['node_room_id'] ?? $payload['room_id'] ?? $lockedRoom->node_room_id ?: $lockedRoom->room_uuid,
                'server_seed' => $payload['server_seed'] ?? null,
                'turn_state' => $payload['turn_state'] ?? null,
                'result_payload' => null,
                'started_at' => now(),
            ]);

            foreach ($seatPayloads as $seatPayload) {
                $roomPlayer = $this->resolveRoomPlayerForSeat($lockedRoom, $seatPayload);

                if (! $roomPlayer && ($seatPayload['player_type'] ?? null) === 'bot') {
                    $roomPlayer = $lockedRoom->players
                        ->first(fn (GameRoomPlayer $player) => (int) $player->seat_no === (int) $seatPayload['seat_no']);

                    if ($roomPlayer) {
                        $roomPlayer->forceFill([
                            'user_id' => null,
                            'wallet_transaction_id' => null,
                            'player_type' => 'bot',
                            'bot_code' => $seatPayload['bot_code'] ?? null,
                            'status' => 'ready',
                            'reconnect_token' => null,
                            'score' => 0,
                            'finish_position' => null,
                            'payout_amount' => 0,
                            'joined_at' => $roomPlayer->joined_at ?: now(),
                            'left_at' => null,
                            'last_seen_at' => now(),
                            'meta' => array_merge($roomPlayer->meta ?? [], [
                                'display_name' => $seatPayload['display_name'] ?? ('Player ' . $seatPayload['seat_no']),
                                'source' => 'node_ludo_v2_start_sync',
                            ]),
                        ])->save();
                    } else {
                        $roomPlayer = GameRoomPlayer::query()->create([
                            'game_room_id' => $lockedRoom->id,
                            'user_id' => null,
                            'wallet_transaction_id' => null,
                            'seat_no' => (int) $seatPayload['seat_no'],
                            'player_type' => 'bot',
                            'bot_code' => $seatPayload['bot_code'] ?? null,
                            'status' => 'ready',
                            'is_host' => false,
                            'reconnect_token' => null,
                            'score' => 0,
                            'finish_position' => null,
                            'payout_amount' => 0,
                            'joined_at' => now(),
                            'left_at' => null,
                            'last_seen_at' => now(),
                            'meta' => [
                                'display_name' => $seatPayload['display_name'] ?? ('Player ' . $seatPayload['seat_no']),
                                'source' => 'node_ludo_v2_start_sync',
                            ],
                        ]);
                    }
                }

                GameMatchPlayer::query()->create([
                    'game_match_id' => $match->id,
                    'user_id' => $roomPlayer?->user_id,
                    'game_room_player_id' => $roomPlayer?->id,
                    'seat_no' => (int) $seatPayload['seat_no'],
                    'player_type' => $seatPayload['player_type'],
                    'bot_code' => $seatPayload['bot_code'],
                    'finish_position' => null,
                    'score' => 0,
                    'is_winner' => false,
                    'payout_amount' => 0,
                    'status' => 'playing',
                    'joined_at' => $roomPlayer?->joined_at ?: now(),
                    'stats' => [
                        'display_name' => $seatPayload['display_name'],
                    ],
                ]);
            }

            $realPlayers = collect($seatPayloads)->where('player_type', 'human')->count();
            $botPlayers = collect($seatPayloads)->where('player_type', 'bot')->count();

            $lockedRoom->forceFill([
                'status' => 'playing',
                'current_players' => count($seatPayloads),
                'current_real_players' => $realPlayers,
                'current_bot_players' => $botPlayers,
                'entry_fee' => (float) ($payload['entry_fee'] ?? $lockedRoom->entry_fee),
                'node_namespace' => $match->node_namespace,
                'node_room_id' => $match->node_room_id,
                'started_with_bots' => $botPlayers > 0,
                'prize_pool' => $match->prize_pool,
                'started_at' => $lockedRoom->started_at ?: now(),
                'fill_bots_at' => null,
                'meta' => array_merge($lockedRoom->meta ?? [], [
                    'active_match_uuid' => $match->match_uuid,
                    'match_started_at' => now()->toIso8601String(),
                    'seat_snapshot' => $seatPayloads,
                ]),
            ])->save();

            return $match->fresh(['room', 'players.user']);
        }, 5);
    }

    public function completeMatch(GameMatch $match, array $payload = []): GameMatch
    {
        return DB::transaction(function () use ($match, $payload) {
            /** @var GameMatch $lockedMatch */
            $lockedMatch = GameMatch::query()
                ->with(['room.players.walletTransaction', 'players.user', 'players.roomPlayer.walletTransaction'])
                ->whereKey($match->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedMatch->status, ['completed', 'cancelled'], true)) {
                return $lockedMatch;
            }

            $cancelled = (bool) ($payload['cancelled'] ?? false);
            $winnerSeat = Arr::get($payload, 'winner.seat_no');
            $winnerUserId = Arr::get($payload, 'winner.user_id');
            $placements = collect($payload['placements'] ?? [])->keyBy(fn ($item) => (string) ($item['seat_no'] ?? ''));

            foreach ($lockedMatch->players as $matchPlayer) {
                $placement = $placements->get((string) $matchPlayer->seat_no, []);
                $isWinner = (bool) ($placement['is_winner'] ?? false);

                if (! $isWinner && $winnerSeat !== null) {
                    $isWinner = (int) $winnerSeat === (int) $matchPlayer->seat_no;
                }

                if (! $isWinner && $winnerUserId !== null && $matchPlayer->user_id !== null) {
                    $isWinner = (int) $winnerUserId === (int) $matchPlayer->user_id;
                }

                $payoutAmount = (float) ($placement['payout_amount'] ?? 0);
                if ($isWinner && $payoutAmount <= 0 && $matchPlayer->player_type === 'human') {
                    $payoutAmount = (float) $lockedMatch->prize_pool;
                }

                $matchPlayer->forceFill([
                    'finish_position' => $placement['finish_position'] ?? null,
                    'score' => (int) ($placement['score'] ?? $matchPlayer->score),
                    'is_winner' => $isWinner,
                    'payout_amount' => $payoutAmount,
                    'status' => $cancelled ? 'cancelled' : 'completed',
                    'finished_at' => now(),
                    'stats' => array_merge($matchPlayer->stats ?? [], $placement['stats'] ?? []),
                ])->save();

                $roomPlayer = $matchPlayer->roomPlayer;
                if ($roomPlayer) {
                    $roomPlayer->forceFill([
                        'finish_position' => $matchPlayer->finish_position,
                        'score' => $matchPlayer->score,
                        'payout_amount' => $matchPlayer->payout_amount,
                        'status' => $cancelled ? 'cancelled' : 'completed',
                        'left_at' => now(),
                    ])->save();
                }

                if ($matchPlayer->player_type !== 'human' || ! $matchPlayer->user) {
                    continue;
                }

                $holdTransaction = $roomPlayer?->walletTransaction;
                if ($holdTransaction instanceof WalletTransaction) {
                    if ($cancelled) {
                        $this->walletService->refundHeldTransaction(
                            $holdTransaction,
                            'Ludo room cancelled refund',
                            [
                                'match_uuid' => $lockedMatch->match_uuid,
                                'game_room_uuid' => $lockedMatch->room?->room_uuid,
                            ]
                        );
                    } else {
                        $this->walletService->captureHeldTransaction(
                            $holdTransaction,
                            'Ludo entry fee captured',
                            [
                                'match_uuid' => $lockedMatch->match_uuid,
                                'game_room_uuid' => $lockedMatch->room?->room_uuid,
                            ]
                        );
                    }
                }

                if (! $cancelled && $matchPlayer->is_winner && $matchPlayer->payout_amount > 0) {
                    $this->walletService->credit(
                        user: $matchPlayer->user,
                        amount: (float) $matchPlayer->payout_amount,
                        referenceType: GameMatch::class,
                        referenceId: $lockedMatch->id,
                        description: 'Ludo match prize credit',
                        currency: 'INR',
                        gameId: $lockedMatch->game_id,
                        meta: [
                            'match_uuid' => $lockedMatch->match_uuid,
                            'seat_no' => $matchPlayer->seat_no,
                            'finish_position' => $matchPlayer->finish_position,
                        ],
                    );
                }
            }

            $winner = $lockedMatch->players()->where('is_winner', true)->whereNotNull('user_id')->first();
            $lockedMatch->forceFill([
                'winner_user_id' => $winner?->user_id,
                'status' => $cancelled ? 'cancelled' : 'completed',
                'result_payload' => $payload,
                'completed_at' => now(),
            ])->save();

            if ($lockedMatch->room) {
                $finalPlayers = $lockedMatch->players;
                $finalRealPlayers = $finalPlayers->where('player_type', 'human')->count();
                $finalBotPlayers = $finalPlayers->where('player_type', 'bot')->count();

                $lockedMatch->room->forceFill([
                    'status' => $cancelled ? 'cancelled' : 'completed',
                    'current_players' => $finalPlayers->count(),
                    'current_real_players' => $finalRealPlayers,
                    'current_bot_players' => $finalBotPlayers,
                    'completed_at' => now(),
                    'meta' => array_merge($lockedMatch->room->meta ?? [], [
                        'completed_match_uuid' => $lockedMatch->match_uuid,
                        'match_completed_at' => now()->toIso8601String(),
                    ]),
                ])->save();
            }

            return $lockedMatch->fresh(['room', 'players.user', 'winner']);
        }, 5);
    }

    protected function resolvePrizePool(GameRoom $room, array $payload, array $seatPayloads): float
    {
        if (isset($payload['prize_pool'])) {
            return (float) $payload['prize_pool'];
        }

        if ((float) $room->prize_pool > 0) {
            return (float) $room->prize_pool;
        }

        return (float) $room->entry_fee * max($room->max_players, count($seatPayloads));
    }

    protected function normalizeSeatPayloads(array $seats): array
    {
        return collect($seats)
            ->map(function ($seat) {
                return [
                    'seat_no' => (int) ($seat['seat_no'] ?? $seat['seatNo'] ?? 0),
                    'user_id' => $seat['user_id'] ?? $seat['userId'] ?? null,
                    'player_type' => ($seat['player_type'] ?? $seat['playerType'] ?? 'human') === 'bot' ? 'bot' : 'human',
                    'display_name' => $seat['display_name'] ?? $seat['displayName'] ?? null,
                    'bot_code' => $seat['bot_code'] ?? $seat['botCode'] ?? null,
                ];
            })
            ->filter(fn (array $seat) => $seat['seat_no'] > 0)
            ->values()
            ->all();
    }

    protected function buildSeatPayloadsFromRoomPlayers($roomPlayers): array
    {
        return $roomPlayers
            ->map(fn (GameRoomPlayer $roomPlayer) => [
                'seat_no' => $roomPlayer->seat_no,
                'user_id' => $roomPlayer->user_id,
                'player_type' => $roomPlayer->player_type,
                'display_name' => $roomPlayer->player_type === 'bot'
                    ? "Player {$roomPlayer->seat_no}"
                    : (Arr::get($roomPlayer->meta ?? [], 'username') ?: (string) ($roomPlayer->user?->user_code ?? $roomPlayer->user?->username)),
                'bot_code' => $roomPlayer->bot_code,
            ])
            ->values()
            ->all();
    }

    protected function resolveRoomPlayerForSeat(GameRoom $room, array $seatPayload): ?GameRoomPlayer
    {
        return $room->players
            ->first(function (GameRoomPlayer $roomPlayer) use ($seatPayload) {
                if ((int) $roomPlayer->seat_no !== (int) $seatPayload['seat_no']) {
                    return false;
                }

                if ($roomPlayer->user_id === null) {
                    return $seatPayload['user_id'] === null;
                }

                return (int) $roomPlayer->user_id === (int) $seatPayload['user_id'];
            });
    }
}
