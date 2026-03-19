<?php

namespace App\Services\Tournament;

use App\Models\GameRoom;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentLudoExecutionService
{
    private const STALE_ASSIGNED_ROOM_AFTER_SECONDS = 90;

    public function __construct(
        private readonly TournamentLudoMatchLinkService $tournamentLudoMatchLinkService,
        private readonly TournamentLudoRoomProvisionService $tournamentLudoRoomProvisionService,
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentBracketMatchService $tournamentBracketMatchService
    ) {
    }

    public function claimRoomForEntry(Tournament $tournament, TournamentEntry $entry): GameRoom
    {
        if ($entry->tournament_id !== $tournament->id) {
            throw new RuntimeException('Entry does not belong to the requested tournament.');
        }

        $match = $this->ensureProvisionedMatch($tournament, $entry);
        if (! $match || ! $match->external_match_ref) {
            throw new ModelNotFoundException('Tournament room assignment not found.');
        }

        /** @var GameRoom $room */
        $room = GameRoom::query()
            ->with('players')
            ->where('room_uuid', $match->external_match_ref)
            ->firstOrFail();

        $room = $this->normalizeTournamentRoomState($room);

        return $room;
    }

    private function ensureProvisionedMatch(Tournament $tournament, TournamentEntry $entry): ?TournamentMatch
    {
        $match = $this->tournamentBracketMatchService->resolveActiveMatchForEntry($tournament, $entry);

        if ($match && $match->external_match_ref) {
            $room = GameRoom::query()
                ->where('room_uuid', $match->external_match_ref)
                ->first();

            if ($room && ! $this->isStaleAssignedRoom($match, $room)) {
                return $match;
            }

            $this->resetStaleAssignedMatch($tournament, $match, $room);
        }

        if (! $tournament->matches()->exists()) {
            $this->tournamentLudoMatchLinkService->seedRoundOne(
                $tournament,
                $this->resolveTableSize($tournament)
            );
        }

        $activeMatch = $this->tournamentBracketMatchService->resolveActiveMatchForEntry(
            $tournament->fresh(),
            $entry->fresh()
        );

        if (
            $activeMatch
            && ! $activeMatch->external_match_ref
        ) {
            $this->tournamentLudoRoomProvisionService->provisionRoomsForRound(
                $tournament->fresh('game'),
                (int) $activeMatch->round_no,
                $this->resolveTableSize($tournament)
            );
        }

        return $this->tournamentBracketMatchService->resolveActiveMatchForEntry(
            $tournament->fresh(),
            $entry->fresh()
        );
    }

    private function isStaleAssignedRoom(TournamentMatch $match, ?GameRoom $room): bool
    {
        if (! $room) {
            return true;
        }

        if ($match->status !== 'assigned') {
            return false;
        }

        if (! in_array($room->status, ['waiting', 'starting', 'playing'], true)) {
            return false;
        }

        $cutoff = now()->subSeconds(self::STALE_ASSIGNED_ROOM_AFTER_SECONDS);

        if (in_array($room->status, ['starting', 'playing'], true)) {
            return $room->current_players < $room->max_players
                && $room->updated_at
                && $room->updated_at->lt($cutoff);
        }

        return $room->updated_at && $room->updated_at->lt($cutoff);
    }

    private function resetStaleAssignedMatch(Tournament $tournament, TournamentMatch $match, ?GameRoom $room): void
    {
        DB::transaction(function () use ($tournament, $match, $room): void {
            $match->status = 'pending';
            $match->external_match_ref = null;
            $match->node_room_id = null;
            $match->scheduled_at = null;
            $match->started_at = null;
            $match->save();

            $tournament->matchLinks()
                ->where('round_no', $match->round_no)
                ->where('table_no', $match->match_no)
                ->update([
                    'external_match_uuid' => null,
                    'status' => 'pending',
                ]);

            if ($room) {
                $meta = (array) ($room->meta ?? []);
                $meta['recovery'] = array_merge((array) ($meta['recovery'] ?? []), [
                    'reset_at' => now()->toIso8601String(),
                    'reason' => 'stale_assigned_room',
                ]);

                $room->status = 'cancelled';
                $room->completed_at = now();
                $room->meta = $meta;
                $room->save();
            }
        });
    }

    private function resolveTableSize(Tournament $tournament): int
    {
        return $this->tournamentBracketConfigService->resolveMatchSize($tournament);
    }

    private function normalizeTournamentRoomState(GameRoom $room): GameRoom
    {
        if (
            $room->play_mode !== 'tournament'
            || $room->current_players >= $room->max_players
            || ! in_array($room->status, ['starting', 'playing'], true)
        ) {
            return $room;
        }

        $meta = (array) ($room->meta ?? []);
        unset($meta['active_match_uuid'], $meta['match_started_at']);

        $room->status = 'waiting';
        $room->started_at = null;
        $room->meta = $meta;
        $room->save();

        return $room->fresh('players');
    }

    public function completeProvisionedRoom(GameRoom $room, array $rankings): Tournament
    {
        $meta = $room->meta ?? [];
        $tournamentId = $meta['tournament_id'] ?? null;

        if (! $tournamentId) {
            throw new RuntimeException('Room is not linked to a tournament.');
        }

        /** @var Tournament $tournament */
        $tournament = Tournament::query()->with('prizes')->findOrFail($tournamentId);

        return DB::transaction(function () use ($room, $rankings, $tournament): Tournament {
            $match = $this->resolveTournamentMatchFromRoom($room);

            if ($match) {
                $tournament->matchLinks()
                    ->where('round_no', $match->round_no)
                    ->where('table_no', $match->match_no)
                    ->where('external_match_uuid', $room->room_uuid)
                    ->update([
                        'status' => 'completed',
                    ]);
            }

            $room->status = 'completed';
            $room->completed_at = now();
            $room->save();

            if ($match) {
                $this->tournamentBracketMatchService->completeMatch($match, $rankings);
            }

            return $tournament;
        });
    }

    public function buildRankingsFromSeatMap(GameRoom $room, array $seatResults): Collection
    {
        $players = $room->players()->get()->keyBy('seat_no');

        return collect($seatResults)
            ->map(function (array $seatResult) use ($players) {
                $seatNo = (int) $seatResult['seat_no'];
                $player = $players->get($seatNo);
                $entryId = $player?->meta['tournament_entry_id'] ?? null;

                if (! $entryId) {
                    throw new RuntimeException('Tournament entry mapping missing for seat '.$seatNo);
                }

                return [
                    'tournament_entry_id' => $entryId,
                    'final_rank' => (int) $seatResult['final_rank'],
                    'score' => (float) ($seatResult['score'] ?? 0),
                ];
            })
            ->values();
    }

    private function resolveTournamentMatchFromRoom(GameRoom $room): ?TournamentMatch
    {
        $meta = (array) ($room->meta ?? []);
        $matchId = $meta['tournament_match_id'] ?? null;

        if (! $matchId) {
            return null;
        }

        return TournamentMatch::query()->find((int) $matchId);
    }
}
