<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameRoomPlayer;
use App\Models\Tournament;
use App\Models\TournamentMatchLink;
use App\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TournamentLudoRoomProvisionService
{
    public function __construct(
        private readonly TournamentBracketMatchService $tournamentBracketMatchService
    ) {
    }

    public function provisionRoundOneRooms(Tournament $tournament, int $tableSize = 4): Collection
    {
        return $this->provisionRoomsForRound($tournament, 1, $tableSize);
    }

    public function provisionRoomsForRound(Tournament $tournament, int $roundNo, int $tableSize = 4): Collection
    {
        $game = $tournament->game;
        $gameKey = strtolower((string) ($game->game ?? $game->code ?? $game->slug ?? $game->name ?? ''));

        if (! $game || $gameKey !== 'ludo') {
            throw new RuntimeException('Only Ludo tournaments can provision Ludo rooms.');
        }

        $matches = TournamentMatch::query()
            ->with('entries.tournamentEntry')
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->where('status', '!=', 'completed')
            ->orderBy('match_no')
            ->get();

        return DB::transaction(function () use ($tournament, $matches, $game, $roundNo, $tableSize): Collection {
            $createdRooms = collect();

            foreach ($matches as $match) {
                $tableNo = $match->match_no;
                $matchEntries = $match->entries
                    ->filter(fn ($matchEntry) => $matchEntry->status !== 'completed')
                    ->values();
                $effectiveTableSize = max(2, min($tableSize, $matchEntries->count()));

                if ($matchEntries->count() <= 1) {
                    continue;
                }

                $existingRoom = GameRoom::query()
                    ->where('meta->tournament_id', (string) $tournament->id)
                    ->where('meta->tournament_table_no', (string) $tableNo)
                    ->where('meta->tournament_round_no', (string) $roundNo)
                    ->whereIn('status', ['waiting', 'starting', 'playing'])
                    ->first();

                if ($existingRoom) {
                    if ($match->external_match_ref !== $existingRoom->room_uuid) {
                        $this->tournamentBracketMatchService->markProvisioned($match, $existingRoom->room_uuid, $existingRoom->id);
                    }
                    $createdRooms->push($existingRoom);
                    continue;
                }

                $room = GameRoom::create([
                    'room_uuid' => (string) Str::uuid(),
                    'game_id' => $game->id,
                    'room_type' => 'match',
                    'play_mode' => 'tournament',
                    'status' => 'waiting',
                    'max_players' => $effectiveTableSize,
                    'min_real_players' => $tournament->resolveMinRealPlayersToStart(),
                    'entry_fee' => $tournament->entry_fee,
                    'prize_pool' => 0,
                    'current_players' => $matchEntries->count(),
                    'current_real_players' => $matchEntries->count(),
                    'current_bot_players' => 0,
                    'allow_bots' => $tournament->bot_allowed,
                    'bot_fill_after_seconds' => $tournament->bot_allowed ? $tournament->resolveBotFillAfterSeconds() : 0,
                    'started_with_bots' => false,
                    'game_mode' => (string) (($tournament->rules['game_mode'] ?? 'CLASSIC')),
                    'started_at' => null,
                    'completed_at' => null,
                    'settings' => [
                        'bot_start_policy' => $tournament->resolveBotStartPolicy(),
                        'replace_offline_with_bots' => $tournament->tournamentBotsCanReplaceOfflinePlayers(),
                    ],
                    'meta' => [
                        'tournament_id' => (string) $tournament->id,
                        'tournament_uuid' => $tournament->uuid,
                        'tournament_table_no' => (string) $tableNo,
                        'tournament_round_no' => (string) $roundNo,
                        'tournament_match_id' => (string) ($match?->id ?? ''),
                        'tournament_match_uuid' => (string) ($match?->match_uuid ?? ''),
                    ],
                ]);

                foreach ($matchEntries as $index => $matchEntry) {
                    $entry = $matchEntry->tournamentEntry;

                    if (! $entry) {
                        continue;
                    }

                    GameRoomPlayer::create([
                        'game_room_id' => $room->id,
                        'user_id' => $entry->user_id,
                        'seat_no' => $index + 1,
                        'player_type' => 'human',
                        'status' => 'joined',
                        'bot_code' => null,
                        'is_host' => $index === 0,
                        'joined_at' => now(),
                        'meta' => [
                            'tournament_entry_id' => $entry->id,
                            'tournament_entry_uuid' => $entry->uuid,
                            'ticket_no' => $entry->ticket_no,
                        ],
                    ]);

                    $this->backfillCompatibilityLink($tournament, $match, $entry, $room->room_uuid);
                }

                $this->tournamentBracketMatchService->markProvisioned($match, $room->room_uuid, $room->id);

                $createdRooms->push($room);
            }

            return $createdRooms;
        });
    }

    private function backfillCompatibilityLink(Tournament $tournament, TournamentMatch $match, $entry, string $roomUuid): void
    {
        TournamentMatchLink::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'tournament_entry_id' => $entry->id,
                'round_no' => $match->round_no,
                'table_no' => $match->match_no,
            ],
            [
                'external_match_uuid' => $roomUuid,
                'status' => 'assigned',
                'meta' => [
                    'game' => 'ludo',
                        'table_size' => $match->max_players,
                    'round_no' => $match->round_no,
                    'advance_count' => data_get($match->settings, 'advance_count', 1),
                    'tournament_match_id' => $match->id,
                    'tournament_match_uuid' => $match->match_uuid,
                ],
            ]
        );
    }
}
