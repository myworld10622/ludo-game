<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrivateLudoTable;
use App\Models\PrivateLudoTablePlayer;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivateLudoTableController extends Controller
{
    public function __construct(private WalletService $wallet) {}

    // POST /api/v1/ludo/private-table/create
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'fee_amount'  => 'required|integer|min:0|max:10000',
            'max_players' => 'required|integer|in:2,3,4',
        ]);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $user) {
                $fee = (int) $request->fee_amount;

                // Check balance before holding
                if ($fee > 0) {
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                    if (!$wallet || $wallet->balance < $fee) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient balance. You need ₹' . $fee . ' to create this table.',
                            'error_code' => 'insufficient_balance',
                        ], 422);
                    }
                }

                $table = PrivateLudoTable::create([
                    'code'            => PrivateLudoTable::generateCode(),
                    'creator_id'      => $user->id,
                    'fee_amount'      => $fee,
                    'max_players'     => (int) $request->max_players,
                    'current_players' => 1,
                    'prize_pool'      => $fee,
                    'status'          => 'waiting',
                    'expires_at'      => now()->addHours(2),
                ]);

                // Hold fee (not deduct — released if game never starts)
                $holdTx = null;
                if ($fee > 0) {
                    $holdTx = $this->wallet->hold(
                        user: $user,
                        amount: $fee,
                        referenceType: 'private_table_entry',
                        referenceId: $table->id,
                        description: "Private table entry hold (Code: {$table->code})",
                    );
                }

                PrivateLudoTablePlayer::create([
                    'private_table_id'      => $table->id,
                    'user_id'               => $user->id,
                    'fee_paid'              => $fee,
                    'wallet_transaction_id' => $holdTx?->id,
                    'status'                => 'joined',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Table created! Share the code with your friends.',
                    'data'    => [
                        'table_id'    => $table->id,
                        'code'        => $table->code,
                        'fee_amount'  => $table->fee_amount,
                        'max_players' => $table->max_players,
                        'status'      => $table->status,
                        'expires_at'  => $table->expires_at,
                    ],
                ], 201);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/v1/ludo/private-table/join
    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $user) {
                $table = PrivateLudoTable::where('code', strtoupper($request->code))
                    ->where('status', 'waiting')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (!$table) {
                    return response()->json([
                        'success'    => false,
                        'message'    => 'Invalid or expired table code. Please check and try again.',
                        'error_code' => 'invalid_code',
                    ], 404);
                }

                // Check if user already has an active slot
                $existingPlayer = PrivateLudoTablePlayer::where('private_table_id', $table->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existingPlayer) {
                    if ($existingPlayer->status === 'joined') {
                        // Already in room — return current state (idempotent rejoin)
                        $table->refresh();
                        return response()->json([
                            'success'         => true,
                            'message'         => 'You are already in this table.',
                            'data'            => $this->tableData($table),
                            'ready_to_start'  => $table->isFull(),
                        ]);
                    }

                    if ($existingPlayer->status === 'left') {
                        // Player left before — allow rejoin if table not full
                        if ($table->isFull()) {
                            return response()->json([
                                'success'    => false,
                                'message'    => 'Table is full. No more players can join.',
                                'error_code' => 'table_full',
                            ], 422);
                        }

                        $fee = $table->fee_amount;

                        if ($fee > 0) {
                            $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                            if (!$wallet || $wallet->balance < $fee) {
                                return response()->json([
                                    'success'    => false,
                                    'message'    => 'Insufficient balance. You need ₹' . $fee . ' to join.',
                                    'error_code' => 'insufficient_balance',
                                ], 422);
                            }
                        }

                        $holdTx = null;
                        if ($fee > 0) {
                            $holdTx = $this->wallet->hold(
                                user: $user,
                                amount: $fee,
                                referenceType: 'private_table_entry',
                                referenceId: $table->id,
                                description: "Private table entry hold (Code: {$table->code})",
                            );
                        }

                        $existingPlayer->update([
                            'status'                => 'joined',
                            'wallet_transaction_id' => $holdTx?->id,
                        ]);

                        $table->increment('current_players');
                        $table->increment('prize_pool', $fee);
                        $table->refresh();

                        $readyToStart = $table->isFull();
                        return response()->json([
                            'success'        => true,
                            'message'        => 'Rejoined table! Waiting for other players.',
                            'data'           => $this->tableData($table),
                            'ready_to_start' => $readyToStart,
                        ]);
                    }
                }

                if ($table->isFull()) {
                    return response()->json([
                        'success'    => false,
                        'message'    => 'Table is full. No more players can join.',
                        'error_code' => 'table_full',
                    ], 422);
                }

                $fee = $table->fee_amount;

                if ($fee > 0) {
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->first();
                    if (!$wallet || $wallet->balance < $fee) {
                        return response()->json([
                            'success'    => false,
                            'message'    => 'Insufficient balance. You need ₹' . $fee . ' to join this table.',
                            'error_code' => 'insufficient_balance',
                        ], 422);
                    }
                }

                $holdTx = null;
                if ($fee > 0) {
                    $holdTx = $this->wallet->hold(
                        user: $user,
                        amount: $fee,
                        referenceType: 'private_table_entry',
                        referenceId: $table->id,
                        description: "Private table entry hold (Code: {$table->code})",
                    );
                }

                PrivateLudoTablePlayer::create([
                    'private_table_id'      => $table->id,
                    'user_id'               => $user->id,
                    'fee_paid'              => $fee,
                    'wallet_transaction_id' => $holdTx?->id,
                    'status'                => 'joined',
                ]);

                $table->increment('current_players');
                $table->increment('prize_pool', $fee);
                $table->refresh();

                $readyToStart = $table->isFull();

                return response()->json([
                    'success'        => true,
                    'message'        => $readyToStart
                        ? 'All players joined! Game is starting...'
                        : 'Joined! Waiting for other players.',
                    'data'           => $this->tableData($table),
                    'ready_to_start' => $readyToStart,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/v1/ludo/private-table/leave
    public function leave(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $user) {
                $table = PrivateLudoTable::where('code', strtoupper($request->code))
                    ->where('status', 'waiting')
                    ->lockForUpdate()
                    ->first();

                if (!$table) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Table not found or game already started.',
                    ], 404);
                }

                $player = PrivateLudoTablePlayer::where('private_table_id', $table->id)
                    ->where('user_id', $user->id)
                    ->where('status', 'joined')
                    ->first();

                if (!$player) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not in this table.',
                    ], 422);
                }

                // Release the fee hold — money goes back to balance
                if ($player->wallet_transaction_id) {
                    $holdTx = WalletTransaction::find($player->wallet_transaction_id);
                    if ($holdTx && $holdTx->status === 'held') {
                        $this->wallet->refundHeldTransaction(
                            $holdTx,
                            'Player left private table before game started',
                        );
                    }
                }

                $player->update(['status' => 'left']);
                $table->decrement('current_players');
                if ($table->fee_amount > 0) {
                    $table->decrement('prize_pool', $table->fee_amount);
                }

                $table->refresh();

                return response()->json([
                    'success'         => true,
                    'message'         => 'Left table. Your balance has been refunded.',
                    'data'            => [
                        'code'            => $table->code,
                        'current_players' => $table->current_players,
                        'max_players'     => $table->max_players,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/v1/ludo/private-table/{code}
    public function info(string $code): JsonResponse
    {
        try {
            $table = PrivateLudoTable::where('code', strtoupper($code))
                ->with(['creator:id,username', 'players.user:id,username'])
                ->first();

            if (!$table) {
                return response()->json(['success' => false, 'message' => 'Table not found.'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => array_merge($this->tableData($table), [
                    'creator' => $table->creator?->username ?? 'Unknown',
                    'players' => $table->players->map(fn($p) => [
                        'user_id' => $p->user_id,
                        'name'    => $p->user?->username ?? 'Unknown',
                        'status'  => $p->status,
                    ]),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/internal/v1/ludo/private-table/start  (called by Node.js when all players ready)
    // Captures all holds atomically — fee is permanently deducted when game starts
    public function start(Request $request): JsonResponse
    {
        $request->validate(['table_id' => 'required|integer']);

        try {
            return DB::transaction(function () use ($request) {
                $table = PrivateLudoTable::where('id', $request->table_id)
                    ->where('status', 'waiting')
                    ->lockForUpdate()
                    ->first();

                if (!$table) {
                    return response()->json(['success' => false, 'message' => 'Table not found or already started.'], 404);
                }

                if (!$table->isFull()) {
                    return response()->json(['success' => false, 'message' => 'Table is not full yet.'], 422);
                }

                // Capture all held fees — permanent deduction for all players
                $players = PrivateLudoTablePlayer::where('private_table_id', $table->id)
                    ->where('status', 'joined')
                    ->get();

                foreach ($players as $player) {
                    if ($player->wallet_transaction_id) {
                        $holdTx = WalletTransaction::find($player->wallet_transaction_id);
                        if ($holdTx && $holdTx->status === 'held') {
                            $this->wallet->captureHeldTransaction(
                                $holdTx,
                                'Private table game started — fee captured',
                            );
                        }
                    }
                }

                $table->update(['status' => 'in_progress', 'started_at' => now()]);
                $table->players()->where('status', 'joined')->update(['status' => 'playing']);

                return response()->json(['success' => true]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/internal/v1/ludo/private-table/complete  (called by Node.js after game ends)
    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'table_id'  => 'required|integer',
            'winner_id' => 'required|integer',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $table = PrivateLudoTable::where('id', $request->table_id)
                    ->where('status', 'in_progress')
                    ->lockForUpdate()
                    ->firstOrFail();

                $prize = (int) round($table->prize_pool * 0.80);
                $winner = \App\Models\User::findOrFail($request->winner_id);

                $this->wallet->credit(
                    user: $winner,
                    amount: $prize,
                    referenceType: 'private_table_prize',
                    referenceId: $table->id,
                    description: "Private table winnings (Code: {$table->code})",
                );

                $table->update([
                    'status'       => 'completed',
                    'winner_id'    => $winner->id,
                    'winner_prize' => $prize,
                ]);

                $table->players()->where('user_id', $winner->id)->update(['status' => 'won']);
                $table->players()->where('user_id', '!=', $winner->id)
                    ->where('status', 'playing')->update(['status' => 'lost']);

                return response()->json([
                    'success'    => true,
                    'winner_id'  => $winner->id,
                    'prize_paid' => $prize,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function tableData(PrivateLudoTable $table): array
    {
        return [
            'table_id'        => $table->id,
            'code'            => $table->code,
            'creator_id'      => $table->creator_id,
            'fee_amount'      => $table->fee_amount,
            'max_players'     => $table->max_players,
            'current_players' => $table->current_players,
            'prize_pool'      => $table->prize_pool,
            'winner_prize'    => (int) round($table->prize_pool * 0.80),
            'status'          => $table->status,
            'expires_at'      => $table->expires_at,
        ];
    }
}
