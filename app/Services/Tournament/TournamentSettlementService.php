<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentEntryResult;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentSettlementService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly TournamentLudoMatchLinkService $tournamentLudoMatchLinkService,
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentBracketMatchService $tournamentBracketMatchService,
        private readonly TournamentRoundLifecycleService $tournamentRoundLifecycleService
    ) {
    }

    public function settle(Tournament $tournament, array $rankings): Tournament
    {
        if (empty($rankings)) {
            throw new RuntimeException('Tournament rankings are required for settlement.');
        }

        return DB::transaction(function () use ($tournament, $rankings): Tournament {
            $rankedEntryIds = collect($rankings)
                ->pluck('tournament_entry_id')
                ->map(static fn ($entryId) => (int) $entryId)
                ->values();

            $currentRound = $this->tournamentBracketMatchService->resolveCurrentRoundForEntryIds(
                $tournament,
                $rankedEntryIds
            );

            foreach ($rankings as $row) {
                $entry = TournamentEntry::query()
                    ->where('tournament_id', $tournament->id)
                    ->findOrFail($row['tournament_entry_id']);

                $rank = (int) $row['final_rank'];
                $score = (float) ($row['score'] ?? 0);
                $prize = $this->resolvePrizeAmount($tournament, $rank);
                $isWinner = $rank === 1 || $prize > 0;

                $captureTransactionId = null;
                if ($entry->wallet_hold_transaction_id) {
                    $holdTransaction = WalletTransaction::find($entry->wallet_hold_transaction_id);
                    if ($holdTransaction && method_exists($this->walletService, 'captureHeldTransaction')) {
                        $capture = $this->walletService->captureHeldTransaction(
                            $holdTransaction,
                            sprintf('Tournament entry captured: %s', $tournament->name)
                        );

                        if ($capture && isset($capture->id)) {
                            $captureTransactionId = $capture->id;
                            $entry->wallet_capture_transaction_id = $capture->id;
                        }
                    }
                }

                TournamentEntryResult::updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'tournament_entry_id' => $entry->id,
                    ],
                    [
                        'final_rank' => $rank,
                        'score' => $score,
                        'prize_amount' => $prize,
                        'result_status' => $isWinner ? 'winner' : 'completed',
                        'meta' => [
                            'wallet_capture_transaction_id' => $captureTransactionId,
                        ],
                    ]
                );

                $entry->status = $isWinner ? 'winner' : 'eliminated';
                $entry->completed_at = now();
                $entry->save();
            }

            $pendingLinksInRound = $this->tournamentBracketMatchService->hasPendingMatchesInRound(
                $tournament,
                $currentRound
            );

            if ($pendingLinksInRound) {
                $tournament->status = 'running';
                $tournament->save();

                return $tournament->fresh(['game', 'prizes']);
            }

            $roundWinners = $this->tournamentBracketMatchService->resolveRoundWinners($tournament, $currentRound);
            if ($roundWinners->count() <= 1) {
                $tournament->status = 'completed';
                $tournament->completed_at = now();
                $tournament->current_active_entries = 0;
                $tournament->save();

                return $tournament->fresh(['game', 'prizes']);
            }

            $nextRound = $currentRound + 1;
            $nextRoundExists = $this->tournamentBracketMatchService->nextRoundExists($tournament, $nextRound);

            if (! $nextRoundExists) {
                $this->tournamentRoundLifecycleService->runRound(
                    $tournament,
                    $nextRound,
                    $this->tournamentBracketConfigService->resolveMatchSize($tournament)
                );
            }

            $tournament->status = 'running';
            $tournament->current_active_entries = $roundWinners->count();
            $tournament->save();

            return $tournament->fresh(['game', 'prizes']);
        });
    }

    private function resolvePrizeAmount(Tournament $tournament, int $rank): float
    {
        $prize = $tournament->prizes()
            ->where('rank_from', '<=', $rank)
            ->where('rank_to', '>=', $rank)
            ->orderBy('rank_from')
            ->first();

        if (! $prize) {
            return 0.0;
        }

        return (float) $prize->prize_amount;
    }
}
