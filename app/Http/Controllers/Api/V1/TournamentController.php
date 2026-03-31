<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\TournamentPrize;
use App\Models\TournamentRegistration;
use App\Services\TournamentBracketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    // ── GET /api/v1/tournaments ───────────────────────────────────────────────
    // List public active tournaments (browsable in Unity lobby)
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::with('prizes')
            ->publicTournaments()
            ->approved()
            ->whereIn('status', [
                Tournament::STATUS_REGISTRATION_OPEN,
                Tournament::STATUS_IN_PROGRESS,
                Tournament::STATUS_REGISTRATION_CLOSED,
            ]);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('format')) {
            $query->where('format', $request->input('format'));
        }
        if ($request->filled('min_fee')) {
            $query->where('entry_fee', '>=', $request->min_fee);
        }
        if ($request->filled('max_fee')) {
            $query->where('entry_fee', '<=', $request->max_fee);
        }

        $tournaments = $query->orderByDesc('registration_start_at')
                             ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $tournaments,
        ]);
    }

    // ── GET /api/v1/tournaments/{tournament} ──────────────────────────────────
    public function show(Tournament $tournament): JsonResponse
    {
        // Private tournament visible only with invite code (handled in separate route)
        if ($tournament->type === 'private') {
            return response()->json(['success' => false, 'message' => 'Tournament not found.'], 404);
        }

        $tournament->load(['prizes', 'registrations.user']);

        return response()->json(['success' => true, 'data' => $tournament]);
    }

    // ── GET /api/v1/tournaments/private/{invite_code} ─────────────────────────
    public function showByInviteCode(Request $request, string $inviteCode): JsonResponse
    {
        $tournament = Tournament::where('invite_code', strtoupper($inviteCode))
            ->where('type', 'private')
            ->first();

        if (! $tournament) {
            return response()->json(['success' => false, 'message' => 'Invalid invite code.'], 404);
        }

        // Password protected
        if ($tournament->invite_password) {
            $request->validate(['password' => 'required|string']);
            if ($request->password !== $tournament->invite_password) {
                return response()->json(['success' => false, 'message' => 'Incorrect password.'], 403);
            }
        }

        $tournament->load('prizes');

        return response()->json(['success' => true, 'data' => $tournament]);
    }

    // ── POST /api/v1/tournaments (Admin or User) ──────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:150',
            'description'           => 'nullable|string',
            'type'                  => ['required', Rule::in(['public', 'private'])],
            'format'                => ['required', Rule::in(['knockout'])], // only knockout supported; others are draft/future
            'bracket_mode'          => ['sometimes', Rule::in(['auto', 'manual'])],
            'entry_fee'             => 'required|numeric|min:0',
            'max_players'           => ['required', 'integer', Rule::in([4, 8, 16, 32, 64, 128])],
            'players_per_match'     => ['sometimes', Rule::in([2, 4])],
            'turn_time_limit'       => 'sometimes|integer|between:15,60',
            'match_timeout'         => 'sometimes|integer|min:600',
            'bot_allowed'           => 'sometimes|boolean',
            'max_bot_pct'           => 'sometimes|integer|max:5', // enforced max 5%
            'terms_conditions'      => 'nullable|string',
            'registration_start_at' => 'required|date|after:now',
            'registration_end_at'   => 'required|date|after:registration_start_at',
            'tournament_start_at'   => 'required|date|after:registration_end_at',
            // Prize structure: must sum to 100%
            'prizes'                => 'required|array|min:1|max:5',
            'prizes.*.position'     => 'required|integer|between:1,5',
            'prizes.*.prize_pct'    => 'required|numeric|min:0.01',
        ]);

        // Validate prize percentages sum to 100
        $totalPct = collect($validated['prizes'])->sum('prize_pct');
        if (abs($totalPct - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Prize percentages must sum to 100%. Current sum: ' . $totalPct . '%',
            ], 422);
        }

        $isAdmin       = $request->user()?->tokenCan('admin') ?? false;
        $creatorUserId = $isAdmin ? null : $request->user()->id;

        // User-created: requires approval for high entry fees
        $requiresApproval = ! $isAdmin && $validated['entry_fee'] > 500;

        // Generate invite code for private tournaments
        $inviteCode = null;
        if ($validated['type'] === 'private') {
            $inviteCode = Tournament::generateInviteCode();
        }

        $tournament = DB::transaction(function () use ($validated, $isAdmin, $creatorUserId, $requiresApproval, $inviteCode) {
            $tournament = Tournament::create([
                'name'                  => $validated['name'],
                'description'           => $validated['description'] ?? null,
                'creator_type'          => $isAdmin ? 'admin' : 'user',
                'creator_user_id'       => $creatorUserId,
                'type'                  => $validated['type'],
                'format'                => $validated['format'],
                'bracket_mode'          => $validated['bracket_mode'] ?? 'auto',
                'status'                => Tournament::STATUS_DRAFT,
                'entry_fee'             => $validated['entry_fee'],
                'max_players'           => $validated['max_players'],
                'players_per_match'     => $validated['players_per_match'] ?? 2,
                'platform_fee_pct'      => 20.00,
                'turn_time_limit'       => $validated['turn_time_limit'] ?? 30,
                'match_timeout'         => $validated['match_timeout'] ?? 2700,
                'bot_allowed'           => $validated['bot_allowed'] ?? false,
                'max_bot_pct'           => min($validated['max_bot_pct'] ?? 5, 5),
                'invite_code'           => $inviteCode,
                'invite_password'       => $validated['invite_password'] ?? null,
                'requires_approval'     => $requiresApproval,
                'is_approved'           => $isAdmin ? true : ! $requiresApproval,
                'terms_conditions'      => $validated['terms_conditions'] ?? null,
                'registration_start_at' => $validated['registration_start_at'],
                'registration_end_at'   => $validated['registration_end_at'],
                'tournament_start_at'   => $validated['tournament_start_at'],
            ]);

            // Create prize slots
            foreach ($validated['prizes'] as $prizeData) {
                TournamentPrize::create([
                    'tournament_id' => $tournament->id,
                    'position'      => $prizeData['position'],
                    'prize_pct'     => $prizeData['prize_pct'],
                    'prize_amount'  => 0, // calculated when registration closes
                ]);
            }

            return $tournament;
        });

        return response()->json([
            'success'     => true,
            'message'     => $requiresApproval
                ? 'Tournament created. Pending admin approval.'
                : 'Tournament created successfully.',
            'data'        => $tournament->load('prizes'),
            'invite_code' => $inviteCode,
        ], 201);
    }

    // ── POST /api/v1/tournaments/{tournament}/approve (Admin only) ─────────────
    // Approve a user-created tournament that requires admin review.
    public function approve(Request $request, Tournament $tournament): JsonResponse
    {
        if (! $request->user()?->tokenCan('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($tournament->is_approved) {
            return response()->json(['success' => false, 'message' => 'Tournament is already approved.'], 422);
        }

        $tournament->update([
            'is_approved'       => true,
            'requires_approval' => false,
            // Auto-publish if still in draft and registration window hasn't passed
            'status' => ($tournament->status === Tournament::STATUS_DRAFT
                && $tournament->registration_start_at
                && $tournament->registration_start_at->isFuture())
                ? Tournament::STATUS_DRAFT            // keep draft — creator can publish
                : ($tournament->status === Tournament::STATUS_DRAFT
                    ? Tournament::STATUS_REGISTRATION_OPEN  // window already open; publish now
                    : $tournament->status),                 // already published; don't change
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tournament approved.',
            'data'    => $tournament->fresh(),
        ]);
    }

    // ── POST /api/v1/tournaments/{tournament}/publish (Admin only) ─────────────
    public function publish(Tournament $tournament): JsonResponse
    {
        if ($tournament->status !== Tournament::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft tournaments can be published.',
            ], 422);
        }

        $tournament->update(['status' => Tournament::STATUS_REGISTRATION_OPEN]);

        return response()->json([
            'success' => true,
            'message' => 'Tournament is now open for registration.',
            'data'    => $tournament,
        ]);
    }

    // ── POST /api/v1/tournaments/{tournament}/close-registration (Admin) ───────
    public function closeRegistration(Tournament $tournament): JsonResponse
    {
        if ($tournament->status !== Tournament::STATUS_REGISTRATION_OPEN) {
            return response()->json(['success' => false, 'message' => 'Registration is not open.'], 422);
        }

        $tournament->update([
            'status'               => Tournament::STATUS_REGISTRATION_CLOSED,
            'registration_end_at'  => now(),
        ]);

        // Recalculate prize pool based on actual registrations
        $tournament->recalculatePrizePool();

        return response()->json([
            'success' => true,
            'message' => 'Registration closed. Prize pool calculated.',
            'data'    => $tournament->fresh(['prizes']),
        ]);
    }

    // ── POST /api/v1/tournaments/{tournament}/cancel (Admin) ──────────────────
    public function cancel(Request $request, Tournament $tournament): JsonResponse
    {
        if (in_array($tournament->status, [Tournament::STATUS_COMPLETED, Tournament::STATUS_CANCELLED])) {
            return response()->json(['success' => false, 'message' => 'Cannot cancel this tournament.'], 422);
        }

        $tournament->update([
            'status'       => Tournament::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        // Refund all registrations
        dispatch(new \App\Jobs\RefundTournamentRegistrations($tournament));

        return response()->json(['success' => true, 'message' => 'Tournament cancelled. Refunds will be processed.']);
    }

    // ── GET /api/v1/tournaments/{tournament}/bracket ───────────────────────────
    public function bracket(Tournament $tournament): JsonResponse
    {
        $matches = $tournament->matches()->with('players.registration.user')->get();

        $bracket = $matches->groupBy('round_number')->map(function ($roundMatches) {
            return $roundMatches->map(function ($match) {
                return [
                    'id'           => $match->id,
                    'match_number' => $match->match_number,
                    'status'       => $match->status,
                    'scheduled_at' => $match->scheduled_at,
                    'players'      => $match->players->map(fn ($p) => [
                        'slot'         => $p->slot_number,
                        'name'         => $p->registration->displayName(),
                        'is_bot'       => $p->registration->is_bot,
                        'score'        => $p->score,
                        'finish_pos'   => $p->finish_position,
                        'result'       => $p->result,
                    ]),
                    'winner' => $match->winner_registration_id
                        ? $match->winner->displayName()
                        : null,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'tournament_id' => $tournament->id,
                'format'        => $tournament->format,
                'rounds'        => $bracket,
            ],
        ]);
    }

    // ── POST /api/v1/tournaments/{tournament}/generate-bracket (Admin) ────────
    public function generateBracket(Tournament $tournament): JsonResponse
    {
        if (! in_array($tournament->status, [
            Tournament::STATUS_REGISTRATION_CLOSED,
            Tournament::STATUS_IN_PROGRESS,
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Close registration before generating the bracket.',
            ], 422);
        }

        try {
            $bracketService = new TournamentBracketService();
            $matches        = $bracketService->generate($tournament);

            return response()->json([
                'success' => true,
                'message' => 'Bracket generated successfully. Tournament is now IN PROGRESS.',
                'data'    => [
                    'total_matches' => count($matches),
                    'matches'       => $matches,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ── POST /api/v1/tournaments/{tournament}/claim-room (Auth User) ──────────
    // Called by Node.js on behalf of the user to get their current match room data
    public function claimRoom(Request $request, Tournament $tournament): JsonResponse
    {
        $user = $request->user();
        $entryUuid = $request->input('tournament_entry_uuid');

        // Find registration — by ID (entry UUID) or by user_id fallback
        $registration = null;
        if ($entryUuid) {
            $registration = TournamentRegistration::where('id', $entryUuid)
                ->where('tournament_id', $tournament->id)
                ->first();
        }
        if (! $registration) {
            $registration = TournamentRegistration::where('tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->first();
        }

        if (! $registration) {
            return response()->json(['success' => false, 'message' => 'Registration not found.'], 404);
        }

        if ($tournament->hasPlaySlots()) {
            $activeSlotIndex = $tournament->activePlaySlotIndex();
            if ($activeSlotIndex === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament can only be played during scheduled play slots.',
                    'play_slots' => $tournament->play_slots,
                ], 422);
            }

            $registration->update([
                'last_checked_in_at' => now(),
                'last_checked_in_slot_index' => $activeSlotIndex,
                'status' => in_array($registration->status, [
                    TournamentRegistration::STATUS_REGISTERED,
                    TournamentRegistration::STATUS_CHECKED_IN,
                ], true) ? TournamentRegistration::STATUS_CHECKED_IN : $registration->status,
            ]);
        }

        // Find the next unplayed match for this registration
        $matchPlayer = TournamentMatchPlayer::where('registration_id', $registration->id)
            ->whereHas('match', function ($q) {
                $q->whereNotIn('status', [
                    TournamentMatch::STATUS_COMPLETED,
                    TournamentMatch::STATUS_CANCELLED,
                ]);
            })
            ->with(['match.players.registration.user'])
            ->first();

        // No bracket yet — find or create a waiting room for this player (queue-style)
        if (! $matchPlayer) {
            $match = $this->findOrCreateWaitingMatch($tournament, $registration);

            if (! $match) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active match found. Wait for the bracket to be generated.',
                ], 404);
            }

            $match->load('players.registration.user');
            $matchPlayer = $match->players->firstWhere('registration_id', $registration->id);
        }

        $match = $matchPlayer->match;

        // Move status from scheduled → waiting when first player claims
        if ($match->status === TournamentMatch::STATUS_SCHEDULED) {
            $match->update(['status' => TournamentMatch::STATUS_WAITING]);
        }

        // Only send human players — bots will be filled by Node.js scheduleBotFill after 8s each
        $players = $match->players->filter(function ($mp) {
            $reg = $mp->registration;
            return $reg && ! $reg->is_bot;
        })->map(function ($mp) {
            $reg  = $mp->registration;
            $name = $reg ? $reg->displayName() : "Player {$mp->slot_number}";
            return [
                'seat_no'      => $mp->slot_number,
                'user_id'      => $reg->user_id,
                'player_type'  => 'human',
                'display_name' => $name,
                'bot_code'     => null,
                'meta'         => [
                    'tournament_entry_uuid' => (string) $reg->id,
                    'tournamentEntryUuid'   => (string) $reg->id,
                ],
            ];
        })->values()->all();

        $humanCount = count($players);
        $botCount   = 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'uuid'                   => "tournament-match-{$match->id}",
                'mode'                   => 'tournament',
                'status'                 => $match->status === TournamentMatch::STATUS_COMPLETED ? 'completed' : 'waiting',
                'max_players'            => $tournament->players_per_match,
                'current_players'        => count($players),
                'current_real_players'   => $humanCount,
                'current_bot_players'    => $botCount,
                'entry_fee'              => $tournament->entry_fee,
                'allow_bots'             => (bool) $tournament->bot_allowed,
                'bot_fill_after_seconds' => 8,
                'match_uuid'             => null,
                'tournament_match_id'    => $match->id,
                'tournament_id'          => $tournament->id,
                'players'                => $players,
            ],
        ]);
    }

    // ── GET /api/v1/tournaments/{tournament}/leaderboard ──────────────────────
    public function leaderboard(Tournament $tournament): JsonResponse
    {
        $registrations = $tournament->registrations()
            ->with('user')
            ->whereNotNull('final_position')
            ->orderBy('final_position')
            ->get();

        $leaderboard = $registrations->map(fn ($reg) => [
            'position'  => $reg->final_position,
            'name'      => $reg->displayName(),
            'is_bot'    => $reg->is_bot,
            'prize_won' => $reg->prize_won,
            'status'    => $reg->status,
        ]);

        return response()->json(['success' => true, 'data' => $leaderboard]);
    }

    // ── GET /api/v1/tournaments/{tournament}/financials (Admin) ───────────────
    public function financials(Tournament $tournament): JsonResponse
    {
        $totalEntry    = $tournament->registrations()->where('is_bot', false)->sum('entry_fee_paid');
        $platformFee   = $totalEntry * ($tournament->platform_fee_pct / 100);
        $prizePool     = $totalEntry - $platformFee;
        $prizesPaid    = $tournament->prizes()->where('payout_status', 'paid')->sum('prize_amount');
        $prizesPending = $tournament->prizes()->where('payout_status', 'pending')->sum('prize_amount');

        return response()->json([
            'success' => true,
            'data'    => [
                'total_entry_collected' => $totalEntry,
                'platform_fee_20pct'    => $platformFee,
                'prize_pool_80pct'      => $prizePool,
                'prizes_paid'           => $prizesPaid,
                'prizes_pending'        => $prizesPending,
                'registered_players'    => $tournament->registrations()->where('is_bot', false)->count(),
                'registered_bots'       => $tournament->registrations()->where('is_bot', true)->count(),
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * For tournaments without a pre-generated bracket:
     * Find a waiting match with open slots, or create one.
     * This allows queue-style matchmaking inside the tournament.
     */
    private function findOrCreateWaitingMatch(Tournament $tournament, TournamentRegistration $registration): ?TournamentMatch
    {
        $perMatch = $tournament->players_per_match;

        // Find a waiting match that still has room
        $match = TournamentMatch::where('tournament_id', $tournament->id)
            ->whereIn('status', [TournamentMatch::STATUS_WAITING, TournamentMatch::STATUS_SCHEDULED])
            ->withCount('players')
            ->having('players_count', '<', $perMatch)
            ->first();

        if (! $match) {
            $matchCount = TournamentMatch::where('tournament_id', $tournament->id)->count();
            $match = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'round_number'  => 1,
                'match_number'  => $matchCount + 1,
                'status'        => TournamentMatch::STATUS_WAITING,
                'scheduled_at'  => now(),
            ]);
        }

        // Add player if not already in the match
        $alreadyIn = TournamentMatchPlayer::where('match_id', $match->id)
            ->where('registration_id', $registration->id)
            ->exists();

        if (! $alreadyIn) {
            $nextSlot = (TournamentMatchPlayer::where('match_id', $match->id)->max('slot_number') ?? 0) + 1;
            TournamentMatchPlayer::create([
                'match_id'        => $match->id,
                'registration_id' => $registration->id,
                'slot_number'     => $nextSlot,
                'score'           => 0,
            ]);
        }

        return $match;
    }
}
