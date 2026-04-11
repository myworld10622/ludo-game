<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ProcessLegacyBonusCommissionCommand extends Command
{
    protected $signature = 'legacy:process-bonus-commission {--limit=3} {--provider=all}';

    protected $description = 'Process legacy purchase bonuses and referral commissions for pending payments.';

    public function handle(WalletService $walletService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $provider = strtolower((string) $this->option('provider'));

        if (! $this->legacyTableExists('tbl_purchase') || ! $this->legacyTableExists('tbl_users')) {
            $this->warn('[LegacyBonus] Missing legacy tables. Skipping.');
            return self::SUCCESS;
        }

        $result = [
            'nowpayments' => 0,
            'payformee' => 0,
            'failed' => 0,
        ];

        if ($provider === 'all' || $provider === 'nowpayments') {
            $result['nowpayments'] = $this->processNowPayments($walletService, $limit);
        }

        if ($provider === 'all' || $provider === 'payformee') {
            $result['payformee'] = $this->processPayformee($walletService, $limit);
        }

        $this->info('[LegacyBonus] Bonus/commission job complete.');
        $this->line('  nowpayments processed : '.$result['nowpayments']);
        $this->line('  payformee processed   : '.$result['payformee']);

        return self::SUCCESS;
    }

    protected function processNowPayments(WalletService $walletService, int $limit): int
    {
        $apiKey = (string) env('PAYMENTAPI_KEY', '');
        if ($apiKey === '') {
            $this->warn('[LegacyBonus] PAYMENTAPI_KEY missing. Skipping nowpayments.');
            return 0;
        }

        $pending = DB::table('tbl_purchase')
            ->where('payment', 0)
            ->where('isDeleted', 0)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($pending as $purchase) {
            if (empty($purchase->razor_payment_id)) {
                continue;
            }

            try {
                $response = Http::timeout(12)
                    ->withHeaders(['x-api-key' => $apiKey])
                    ->get('https://api.nowpayments.io/v1/payment/'.$purchase->razor_payment_id);
            } catch (\Throwable $exception) {
                $this->warn('[LegacyBonus] Nowpayments HTTP error for purchase '.$purchase->id);
                continue;
            }

            if (! $response->ok()) {
                $this->warn('[LegacyBonus] Nowpayments status check failed for purchase '.$purchase->id);
                continue;
            }

            $payload = $response->json();
            if (($payload['payment_status'] ?? '') !== 'finished') {
                continue;
            }

            $this->applyLegacyPurchase($walletService, $purchase, 'nowpayments');
            $processed++;
        }

        return $processed;
    }

    protected function processPayformee(WalletService $walletService, int $limit): int
    {
        $token = (string) env('PAYFORMEE_USER_TOKEN', env('PAYFORMEE_TOKEN', ''));
        if ($token === '') {
            $this->warn('[LegacyBonus] PAYFORMEE_USER_TOKEN missing. Skipping payformee.');
            return 0;
        }

        $pending = DB::table('tbl_purchase')
            ->where('payment', 0)
            ->where('isDeleted', 0)
            ->where('transaction_type', 3)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($pending as $purchase) {
            try {
                $response = Http::timeout(12)->asForm()->post('https://Payformee.com/api/check-order-status', [
                    'user_token' => $token,
                    'order_id' => $purchase->id,
                ]);
            } catch (\Throwable $exception) {
                $this->warn('[LegacyBonus] Payformee HTTP error for purchase '.$purchase->id);
                continue;
            }

            if (! $response->ok()) {
                $this->warn('[LegacyBonus] Payformee status check failed for purchase '.$purchase->id);
                continue;
            }

            $payload = $response->json();
            if (! ($payload['status'] ?? false)) {
                continue;
            }

            $txnStatus = $payload['result']['txnStatus'] ?? '';
            if ($txnStatus === 'SUCCESS') {
                $this->applyLegacyPurchase($walletService, $purchase, 'payformee');
                $processed++;
                continue;
            }

            if ($txnStatus === 'FAILURE') {
                DB::table('tbl_purchase')
                    ->where('id', $purchase->id)
                    ->update([
                        'payment' => 2,
                        'updated_date' => now()->format('Y-m-d H:i:s'),
                    ]);
            }
        }

        return $processed;
    }

    protected function applyLegacyPurchase(WalletService $walletService, object $purchase, string $provider): void
    {
        DB::transaction(function () use ($walletService, $purchase, $provider) {
            $lockedPurchase = DB::table('tbl_purchase')
                ->where('id', $purchase->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPurchase || (int) $lockedPurchase->payment !== 0) {
                return;
            }

            DB::table('tbl_purchase')
                ->where('id', $lockedPurchase->id)
                ->update([
                    'payment' => 1,
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]);

            $legacyUser = DB::table('tbl_users')->where('id', $lockedPurchase->user_id)->first();
            if (! $legacyUser) {
                return;
            }

            $this->applyLegacyWalletCredit($lockedPurchase->user_id, (float) $lockedPurchase->coin, false);
            $this->creditLaravelWallet(
                $walletService,
                $legacyUser,
                (float) $lockedPurchase->coin,
                'Deposit credited (legacy)',
                (int) $lockedPurchase->id,
                ['legacy_provider' => $provider]
            );

            if ($provider === 'payformee') {
                $this->logStatement(
                    $lockedPurchase->user_id,
                    'Add Cash',
                    (float) $lockedPurchase->coin,
                    (int) $lockedPurchase->id,
                    0
                );
            }

            $purchaseCount = (int) DB::table('tbl_purchase')
                ->where('user_id', $lockedPurchase->user_id)
                ->where('payment', 1)
                ->where('isDeleted', 0)
                ->count();

            $settings = $this->legacySettingsRow();

            if ($purchaseCount === 1 && ! empty($legacyUser->referred_by) && $settings && isset($settings->referral_amount)) {
                $referralAmount = (float) $settings->referral_amount;
                if ($referralAmount > 0) {
                    $this->applyLegacyWalletCredit((int) $legacyUser->referred_by, $referralAmount, true);
                    $this->insertReferralBonusLog((int) $legacyUser->referred_by, (int) $legacyUser->id, $referralAmount);
                    $this->directAdminProfitStatement('Referral Bonus', -$referralAmount, (int) $legacyUser->referred_by);
                    $this->logStatement((int) $legacyUser->referred_by, 'Referral Bonus', $referralAmount, 0, 0);

                    $referrer = DB::table('tbl_users')->where('id', $legacyUser->referred_by)->first();
                    if ($referrer) {
                        $this->creditLaravelWallet(
                            $walletService,
                            $referrer,
                            $referralAmount,
                            'Referral bonus (legacy)',
                            (int) $lockedPurchase->id,
                            ['legacy_provider' => $provider, 'legacy_referral_type' => 'signup']
                        );
                    }
                }
            }

            if ($this->incomeDepositBonusEnabled()) {
                $this->applyDepositBonus(
                    $walletService,
                    $legacyUser,
                    $lockedPurchase,
                    $purchaseCount,
                    $provider
                );
            }

            $this->applyReferralLevels(
                $walletService,
                $legacyUser,
                $lockedPurchase,
                $settings,
                $provider
            );

            if ((float) $lockedPurchase->extra > 0) {
                $extraAmount = ((float) $lockedPurchase->coin) * ((float) $lockedPurchase->extra / 100);
                if ($extraAmount > 0) {
                    $this->applyLegacyWalletCredit($lockedPurchase->user_id, $extraAmount, true);
                    $this->insertExtraWalletLog($lockedPurchase->user_id, $extraAmount, 0);
                    $this->creditLaravelWallet(
                        $walletService,
                        $legacyUser,
                        $extraAmount,
                        'Deposit bonus extra (legacy)',
                        (int) $lockedPurchase->id,
                        ['legacy_provider' => $provider, 'legacy_bonus' => 'extra']
                    );
                }
            }
        });
    }

    protected function applyDepositBonus(
        WalletService $walletService,
        object $legacyUser,
        object $purchase,
        int $purchaseCount,
        string $provider
    ): void {
        if (! $this->legacyTableExists('tbl_deposit_bonus_master')) {
            return;
        }

        $bonusRow = DB::table('tbl_deposit_bonus_master')
            ->where('min', '<=', $purchase->coin)
            ->where('max', '>=', $purchase->coin)
            ->where('deposit_count', $purchaseCount)
            ->where('isDeleted', 0)
            ->orderBy('min')
            ->first();

        if (! $bonusRow) {
            return;
        }

        $uplineBonus = (float) ($bonusRow->upline_bonus ?? 0);
        $selfBonus = (float) ($bonusRow->self_bonus ?? 0);
        $adminDeduction = $uplineBonus + $selfBonus;
        $sourceLabel = match ($purchaseCount) {
            1 => '1st Deposit Bonus',
            2 => '2nd Deposit Bonus',
            3 => '3rd Deposit Bonus',
            4 => '4th Deposit Bonus',
            5 => '5th Deposit Bonus',
            default => 'Deposit Bonus',
        };

        if ($selfBonus > 0) {
            $this->applyLegacyWalletCredit($legacyUser->id, $selfBonus, true);
            $this->insertPurchaseReferLog((int) $legacyUser->id, (int) $purchase->id, (int) $purchase->user_id, $selfBonus, (float) $purchase->coin, 0);
            $this->logStatement((int) $legacyUser->id, $sourceLabel, $selfBonus, 0, 0);
            $this->creditLaravelWallet(
                $walletService,
                $legacyUser,
                $selfBonus,
                $sourceLabel.' (self)',
                (int) $purchase->id,
                ['legacy_provider' => $provider, 'legacy_bonus' => 'self']
            );
        }

        if (! empty($legacyUser->referred_by) && $uplineBonus > 0) {
            $referrerId = (int) $legacyUser->referred_by;
            $this->applyLegacyWalletCredit($referrerId, $uplineBonus, true);
            $this->insertPurchaseReferLog($referrerId, (int) $purchase->id, (int) $purchase->user_id, $uplineBonus, (float) $purchase->coin, 1);
            $this->logStatement($referrerId, $sourceLabel, $uplineBonus, 0, 0);

            $referrer = DB::table('tbl_users')->where('id', $referrerId)->first();
            if ($referrer) {
                $this->creditLaravelWallet(
                    $walletService,
                    $referrer,
                    $uplineBonus,
                    $sourceLabel.' (upline)',
                    (int) $purchase->id,
                    ['legacy_provider' => $provider, 'legacy_bonus' => 'upline']
                );
            }
        }

        if ($adminDeduction > 0) {
            $this->directAdminProfitStatement($sourceLabel, -$adminDeduction, 0);
        }
    }

    protected function applyReferralLevels(
        WalletService $walletService,
        object $legacyUser,
        object $purchase,
        ?object $settings,
        string $provider
    ): void {
        $currentUser = $legacyUser;

        for ($level = 1; $level <= 10; $level++) {
            if (empty($currentUser->referred_by)) {
                break;
            }

            $referrerId = (int) $currentUser->referred_by;
            $referrer = DB::table('tbl_users')->where('id', $referrerId)->first();
            if (! $referrer) {
                break;
            }

            $percent = 0.0;
            if ($provider === 'payformee' && $level === 1) {
                $percent = (float) ($referrer->referral_precent ?? 0);
            } elseif ($settings) {
                $levelKey = 'level_'.$level;
                $percent = (float) ($settings->{$levelKey} ?? 0);
            }

            $coins = $percent > 0 ? ((float) $purchase->coin * $percent) / 100 : 0;
            if ($coins > 0) {
                $this->applyLegacyWalletCredit($referrerId, $coins, true);
                $this->insertPurchaseReferLog($referrerId, (int) $purchase->id, (int) $purchase->user_id, $coins, (float) $purchase->coin, $level);

                if ($provider === 'payformee') {
                    $this->logStatement($referrerId, 'Referral Bonus', $coins, (int) $purchase->user_id, 0);
                }

                $this->creditLaravelWallet(
                    $walletService,
                    $referrer,
                    $coins,
                    'Referral bonus (level '.$level.')',
                    (int) $purchase->id,
                    ['legacy_provider' => $provider, 'legacy_referral_level' => $level]
                );
            }

            $currentUser = $referrer;
        }
    }

    protected function applyLegacyWalletCredit(int $userId, float $amount, bool $bonus): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::table('tbl_users')
            ->where('id', $userId)
            ->update([
                'wallet' => DB::raw('wallet + '.$amount),
                'bonus_wallet' => $bonus ? DB::raw('bonus_wallet + '.$amount) : DB::raw('bonus_wallet'),
                'unutilized_wallet' => $bonus ? DB::raw('unutilized_wallet') : DB::raw('unutilized_wallet + '.$amount),
                'todays_recharge' => $bonus ? DB::raw('todays_recharge') : DB::raw('todays_recharge + '.$amount),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    protected function creditLaravelWallet(
        WalletService $walletService,
        object $legacyUser,
        float $amount,
        string $description,
        int $referenceId,
        array $meta = []
    ): void {
        if ($amount <= 0) {
            return;
        }

        $laravelUser = $this->resolveLaravelUserFromLegacy($legacyUser);
        if (! $laravelUser) {
            return;
        }

        $exists = WalletTransaction::query()
            ->where('user_id', $laravelUser->id)
            ->where('reference_type', 'legacy_purchase')
            ->where('reference_id', $referenceId)
            ->where('description', $description)
            ->exists();

        if ($exists) {
            return;
        }

        $walletService->credit(
            user: $laravelUser,
            amount: $amount,
            referenceType: 'legacy_purchase',
            referenceId: $referenceId,
            description: $description,
            currency: 'INR',
            meta: array_merge($meta, [
                'legacy_purchase_id' => $referenceId,
                'legacy_user_id' => $legacyUser->id ?? null,
            ])
        );
    }

    protected function resolveLaravelUserFromLegacy(object $legacyUser): ?User
    {
        $query = User::query();
        $hasCriteria = false;

        if (! empty($legacyUser->mobile)) {
            $query->orWhere('mobile', $legacyUser->mobile);
            $hasCriteria = true;
        }

        if (! empty($legacyUser->email)) {
            $query->orWhere('email', $legacyUser->email);
            $hasCriteria = true;
        }

        if (! $hasCriteria) {
            return null;
        }

        return $query->first();
    }

    protected function logStatement(int $userId, string $source, float $amount, int $sourceId, float $adminCommission): void
    {
        if (! $this->legacyTableExists('tbl_statement')) {
            return;
        }

        $user = DB::table('tbl_users')->where('id', $userId)->first();
        $setting = $this->legacySettingsRow();
        $adminCoin = $setting && isset($setting->admin_coin)
            ? (float) $setting->admin_coin + $adminCommission
            : $adminCommission;

        DB::table('tbl_statement')->insert([
            'user_id' => $userId,
            'source' => $source,
            'source_id' => $sourceId,
            'user_type' => 0,
            'amount' => $amount,
            'current_wallet' => $user?->wallet ?? 0,
            'admin_commission' => $adminCommission,
            'admin_coin' => $adminCoin,
            'added_date' => now()->format('Y-m-d H:i:s'),
        ]);

        if ($this->legacyTableExists('tbl_setting')) {
            DB::table('tbl_setting')->update([
                'admin_coin' => DB::raw('admin_coin + '.$adminCommission),
            ]);
        }
    }

    protected function directAdminProfitStatement(string $source, float $adminCommission, int $sourceId): void
    {
        if (! $this->legacyTableExists('tbl_direct_admin_profit_statement')) {
            return;
        }

        $setting = $this->legacySettingsRow();
        $adminCoin = $setting && isset($setting->admin_coin)
            ? (float) $setting->admin_coin + $adminCommission
            : $adminCommission;

        if ($this->legacyTableExists('tbl_setting')) {
            DB::table('tbl_setting')->update([
                'admin_coin' => DB::raw('admin_coin + '.$adminCommission),
            ]);
        }

        DB::table('tbl_direct_admin_profit_statement')->insert([
            'source' => $source,
            'source_id' => $sourceId,
            'admin_coin' => $adminCoin,
            'admin_commission' => $adminCommission,
            'added_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function insertPurchaseReferLog(
        int $userId,
        int $purchaseId,
        int $purchaseUserId,
        float $coin,
        float $purchaseAmount,
        int $level
    ): void {
        if (! $this->legacyTableExists('tbl_purcharse_ref')) {
            return;
        }

        DB::table('tbl_purcharse_ref')->insert([
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'purchase_user_id' => $purchaseUserId,
            'coin' => $coin,
            'purchase_amount' => $purchaseAmount,
            'level' => $level,
        ]);
    }

    protected function insertReferralBonusLog(int $referrerId, int $referredUserId, float $amount): void
    {
        if (! $this->legacyTableExists('tbl_referral_bonus_log')) {
            return;
        }

        DB::table('tbl_referral_bonus_log')->insert([
            'user_id' => $referrerId,
            'referred_user_id' => $referredUserId,
            'coin' => $amount,
            'added_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function insertExtraWalletLog(int $userId, float $amount, int $type): void
    {
        if (! $this->legacyTableExists('tbl_extra_wallet_log')) {
            return;
        }

        DB::table('tbl_extra_wallet_log')->insert([
            'user_id' => $userId,
            'coin' => $amount,
            'type' => $type,
            'added_date' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function legacySettingsRow(): ?object
    {
        if (! $this->legacyTableExists('tbl_setting')) {
            return null;
        }

        return DB::table('tbl_setting')
            ->orderByDesc('id')
            ->first();
    }

    protected function incomeDepositBonusEnabled(): bool
    {
        return (bool) env('LEGACY_INCOME_DEPOSIT_BONUS', false);
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
