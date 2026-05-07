<?php

namespace App\Services\Wallet;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WalletService
{
    public function summary(User $user, string $walletType = 'cash', string $currency = 'INR'): ?Wallet
    {
        return Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', $walletType)
            ->where('currency', $currency)
            ->first();
    }

    public function history(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->with(['game', 'tournament'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function debitForTournamentEntry(User $user, Tournament $tournament, array $meta = []): WalletTransaction
    {
        return $this->debit(
            user: $user,
            amount: (float) $tournament->entry_fee,
            referenceType: Tournament::class,
            referenceId: $tournament->id,
            description: 'Tournament entry fee',
            currency: $tournament->currency,
            gameId: $tournament->game_id,
            tournamentId: $tournament->id,
            meta: $meta,
        );
    }

    public function creditPrize(User $user, Tournament $tournament, float $amount, array $meta = []): WalletTransaction
    {
        return $this->credit(
            user: $user,
            amount: $amount,
            referenceType: Tournament::class,
            referenceId: $tournament->id,
            description: 'Tournament prize credit',
            currency: $tournament->currency,
            gameId: $tournament->game_id,
            tournamentId: $tournament->id,
            meta: $meta,
        );
    }

    public function refundTournamentEntry(User $user, Tournament $tournament, float $amount, array $meta = []): WalletTransaction
    {
        return $this->refund(
            user: $user,
            amount: $amount,
            referenceType: Tournament::class,
            referenceId: $tournament->id,
            description: 'Tournament entry refund',
            currency: $tournament->currency,
            gameId: $tournament->game_id,
            tournamentId: $tournament->id,
            meta: $meta,
        );
    }

    public function credit(
        User $user,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency = 'INR',
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return $this->applyTransaction(
            user: $user,
            type: 'credit',
            direction: 'credit',
            amount: $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            currency: $currency,
            gameId: $gameId,
            tournamentId: $tournamentId,
            meta: $meta,
        );
    }

    public function debit(
        User $user,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency = 'INR',
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return $this->applyTransaction(
            user: $user,
            type: 'debit',
            direction: 'debit',
            amount: $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            currency: $currency,
            gameId: $gameId,
            tournamentId: $tournamentId,
            meta: $meta,
        );
    }

    public function hold(
        User $user,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency = 'INR',
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $referenceType, $referenceId, $description, $currency, $gameId, $tournamentId, $meta) {
            $wallet = $this->resolveLockedWallet($user, $currency);
            $openingBalance = (string) $wallet->balance;

            if ((float) $wallet->balance < $amount) {
                throw new HttpException(422, 'Insufficient wallet balance.');
            }

            $wallet->forceFill([
                'balance' => number_format(((float) $wallet->balance - $amount), 4, '.', ''),
                'locked_balance' => number_format(((float) $wallet->locked_balance + $amount), 4, '.', ''),
                'last_transaction_at' => now(),
            ])->save();

            return $this->storeTransaction(
                wallet: $wallet,
                user: $user,
                type: 'hold',
                direction: 'debit',
                status: 'held',
                amount: $amount,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                currency: $currency,
                openingBalance: $openingBalance,
                closingBalance: (string) $wallet->balance,
                gameId: $gameId,
                tournamentId: $tournamentId,
                meta: $meta,
            );
        });
    }

    public function refund(
        User $user,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency = 'INR',
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return $this->applyTransaction(
            user: $user,
            type: 'refund',
            direction: 'credit',
            amount: $amount,
            referenceType: $referenceType,
            referenceId: $referenceId,
            description: $description,
            currency: $currency,
            gameId: $gameId,
            tournamentId: $tournamentId,
            meta: $meta,
        );
    }

    public function transfer(
        User $sender,
        User $receiver,
        float $amount,
        string $currency = 'INR',
        ?string $note = null,
        array $meta = []
    ): WalletTransfer {
        if ($sender->id === $receiver->id) {
            throw new HttpException(422, 'You cannot transfer to your own account.');
        }

        if ($amount <= 0) {
            throw new HttpException(422, 'Transfer amount must be greater than zero.');
        }

        return DB::transaction(function () use ($sender, $receiver, $amount, $currency, $note, $meta) {
            $normalizedCurrency = strtoupper(trim($currency)) ?: 'INR';

            if ($sender->id < $receiver->id) {
                $senderWallet = $this->resolveLockedWallet($sender, $normalizedCurrency);
                $receiverWallet = $this->resolveLockedWallet($receiver, $normalizedCurrency);
            } else {
                $receiverWallet = $this->resolveLockedWallet($receiver, $normalizedCurrency);
                $senderWallet = $this->resolveLockedWallet($sender, $normalizedCurrency);
            }

            $senderOpeningBalance = (string) $senderWallet->balance;
            $receiverOpeningBalance = (string) $receiverWallet->balance;

            if ((float) $senderWallet->balance < $amount) {
                throw new HttpException(422, 'Insufficient wallet balance.');
            }

            $senderClosingBalance = number_format(((float) $senderWallet->balance - $amount), 4, '.', '');
            $receiverClosingBalance = number_format(((float) $receiverWallet->balance + $amount), 4, '.', '');

            $senderWallet->forceFill([
                'balance' => $senderClosingBalance,
                'last_transaction_at' => now(),
            ])->save();

            $receiverWallet->forceFill([
                'balance' => $receiverClosingBalance,
                'last_transaction_at' => now(),
            ])->save();

            $transfer = WalletTransfer::query()->create([
                'transfer_uuid' => 'TRF-'.strtoupper(Str::random(12)),
                'sender_user_id' => $sender->id,
                'receiver_user_id' => $receiver->id,
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $amount,
                'currency' => $normalizedCurrency,
                'status' => 'completed',
                'note' => $note,
                'meta' => array_merge($meta, [
                    'sender_user_code' => (string) $sender->user_code,
                    'receiver_user_code' => (string) $receiver->user_code,
                ]),
                'processed_at' => now(),
            ]);

            $senderTransaction = $this->storeTransaction(
                wallet: $senderWallet,
                user: $sender,
                type: 'transfer_sent',
                direction: 'debit',
                status: 'completed',
                amount: $amount,
                referenceType: WalletTransfer::class,
                referenceId: $transfer->id,
                description: 'Wallet transfer sent to '.$receiver->username,
                currency: $normalizedCurrency,
                openingBalance: $senderOpeningBalance,
                closingBalance: $senderClosingBalance,
                meta: array_merge($meta, [
                    'transfer_uuid' => $transfer->transfer_uuid,
                    'counterparty_user_id' => $receiver->id,
                    'counterparty_user_code' => (string) $receiver->user_code,
                    'counterparty_username' => (string) $receiver->username,
                ]),
            );

            $receiverTransaction = $this->storeTransaction(
                wallet: $receiverWallet,
                user: $receiver,
                type: 'transfer_received',
                direction: 'credit',
                status: 'completed',
                amount: $amount,
                referenceType: WalletTransfer::class,
                referenceId: $transfer->id,
                description: 'Wallet transfer received from '.$sender->username,
                currency: $normalizedCurrency,
                openingBalance: $receiverOpeningBalance,
                closingBalance: $receiverClosingBalance,
                meta: array_merge($meta, [
                    'transfer_uuid' => $transfer->transfer_uuid,
                    'counterparty_user_id' => $sender->id,
                    'counterparty_user_code' => (string) $sender->user_code,
                    'counterparty_username' => (string) $sender->username,
                ]),
            );

            $transfer->forceFill([
                'sender_wallet_transaction_id' => $senderTransaction->id,
                'receiver_wallet_transaction_id' => $receiverTransaction->id,
            ])->save();

            return $transfer->fresh([
                'sender',
                'receiver',
                'senderTransaction',
                'receiverTransaction',
            ]);
        });
    }

    public function captureHeldTransaction(
        WalletTransaction $holdTransaction,
        ?string $description = null,
        array $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($holdTransaction, $description, $meta) {
            $transaction = WalletTransaction::query()
                ->whereKey($holdTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== 'hold') {
                throw new HttpException(422, 'Only held transactions can be captured.');
            }

            if ($transaction->status !== 'held') {
                return $transaction;
            }

            $wallet = Wallet::query()
                ->whereKey($transaction->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            $wallet->forceFill([
                'locked_balance' => number_format(max(0, (float) $wallet->locked_balance - (float) $transaction->amount), 4, '.', ''),
                'last_transaction_at' => now(),
            ])->save();

            $transaction->forceFill([
                'status' => 'completed',
                'description' => $description ?: $transaction->description,
                'meta' => array_merge($transaction->meta ?? [], $meta, [
                    'hold_captured_at' => now()->toIso8601String(),
                ]),
                'processed_at' => now(),
            ])->save();

            return $transaction->fresh();
        });
    }

    public function refundHeldTransaction(
        WalletTransaction $holdTransaction,
        ?string $description = null,
        array $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use ($holdTransaction, $description, $meta) {
            $transaction = WalletTransaction::query()
                ->whereKey($holdTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== 'hold') {
                throw new HttpException(422, 'Only held transactions can be refunded.');
            }

            if ($transaction->status === 'refunded') {
                return $transaction;
            }

            $wallet = Wallet::query()
                ->whereKey($transaction->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status === 'held') {
                $wallet->forceFill([
                    'balance' => number_format(((float) $wallet->balance + (float) $transaction->amount), 4, '.', ''),
                    'locked_balance' => number_format(max(0, (float) $wallet->locked_balance - (float) $transaction->amount), 4, '.', ''),
                    'last_transaction_at' => now(),
                ])->save();
            }

            $transaction->forceFill([
                'status' => 'refunded',
                'description' => $description ?: $transaction->description,
                'meta' => array_merge($transaction->meta ?? [], $meta, [
                    'hold_refunded_at' => now()->toIso8601String(),
                ]),
                'processed_at' => now(),
            ])->save();

            return $this->storeTransaction(
                wallet: $wallet,
                user: $transaction->user,
                type: 'refund',
                direction: 'credit',
                status: 'completed',
                amount: (float) $transaction->amount,
                referenceType: $transaction->reference_type ?: WalletTransaction::class,
                referenceId: $transaction->reference_id ?: $transaction->id,
                description: $description ?: 'Held amount refunded',
                currency: $transaction->currency,
                openingBalance: $transaction->status === 'held'
                    ? number_format(((float) $wallet->balance - (float) $transaction->amount), 4, '.', '')
                    : (string) $wallet->balance,
                closingBalance: (string) $wallet->balance,
                gameId: $transaction->game_id,
                tournamentId: $transaction->tournament_id,
                meta: array_merge($meta, [
                    'source_hold_transaction_uuid' => $transaction->transaction_uuid,
                ]),
            );
        });
    }

    protected function applyTransaction(
        User $user,
        string $type,
        string $direction,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency = 'INR',
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return DB::transaction(function () use (
            $user,
            $type,
            $direction,
            $amount,
            $referenceType,
            $referenceId,
            $description,
            $currency,
            $gameId,
            $tournamentId,
            $meta
        ) {
            $wallet = $this->resolveLockedWallet($user, $currency);
            $openingBalance = (string) $wallet->balance;

            if ($direction === 'debit' && (float) $wallet->balance < $amount) {
                throw new HttpException(422, 'Insufficient wallet balance.');
            }

            $closingBalance = $direction === 'debit'
                ? number_format(((float) $wallet->balance - $amount), 4, '.', '')
                : number_format(((float) $wallet->balance + $amount), 4, '.', '');

            $wallet->forceFill([
                'balance' => $closingBalance,
                'last_transaction_at' => now(),
            ])->save();

            return $this->storeTransaction(
                wallet: $wallet,
                user: $user,
                type: $type,
                direction: $direction,
                status: 'completed',
                amount: $amount,
                referenceType: $referenceType,
                referenceId: $referenceId,
                description: $description,
                currency: $currency,
                openingBalance: $openingBalance,
                closingBalance: $closingBalance,
                gameId: $gameId,
                tournamentId: $tournamentId,
                meta: $meta,
            );
        });
    }

    protected function resolveLockedWallet(User $user, string $currency): Wallet
    {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', 'cash')
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => 'cash',
            'currency' => $currency,
            'balance' => 0,
            'locked_balance' => 0,
            'is_active' => true,
        ]);

        return Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', 'cash')
            ->where('currency', $currency)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function storeTransaction(
        Wallet $wallet,
        User $user,
        string $type,
        string $direction,
        string $status,
        float $amount,
        string $referenceType,
        int|string|null $referenceId,
        string $description,
        string $currency,
        string $openingBalance,
        string $closingBalance,
        ?int $gameId = null,
        ?int $tournamentId = null,
        array $meta = []
    ): WalletTransaction {
        return WalletTransaction::query()->create([
            'transaction_uuid' => (string) Str::uuid(),
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'game_id' => $gameId,
            'tournament_id' => $tournamentId,
            'type' => $type,
            'direction' => $direction,
            'status' => $status,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'balance_before' => $openingBalance,
            'balance_after' => $closingBalance,
            'currency' => $currency,
            'description' => $description,
            'meta' => $meta,
            'processed_at' => now(),
        ]);
    }
}
