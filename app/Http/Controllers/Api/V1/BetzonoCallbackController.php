<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BetzonoCallbackController extends Controller
{
    public function deposit(Request $request, WalletService $walletService): JsonResponse
    {
        if (! $this->isValidSignature($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $trx = (string) data_get($payload, 'trx', '');
        if ($trx === '') {
            return response()->json(['message' => 'Missing transaction id'], 422);
        }

        $status = $this->normalizeStatus((string) data_get($payload, 'status', 'pending'));
        $gatewayStatus = (string) data_get($payload, 'gateway_status', '');
        $amount = (float) data_get($payload, 'amount', 0);
        $currency = (string) data_get($payload, 'currency', 'INR');

        $user = $this->resolveUserFromPayload($payload, $trx);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $gatewayTx = $this->upsertGatewayTransaction(
            trx: $trx,
            type: 'deposit',
            userId: $user->id,
            amount: $amount,
            currency: $currency,
            status: $status,
            gatewayStatus: $gatewayStatus,
            payload: $payload
        );

        if ($status === 'success' && empty($gatewayTx->wallet_transaction_id)) {
            $walletTx = $walletService->credit(
                user: $user,
                amount: $amount,
                referenceType: WalletTransaction::class,
                referenceId: $gatewayTx->id,
                description: 'Deposit via Betzono',
                currency: $currency,
                meta: [
                    'gateway' => 'betzono',
                    'trx' => $trx,
                ],
            );

            DB::table('rox_gateway_transactions')
                ->where('id', $gatewayTx->id)
                ->update([
                    'wallet_transaction_id' => $walletTx->id,
                    'status' => 'success',
                    'updated_at' => now(),
                ]);

            $this->syncLegacyUserBalance($user, $amount);
        }

        return response()->json(['message' => 'OK']);
    }

    public function withdraw(Request $request, WalletService $walletService): JsonResponse
    {
        if (! $this->isValidSignature($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $trx = (string) data_get($payload, 'trx', '');
        if ($trx === '') {
            return response()->json(['message' => 'Missing transaction id'], 422);
        }

        $status = $this->normalizeStatus((string) data_get($payload, 'status', 'pending'));
        $gatewayStatus = (string) data_get($payload, 'gateway_status', '');
        $amount = (float) data_get($payload, 'amount', 0);
        $currency = (string) data_get($payload, 'currency', 'INR');

        $user = $this->resolveUserFromPayload($payload, $trx);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $gatewayTx = $this->upsertGatewayTransaction(
            trx: $trx,
            type: 'withdraw',
            userId: $user->id,
            amount: $amount,
            currency: $currency,
            status: $status,
            gatewayStatus: $gatewayStatus,
            payload: $payload
        );

        $this->updateLegacyWithdrawalStatus($trx, $status, $payload);

        if ($status === 'rejected' && empty($gatewayTx->refund_wallet_transaction_id)) {
            $walletTx = $walletService->credit(
                user: $user,
                amount: $amount,
                referenceType: WalletTransaction::class,
                referenceId: $gatewayTx->id,
                description: 'Withdrawal failed refund',
                currency: $currency,
                meta: [
                    'gateway' => 'betzono',
                    'trx' => $trx,
                    'reason' => 'withdraw_rejected',
                ],
            );

            DB::table('rox_gateway_transactions')
                ->where('id', $gatewayTx->id)
                ->update([
                    'refund_wallet_transaction_id' => $walletTx->id,
                    'updated_at' => now(),
                ]);

            $this->syncLegacyUserBalance($user, $amount);
        }

        return response()->json(['message' => 'OK']);
    }

    private function isValidSignature(Request $request): bool
    {
        $secret = (string) env('ROX_CALLBACK_SECRET', '');
        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-ROX-SIGNATURE', '');
        if ($signature === '') {
            return false;
        }

        $rawBody = $request->getContent();
        $fallbackJson = json_encode($request->all());
        $normalizedRaw = str_replace("\r\n", "\n", $rawBody);

        $candidates = array_filter([
            $rawBody !== '' ? hash_hmac('sha256', $rawBody, $secret) : null,
            $normalizedRaw !== '' ? hash_hmac('sha256', $normalizedRaw, $secret) : null,
            $fallbackJson !== false ? hash_hmac('sha256', $fallbackJson, $secret) : null,
        ]);

        foreach ($candidates as $expected) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeStatus(string $status): string
    {
        $value = strtolower(trim($status));
        if ($value === 'success') {
            return 'success';
        }
        if (in_array($value, ['rejected', 'cancel', 'declined', 'failed'], true)) {
            return 'rejected';
        }

        return 'pending';
    }

    private function resolveUserFromPayload(array $payload, string $trx): ?User
    {
        $userId = data_get($payload, 'user_id');
        if (! empty($userId)) {
            $user = User::query()->where('id', $userId)->first();
            if ($user) {
                return $user;
            }

            $user = User::query()->where('user_code', $userId)->first();
            if ($user) {
                return $user;
            }
        }

        if (str_starts_with($trx, 'ROX-')) {
            $parts = explode('-', $trx);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                return User::query()->where('id', (int) $parts[1])->first()
                    ?: User::query()->where('user_code', $parts[1])->first();
            }
        }

        return null;
    }

    private function upsertGatewayTransaction(
        string $trx,
        string $type,
        int $userId,
        float $amount,
        string $currency,
        string $status,
        string $gatewayStatus,
        array $payload
    ): object {
        $existing = DB::table('rox_gateway_transactions')->where('trx', $trx)->first();

        if ($existing) {
            DB::table('rox_gateway_transactions')
                ->where('id', $existing->id)
                ->update([
                    'status' => $status,
                    'gateway_status' => $gatewayStatus,
                    'payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);

            return DB::table('rox_gateway_transactions')->where('id', $existing->id)->first();
        }

        $id = DB::table('rox_gateway_transactions')->insertGetId([
            'trx' => $trx,
            'type' => $type,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'gateway_status' => $gatewayStatus,
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('rox_gateway_transactions')->where('id', $id)->first();
    }

    private function syncLegacyUserBalance(User $user, float $amount): void
    {
        if (! Schema::hasTable('tbl_users')) {
            return;
        }

        $legacy = DB::table('tbl_users')
            ->where('mobile', $user->mobile)
            ->orWhere('email', $user->email)
            ->orderByDesc('id')
            ->first();

        if (! $legacy) {
            DB::table('tbl_users')->insert([
                'name' => $user->profile?->first_name ?? $user->username,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'wallet' => $amount,
                'bonus_wallet' => 0,
                'winning_wallet' => 0,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
                'isDeleted' => 0,
            ]);
            return;
        }

        DB::table('tbl_users')
            ->where('id', $legacy->id)
            ->update([
                'wallet' => DB::raw('wallet + '.$amount),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    private function updateLegacyWithdrawalStatus(string $trx, string $status, array $payload): void
    {
        if (! Schema::hasTable('tbl_withdrawal_log')) {
            return;
        }

        $mapped = $status === 'success' ? 1 : ($status === 'rejected' ? 2 : 0);

        DB::table('tbl_withdrawal_log')
            ->where('transaction_id', $trx)
            ->update([
                'status' => $mapped,
                'payout_response' => json_encode($payload),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);
    }
}
