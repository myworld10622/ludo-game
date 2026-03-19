<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TournamentAdminLifecycleService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly TournamentRoundLifecycleService $tournamentRoundLifecycleService
    ) {
    }

    public function publish(Tournament $tournament, ?int $adminId = null): Tournament
    {
        if (! in_array($tournament->status, ['draft', 'cancelled'], true)) {
            throw new RuntimeException('Only draft or cancelled tournaments can be published.');
        }

        $tournament->status = 'published';
        $tournament->updated_by_admin_id = $adminId;
        $tournament->save();

        return $tournament->fresh(['game', 'prizes']);
    }

    public function lockEntries(Tournament $tournament, ?int $adminId = null): Tournament
    {
        if (! in_array($tournament->status, ['published', 'entry_open'], true)) {
            throw new RuntimeException('Tournament entries cannot be locked from the current state.');
        }

        $tournament->status = 'entry_locked';
        $tournament->updated_by_admin_id = $adminId;
        $tournament->save();

        if ($tournament->start_at && $tournament->start_at <= now()) {
            $tournament->status = 'seeding';
            $tournament->save();
            $this->tournamentRoundLifecycleService->dispatch($tournament, 1);
        }

        return $tournament->fresh(['game', 'prizes']);
    }

    public function cancel(Tournament $tournament, ?string $reason = null, ?int $adminId = null): Tournament
    {
        return DB::transaction(function () use ($tournament, $reason, $adminId): Tournament {
            $entries = $tournament->entries()->get();

            foreach ($entries as $entry) {
                $this->refundEntryIfPossible($entry, $reason);

                $entry->status = 'refunded';
                $entry->completed_at = now();
                $entry->meta = array_merge($entry->meta ?? [], [
                    'cancel_reason' => $reason,
                ]);
                $entry->save();
            }

            $tournament->status = 'cancelled';
            $tournament->cancelled_at = now();
            $tournament->updated_by_admin_id = $adminId;
            $tournament->meta = array_merge($tournament->meta ?? [], [
                'cancel_reason' => $reason,
            ]);
            $tournament->current_active_entries = 0;
            $tournament->save();

            return $tournament->fresh(['game', 'prizes']);
        });
    }

    private function refundEntryIfPossible(TournamentEntry $entry, ?string $reason = null): void
    {
        if (! $entry->wallet_hold_transaction_id) {
            return;
        }

        $holdTransaction = WalletTransaction::find($entry->wallet_hold_transaction_id);
        if (! $holdTransaction) {
            return;
        }

        if (method_exists($this->walletService, 'refundHeldTransaction')) {
            $refund = $this->walletService->refundHeldTransaction(
                $holdTransaction,
                $reason ?: 'Tournament cancelled refund'
            );

            if ($refund && isset($refund->id)) {
                $entry->wallet_refund_transaction_id = $refund->id;
            }
        }
    }
}
