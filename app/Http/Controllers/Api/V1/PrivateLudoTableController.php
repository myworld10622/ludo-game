<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrivateLudoTable;
use App\Models\PrivateLudoTablePlayer;
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
        try {
        $request->validate([
            'fee_amount'  => 'required|integer|min:0|max:10000',
            'max_players' => 'required|integer|in:2,3,4',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $fee = (int) $request->fee_amount;

            if ($fee > 0) {
                $this->wallet->debit(
                    user: $user,
                    amount: $fee,
                    referenceType: 'private_table_entry',
                    referenceId: 0,
                    description: 'Private table entry fee',
                );
            }

            $table = PrivateLudoTable::create([
                'code'           => PrivateLudoTable::generateCode(),
                'creator_id'     => $user->id,
                'fee_amount'     => $fee,
                'max_players'    => $request->max_players,
                'current_players' => 1,
                'prize_pool'     => $fee,
                'status'         => 'waiting',
                'expires_at'     => now()->addHours(2),
            ]);

            PrivateLudoTablePlayer::create([
                'private_table_id' => $table->id,
                'user_id'          => $user->id,
                'fee_paid'         => $fee,
                'status'           => 'joined',
            ]);

            // Update wallet debit reference now that we have table id
            if ($fee > 0) {
                DB::table('wallet_transactions')
                    ->where('user_id', $user->id)
                    ->where('reference_type', 'private_table_entry')
                    ->where('reference_id', 0)
                    ->latest()
                    ->limit(1)
                    ->update(['reference_id' => $table->id]);
            }

            return response()->json([
                'success' => true,
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

        return DB::transaction(function () use ($request, $user) {
            $table = PrivateLudoTable::where('code', strtoupper($request->code))
                ->where('status', 'waiting')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$table) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired table code.'], 404);
            }

            if ($table->isFull()) {
                return response()->json(['success' => false, 'message' => 'Table is full.'], 422);
            }

            $alreadyJoined = PrivateLudoTablePlayer::where('private_table_id', $table->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyJoined) {
                return response()->json(['success' => false, 'message' => 'Already joined this table.'], 422);
            }

            $fee = $table->fee_amount;

            if ($fee > 0) {
                $this->wallet->debit(
                    user: $user,
                    amount: $fee,
                    referenceType: 'private_table_entry',
                    referenceId: $table->id,
                    description: 'Private table entry fee',
                );
            }

            PrivateLudoTablePlayer::create([
                'private_table_id' => $table->id,
                'user_id'          => $user->id,
                'fee_paid'         => $fee,
                'status'           => 'joined',
            ]);

            $table->increment('current_players');
            $table->increment('prize_pool', $fee);
            $table->refresh();

            $readyToStart = $table->isFull();

            if ($readyToStart) {
                $table->update(['status' => 'in_progress', 'started_at' => now()]);
                $table->players()->update(['status' => 'playing']);
            }

            return response()->json([
                'success'        => true,
                'data'           => [
                    'table_id'       => $table->id,
                    'code'           => $table->code,
                    'fee_amount'     => $table->fee_amount,
                    'max_players'    => $table->max_players,
                    'current_players' => $table->current_players,
                    'prize_pool'     => $table->prize_pool,
                    'status'         => $table->status,
                    'ready_to_start' => $readyToStart,
                ],
            ]);
        });
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
                'data'    => [
                    'table_id'        => $table->id,
                    'code'            => $table->code,
                    'creator_id'      => $table->creator_id,
                    'creator'         => $table->creator?->username ?? 'Unknown',
                    'fee_amount'      => $table->fee_amount,
                    'max_players'     => $table->max_players,
                    'current_players' => $table->current_players,
                    'prize_pool'      => $table->prize_pool,
                    'winner_prize'    => (int) round($table->prize_pool * 0.80),
                    'status'          => $table->status,
                    'expires_at'      => $table->expires_at,
                    'players'         => $table->players->map(fn($p) => [
                        'user_id' => $p->user_id,
                        'name'    => $p->user?->username ?? 'Unknown',
                        'status'  => $p->status,
                    ]),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/v1/ludo/private-table/complete  (called by Node.js internal)
    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'table_id'  => 'required|integer',
            'winner_id' => 'required|integer',
        ]);

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
                description: 'Private table winnings',
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
                'success'      => true,
                'winner_id'    => $winner->id,
                'prize_paid'   => $prize,
            ]);
        });
    }
}
