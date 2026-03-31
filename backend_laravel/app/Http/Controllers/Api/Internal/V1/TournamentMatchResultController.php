<?php

namespace App\Http\Controllers\Api\Internal\V1;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\TournamentRegistration;
use App\Models\TournamentPrize;
use App\Models\TournamentWalletTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Called by Node.js after a tournament match ends.
 * Secured with internal server token.
 *
 * POST /api/internal/v1/tournaments/matches/{match}/result
 *
 * Payload from Node.js:
 * {
 *   "room_id": "room_abc123",
 *   "started_at": "2026-03-26T10:00:00Z",
 *   "ended_at":   "2026-03-26T10:38:00Z",
 *   "results": [
 *     { "user_id": 101, "slot": 1, "score": 4, "finish_position": 1, "result": "win" },
 *     { "user_id": 202, "slot": 2, "score": 2, "finish_position": 2, "result": "loss" }
 *   ],
 *   "game_log": "base64_replay_data"
 * }
 */
class TournamentMatchResultController extends Controller
{
    public function submit(Request $request, TournamentMatch $match): JsonResponse
    {
        if ($match->status === TournamentMatch::STATUS_COMPLETED) {
            return response()->json(['success' => false, 'message' => 'Match already completed.'], 409);
        }

        $validated = $request->validate([
            'room_id'                     => 'required|string',
            'started_at'                  => 'required|date',
            'ended_at'                    => 'required|date|after:started_at',
            'results'                     => 'required|array|min:1',
            'results.*.user_id'           => 'nullable|integer',    // null = bot
            'results.*.slot'              => 'required|integer|between:1,4',
            'results.*.score'             => 'required|integer|min:0',
            'results.*.finish_position'   => 'required|integer|min:1',
            'results.*.result'            => ['required', \Illuminate\Validation\Rule::in(['win', 'loss', 'draw', 'forfeit', 'disconnected'])],
            'game_log'                    => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($match, $validated) {
                $tournament = $match->tournament;

                // ── Update Match ──────────────────────────────────────────────
                $match->update([
                    'status'       => TournamentMatch::STATUS_COMPLETED,
                    'player_scores'=> $validated['results'],
                    'game_log'     => $validated['game_log'] ? ['data' => $validated['game_log']] : null,
                    'started_at'   => $validated['started_at'],
                    'ended_at'     => $validated['ended_at'],
                ]);

                // ── Update Match Players ──────────────────────────────────────
                $winnerRegistration = null;

                foreach ($validated['results'] as $result) {
                    // Find registration
                    $registration = $result['user_id']
                        ? TournamentRegistration::where('tournament_id', $tournament->id)
                                                ->where('user_id', $result['user_id'])
                                                ->first()
                        : null;

                    if (! $registration) {
                        continue;
                    }

                    TournamentMatchPlayer::updateOrCreate(
                        ['match_id' => $match->id, 'registration_id' => $registration->id],
                        [
                            'slot_number'     => $result['slot'],
                            'score'           => $result['score'],
                            'finish_position' => $result['finish_position'],
                            'result'          => $result['result'],
                            'finished_at'     => now(),
                        ]
                    );

                    // Track winner
                    if ($result['result'] === 'win') {
                        $winnerRegistration = $registration;
                    }

                    // Update registration status based on tournament round
                    $isLastRound = $this->isLastRound($match);
                    if ($result['result'] === 'win') {
                        $registration->update([
                            'status' => $isLastRound
                                ? TournamentRegistration::STATUS_WINNER
                                : TournamentRegistration::STATUS_PLAYING,
                        ]);
                    } else {
                        $registration->update([
                            'status'        => TournamentRegistration::STATUS_ELIMINATED,
                            'eliminated_at' => now(),
                        ]);
                    }
                }

                // Set match winner
                if ($winnerRegistration) {
                    $match->update(['winner_registration_id' => $winnerRegistration->id]);
                }

                // ── Advance Bracket ───────────────────────────────────────────
                $this->advanceBracket($match, $winnerRegistration);

                // ── Check if Tournament is Complete ───────────────────────────
                $this->checkTournamentCompletion($tournament);
            });

            return response()->json([
                'success' => true,
                'message' => 'Match result recorded successfully.',
            ]);

        } catch (\Throwable $e) {
            Log::error('TournamentMatchResult error', [
                'match_id' => $match->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record match result.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── POST /api/internal/v1/tournaments/matches/{match}/override (Admin) ────
    public function override(Request $request, TournamentMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'winner_user_id' => 'required|integer|exists:users,id',
            'reason'         => 'required|string|max:500',
        ]);

        $tournament    = $match->tournament;
        $winnerReg     = TournamentRegistration::where('tournament_id', $tournament->id)
                            ->where('user_id', $validated['winner_user_id'])
                            ->firstOrFail();

        DB::transaction(function () use ($match, $winnerReg, $validated) {
            $match->update([
                'winner_registration_id' => $winnerReg->id,
                'status'                 => TournamentMatch::STATUS_COMPLETED,
                'is_admin_override'      => true,
                'admin_override_note'    => $validated['reason'],
                'ended_at'               => now(),
            ]);

            // Re-run bracket advancement
            $this->advanceBracket($match, $winnerReg);
            $this->checkTournamentCompletion($match->tournament);
        });

        return response()->json(['success' => true, 'message' => 'Match result overridden by admin.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isLastRound(TournamentMatch $match): bool
    {
        $maxRound = TournamentMatch::where('tournament_id', $match->tournament_id)
                        ->max('round_number');
        return $match->round_number === $maxRound;
    }

    private function advanceBracket(TournamentMatch $match, ?TournamentRegistration $winner): void
    {
        if (! $winner) {
            return;
        }

        // Find the next round match for this winner.
        // next_match = ceil(match_number / players_per_match)
        $perMatch        = $match->tournament->players_per_match ?? 2;
        $nextMatchNumber = (int) ceil($match->match_number / $perMatch);
        $nextRound       = $match->round_number + 1;

        $nextMatch = TournamentMatch::where('tournament_id', $match->tournament_id)
                        ->where('round_number', $nextRound)
                        ->where('match_number', $nextMatchNumber)
                        ->first();

        if (! $nextMatch) {
            return; // Final round — no next match
        }

        // Assign winner to next match (fill next available slot)
        $slotsTaken = TournamentMatchPlayer::where('match_id', $nextMatch->id)->count();
        $nextSlot   = $slotsTaken + 1;

        TournamentMatchPlayer::create([
            'match_id'        => $nextMatch->id,
            'registration_id' => $winner->id,
            'slot_number'     => $nextSlot,
            'score'           => 0,
        ]);

        // If match is now fully populated, mark it as waiting
        $playersNeeded = $match->tournament->players_per_match;
        if (TournamentMatchPlayer::where('match_id', $nextMatch->id)->count() >= $playersNeeded) {
            $nextMatch->update(['status' => TournamentMatch::STATUS_WAITING]);
        }
    }

    private function checkTournamentCompletion(Tournament $tournament): void
    {
        $pendingMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->whereNotIn('status', [
                TournamentMatch::STATUS_COMPLETED,
                TournamentMatch::STATUS_CANCELLED,
                TournamentMatch::STATUS_FORFEITED,
            ])
            ->count();

        if ($pendingMatches > 0) {
            return;
        }

        // All matches done — calculate final rankings and pay prizes
        $tournament->update([
            'status'       => Tournament::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->distributePrizes($tournament);
    }

    private function distributePrizes(Tournament $tournament): void
    {
        // Get final rankings: winners first (by final_position), then eliminated (by eliminated_at desc)
        $registrations = $tournament->registrations()
            ->where('is_bot', false)
            ->orderByRaw("CASE WHEN status = 'winner' THEN 0 ELSE 1 END")
            ->orderBy('final_position')
            ->orderByDesc('eliminated_at')
            ->get();

        $prizes  = $tournament->prizes()->orderBy('position')->get();
        $ranked  = $registrations->values();
        $rankIdx = 0;

        foreach ($prizes as $prize) {
            // Skip bots — cascade to next real player
            while (isset($ranked[$rankIdx]) && $ranked[$rankIdx]->is_bot) {
                Log::info("Prize cascade: position {$prize->position} skipped bot, moving to next real player.");
                $rankIdx++;
            }

            if (! isset($ranked[$rankIdx])) {
                break;
            }

            $winner = $ranked[$rankIdx];

            // Credit prize to wallet
            $wallet = Wallet::where('user_id', $winner->user_id)->lockForUpdate()->first();
            if ($wallet) {
                $wallet->balance += $prize->prize_amount;
                $wallet->save();
            }

            // Record transaction
            TournamentWalletTransaction::create([
                'tournament_id'   => $tournament->id,
                'user_id'         => $winner->user_id,
                'type'            => TournamentWalletTransaction::TYPE_PRIZE_CREDIT,
                'amount'          => $prize->prize_amount,
                'status'          => 'completed',
                'registration_id' => $winner->id,
                'description'     => "Prize for position #{$prize->position} in {$tournament->name}",
            ]);

            // Update registration
            $winner->update([
                'final_position' => $prize->position,
                'prize_won'      => $prize->prize_amount,
                'completed_at'   => now(),
            ]);

            // Update prize record
            $prize->update([
                'winner_user_id' => $winner->user_id,
                'payout_status'  => 'paid',
                'paid_at'        => now(),
            ]);

            $rankIdx++;
        }

        // Record platform fee
        TournamentWalletTransaction::create([
            'tournament_id' => $tournament->id,
            'user_id'       => 1, // Platform account
            'type'          => TournamentWalletTransaction::TYPE_PLATFORM_FEE,
            'amount'        => $tournament->platform_fee_amount,
            'status'        => 'completed',
            'description'   => "20% platform fee for tournament: {$tournament->name}",
        ]);

        Log::info("Tournament {$tournament->id} completed. Prizes distributed.", [
            'prize_pool'    => $tournament->total_prize_pool,
            'platform_fee'  => $tournament->platform_fee_amount,
        ]);
    }
}
