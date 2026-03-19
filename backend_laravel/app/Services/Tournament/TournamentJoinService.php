<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class TournamentJoinService
{
    public function __construct(
        private readonly TournamentEntryService $tournamentEntryService,
        private readonly WalletService $walletService
    ) {
    }

    public function join(Tournament $tournament, User $user, int $entriesRequested = 1): Collection
    {
        if (! $tournament->allow_multiple_entries && $entriesRequested === 1) {
            $existingEntries = $tournament->entries()
                ->where('user_id', $user->id)
                ->get();

            if ($existingEntries->isNotEmpty()) {
                $existingEntries->each(function (TournamentEntry $entry): void {
                    $entry->load('tournament');
                });

                return $existingEntries;
            }
        }

        $this->assertJoinAllowed($tournament, $user, $entriesRequested);

        return DB::transaction(function () use ($tournament, $user, $entriesRequested): Collection {
            $entries = collect();

            for ($i = 0; $i < $entriesRequested; $i++) {
                $entryNo = (int) $tournament->next_entry_no;
                $entryIndexForUser = $this->tournamentEntryService->nextEntryIndexForUser($tournament, $user->id);
                $ticketNo = $this->tournamentEntryService->generateTicketNumber($tournament, $entryNo);
                $entryUuid = (string) Str::uuid();

                $hold = null;
                if ((float) $tournament->entry_fee > 0) {
                    $hold = $this->walletService->hold(
                        $user,
                        (float) $tournament->entry_fee,
                        sprintf('Tournament entry hold: %s', $tournament->name),
                        null,
                        $tournament->id
                    );
                }

                $entry = TournamentEntry::create([
                    'uuid' => $entryUuid,
                    'entry_uuid' => $entryUuid,
                    'tournament_id' => $tournament->id,
                    'game_id' => $tournament->game_id,
                    'user_id' => $user->id,
                    'entry_no' => $entryNo,
                    'ticket_no' => $ticketNo,
                    'entry_index_for_user' => $entryIndexForUser,
                    'status' => 'joined',
                    'entry_fee' => $tournament->entry_fee,
                    'wallet_hold_transaction_id' => $hold?->id,
                    'joined_at' => now(),
                ]);

                $entries->push($entry);

                $tournament->next_entry_no = $entryNo + 1;
                $tournament->current_total_entries += 1;
                $tournament->current_active_entries += 1;
                $tournament->save();
            }

            $entries->each(function (TournamentEntry $entry): void {
                $entry->load('tournament');
            });

            return $entries;
        });
    }

    private function assertJoinAllowed(Tournament $tournament, User $user, int $entriesRequested): void
    {
        if (! in_array($tournament->status, ['published', 'entry_open'], true)) {
            throw new RuntimeException('Tournament is not open for entries.');
        }

        $now = CarbonImmutable::now();
        if ($tournament->entry_open_at && $now->lt(CarbonImmutable::parse($tournament->entry_open_at))) {
            throw new RuntimeException('Tournament entries are not open yet.');
        }

        if ($tournament->entry_close_at && $now->gt(CarbonImmutable::parse($tournament->entry_close_at))) {
            throw new RuntimeException('Tournament entry window is closed.');
        }

        if ($entriesRequested < 1) {
            throw new InvalidArgumentException('At least one entry is required.');
        }

        if (! $tournament->allow_multiple_entries && $entriesRequested > 1) {
            throw new RuntimeException('Multiple entries are not allowed in this tournament.');
        }

        $existingEntries = $tournament->entries()->where('user_id', $user->id)->count();
        if (($existingEntries + $entriesRequested) > $tournament->max_entries_per_user) {
            throw new RuntimeException('Entry limit reached for this tournament.');
        }

        if ($tournament->max_total_entries !== null) {
            $remainingCapacity = $tournament->max_total_entries - $tournament->current_total_entries;
            if ($entriesRequested > $remainingCapacity) {
                throw new RuntimeException('Tournament entry capacity is full.');
            }
        }
    }
}
