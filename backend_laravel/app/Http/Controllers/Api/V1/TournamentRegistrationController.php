<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentWalletTransaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TournamentRegistrationController extends Controller
{
    // ── POST /api/v1/tournaments/{tournament}/register ────────────────────────
    // Player joins a tournament (deducts entry fee from wallet)
    public function register(Request $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();

        // If already registered, return existing registration (idempotent — checked first)
        $existing = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->whereNotIn('status', [TournamentRegistration::STATUS_REFUNDED])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Already registered.',
                'data'    => $existing,
            ], 200);
        }

        // ── Eligibility Checks ────────────────────────────────────────────────
        if (! $tournament->isRegistrationOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament registration is not open.',
            ], 422);
        }

        if ($tournament->isFull()) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament is full.',
            ], 422);
        }

        // Wallet balance check
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

        if (! $wallet || $wallet->balance < $tournament->entry_fee) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please add funds.',
                'required' => $tournament->entry_fee,
                'balance'  => $wallet?->balance ?? 0,
            ], 422);
        }

        // ── Registration Transaction ──────────────────────────────────────────
        $registration = DB::transaction(function () use ($user, $tournament, $wallet) {
            // Deduct entry fee
            $wallet->balance -= $tournament->entry_fee;
            $wallet->save();

            // Create registration
            $registration = TournamentRegistration::create([
                'tournament_id'  => $tournament->id,
                'user_id'        => $user->id,
                'is_bot'         => false,
                'entry_fee_paid' => $tournament->entry_fee,
                'status'         => TournamentRegistration::STATUS_REGISTERED,
                'registered_at'  => now(),
            ]);

            // Log wallet transaction
            TournamentWalletTransaction::create([
                'tournament_id'   => $tournament->id,
                'user_id'         => $user->id,
                'type'            => TournamentWalletTransaction::TYPE_ENTRY_FEE,
                'amount'          => $tournament->entry_fee,
                'status'          => 'completed',
                'registration_id' => $registration->id,
                'description'     => "Entry fee for tournament: {$tournament->name}",
            ]);

            // Increment player count
            $tournament->increment('current_players');

            // Auto-close if full
            if ($tournament->fresh()->isFull()) {
                $tournament->update(['status' => Tournament::STATUS_REGISTRATION_CLOSED]);
                $tournament->recalculatePrizePool();
            }

            return $registration;
        });

        return response()->json([
            'success'       => true,
            'message'       => 'Successfully registered for the tournament!',
            'data'          => $registration,
            'new_balance'   => $wallet->balance,
        ], 201);
    }

    // ── DELETE /api/v1/tournaments/{tournament}/register ──────────────────────
    // Player cancels registration (refund if before deadline)
    public function cancel(Request $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();

        $registration = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('status', TournamentRegistration::STATUS_REGISTERED)
            ->first();

        if (! $registration) {
            return response()->json([
                'success' => false,
                'message' => 'No active registration found.',
            ], 404);
        }

        // Can only cancel during registration phase
        if (! in_array($tournament->status, [Tournament::STATUS_REGISTRATION_OPEN, Tournament::STATUS_REGISTRATION_CLOSED])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel after tournament has started.',
            ], 422);
        }

        DB::transaction(function () use ($user, $tournament, $registration) {
            // Refund entry fee
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            $wallet->balance += $registration->entry_fee_paid;
            $wallet->save();

            TournamentWalletTransaction::create([
                'tournament_id'   => $tournament->id,
                'user_id'         => $user->id,
                'type'            => TournamentWalletTransaction::TYPE_REFUND,
                'amount'          => $registration->entry_fee_paid,
                'status'          => 'completed',
                'registration_id' => $registration->id,
                'description'     => "Refund for cancelled registration: {$tournament->name}",
            ]);

            $registration->update([
                'status'    => TournamentRegistration::STATUS_REFUNDED,
                'prize_won' => 0,
            ]);

            $tournament->decrement('current_players');

            // Re-open registration if it was closed due to being full
            if ($tournament->status === Tournament::STATUS_REGISTRATION_CLOSED && ! $tournament->isFull()) {
                $tournament->update(['status' => Tournament::STATUS_REGISTRATION_OPEN]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Registration cancelled. ₹{$registration->entry_fee_paid} refunded to your wallet.",
        ]);
    }

    // ── GET /api/v1/tournaments/{tournament}/registrations (Admin) ────────────
    public function list(Tournament $tournament): JsonResponse
    {
        $registrations = $tournament->registrations()->with('user')->get()->map(fn ($reg) => [
            'id'             => $reg->id,
            'name'           => $reg->displayName(),
            'is_bot'         => $reg->is_bot,
            'bot_difficulty' => $reg->bot_difficulty,
            'seed_number'    => $reg->seed_number,
            'status'         => $reg->status,
            'final_position' => $reg->final_position,
            'prize_won'      => $reg->prize_won,
            'registered_at'  => $reg->registered_at,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'total'         => $registrations->count(),
                'human_players' => $registrations->where('is_bot', false)->count(),
                'bots'          => $registrations->where('is_bot', true)->count(),
                'registrations' => $registrations,
            ],
        ]);
    }

    // ── POST /api/v1/tournaments/{tournament}/add-bot (Admin only) ────────────
    public function addBot(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'bot_difficulty' => 'sometimes|integer|between:1,3',
            'bot_name'       => 'sometimes|string|max:50',
            'slot_count'     => 'sometimes|integer|min:1|max:10',
        ]);

        if (! $tournament->canAddBot()) {
            $maxBots = $tournament->maxBotsAllowed();
            $current = $tournament->currentBotCount();
            return response()->json([
                'success' => false,
                'message' => "Cannot add more bots. Max allowed: {$maxBots}, current: {$current} (5% rule).",
            ], 422);
        }

        if ($tournament->isFull()) {
            return response()->json(['success' => false, 'message' => 'Tournament is full.'], 422);
        }

        $count = min($validated['slot_count'] ?? 1, $tournament->max_players - $tournament->current_players);
        $added = 0;

        DB::transaction(function () use ($tournament, $validated, $count, &$added) {
            for ($i = 0; $i < $count; $i++) {
                if (! $tournament->canAddBot() || $tournament->isFull()) {
                    break;
                }

                $botNum = $tournament->currentBotCount() + 1;
                TournamentRegistration::create([
                    'tournament_id'  => $tournament->id,
                    'user_id'        => null,
                    'is_bot'         => true,
                    'bot_difficulty' => $validated['bot_difficulty'] ?? 2,
                    'bot_name'       => $validated['bot_name'] ?? "Bot #{$botNum}",
                    'entry_fee_paid' => 0, // bots don't pay
                    'status'         => TournamentRegistration::STATUS_REGISTERED,
                    'registered_at'  => now(),
                ]);

                $tournament->increment('current_players');
                $added++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$added} bot(s) added successfully.",
            'data'    => [
                'added'          => $added,
                'current_bots'   => $tournament->currentBotCount(),
                'max_bots'       => $tournament->maxBotsAllowed(),
                'current_players'=> $tournament->current_players,
                'max_players'    => $tournament->max_players,
            ],
        ]);
    }

    // ── DELETE /api/v1/tournaments/{tournament}/bots/{registration} (Admin) ───
    public function removeBot(Tournament $tournament, TournamentRegistration $registration): JsonResponse
    {
        if (! $registration->is_bot || $registration->tournament_id !== $tournament->id) {
            return response()->json(['success' => false, 'message' => 'Bot registration not found.'], 404);
        }

        if ($registration->status === TournamentRegistration::STATUS_PLAYING) {
            return response()->json(['success' => false, 'message' => 'Cannot remove a bot mid-match.'], 422);
        }

        DB::transaction(function () use ($tournament, $registration) {
            $registration->delete();
            $tournament->decrement('current_players');

            // Re-open registration if closed
            if ($tournament->status === Tournament::STATUS_REGISTRATION_CLOSED) {
                $tournament->update(['status' => Tournament::STATUS_REGISTRATION_OPEN]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Bot removed. Slot is now available for real players.']);
    }

    // ── GET /api/v1/user/tournament-history (Auth User) ───────────────────────
    public function myHistory(Request $request): JsonResponse
    {
        $registrations = TournamentRegistration::with('tournament.prizes')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $registrations]);
    }
}
