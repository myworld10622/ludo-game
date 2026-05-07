<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class PlanCompatibilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id'),
            $request->input('token') ?: $request->input('Token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
                'PlanDetails' => [],
            ]);
        }

        if (! $this->legacyTableExists('tbl_coin_plan')) {
            return response()->json([
                'message' => 'Success',
                'code' => 200,
                'PlanDetails' => [],
            ]);
        }

        $plans = DB::table('tbl_coin_plan')
            ->where('isDeleted', 0)
            ->orderByDesc('id')
            ->get();

        if ($plans->isEmpty()) {
            $now = Carbon::now()->toDateTimeString();
            $fallbackPlans = [
                (object) [
                    'id' => 1,
                    'coin' => '100',
                    'price' => '100',
                    'title' => 'Starter',
                    'added_date' => $now,
                    'updated_date' => $now,
                    'isDeleted' => 0,
                ],
                (object) [
                    'id' => 2,
                    'coin' => '500',
                    'price' => '500',
                    'title' => 'Plus',
                    'added_date' => $now,
                    'updated_date' => $now,
                    'isDeleted' => 0,
                ],
                (object) [
                    'id' => 3,
                    'coin' => '2500',
                    'price' => '2500',
                    'title' => 'Pro',
                    'added_date' => $now,
                    'updated_date' => $now,
                    'isDeleted' => 0,
                ],
                (object) [
                    'id' => 4,
                    'coin' => '5000',
                    'price' => '5000',
                    'title' => 'Elite',
                    'added_date' => $now,
                    'updated_date' => $now,
                    'isDeleted' => 0,
                ],
                (object) [
                    'id' => 5,
                    'coin' => '10000',
                    'price' => '10000',
                    'title' => 'Mega',
                    'added_date' => $now,
                    'updated_date' => $now,
                    'isDeleted' => 0,
                ],
            ];

            return response()->json([
                'code' => 200,
                'message' => 'Success',
                'PlanDetails' => $fallbackPlans,
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Success',
            'PlanDetails' => $plans,
        ]);
    }

    public function placeOrderUpiGateway(Request $request): JsonResponse
    {
        try {
            $user = $this->resolveLegacyUser(
                $request->input('user_id'),
                $request->input('token') ?: $request->input('Token')
            );

            if (! $user) {
                return response()->json([
                    'message' => 'Invalid User',
                    'code' => 404,
                ]);
            }

            $amount = (float) $request->input('amount', 0);
            if ($amount <= 0) {
                return response()->json([
                    'message' => 'Invalid amount',
                    'code' => 422,
                ]);
            }

            $planId = $request->input('plan_id');
            $legacyUser = $this->resolveLegacyDbUser($user);
            if (! $legacyUser) {
                return response()->json([
                    'message' => 'Invalid User',
                    'code' => 404,
                ]);
            }

            $coin = $amount;
            if ($planId && $this->legacyTableExists('tbl_coin_plan')) {
                $plan = DB::table('tbl_coin_plan')
                    ->where('id', $planId)
                    ->first();
                if ($plan && isset($plan->coin)) {
                    $coin = (float) $plan->coin;
                }
            }

            $orderId = (string) Str::uuid();
            if ($this->legacyTableExists('tbl_purchase')) {
                $orderId = (string) DB::table('tbl_purchase')->insertGetId([
                    'user_id' => $legacyUser->id,
                    'plan_id' => $planId ?: 0,
                    'coin' => $coin,
                    'price' => $amount,
                    'payment' => 0,
                    'status' => 0,
                    'transaction_type' => 0,
                    'transaction_id' => null,
                    'extra' => 0,
                    'razor_payment_id' => null,
                    'json_response' => null,
                    'photo' => null,
                    'utr' => null,
                    'added_date' => Carbon::now(),
                    'updated_date' => Carbon::now(),
                    'isDeleted' => 0,
                ]);
            }

            $transactionId = 'ROX-'.$legacyUser->id.'-'.$orderId;
            $betzonoUrl = $this->getBetzonoDepositUrl();
            $merchantId = $this->getBetzonoMerchantId();
            $proxyUserId = $this->getBetzonoProxyUserId();
            $proxyEmail = $this->getBetzonoProxyEmail();
            $proxyPhone = $this->getBetzonoProxyPhone();
            $debugFallbackUrl = $this->getBetzonoDebugFallbackUrl();

            if (
                $merchantId === ''
                || $proxyUserId === 0
                || $proxyEmail === ''
                || $proxyPhone === ''
                || ! filter_var($betzonoUrl, FILTER_VALIDATE_URL)
            ) {
                return response()->json([
                    'message' => 'Automatic gateway not configured',
                    'code' => 422,
                ]);
            }

            $payload = [
                'userId' => $proxyUserId,
                'merchantId' => $merchantId,
                'amount' => $amount,
                'phoneNumber' => $proxyPhone,
                'email' => $proxyEmail,
                'metaData' => [
                    'TransactionId' => $transactionId,
                    'rox_user_id' => $legacyUser->id,
                    'user_id' => $user->id,
                    'app' => 'rox_ludo',
                    'source' => 'rox_ludo',
                ],
            ];

            $this->upsertGatewayInitTransaction(
                trx: $transactionId,
                userId: $user->id,
                legacyUserId: (int) $legacyUser->id,
                legacyOrderId: (int) $orderId,
                amount: $amount,
                currency: 'INR',
                status: 'pending',
                gatewayStatus: 'initiated',
                requestPayload: $payload
            );

            $response = Http::timeout(15)->acceptJson()->post($betzonoUrl, $payload);
            if (! $response->successful()) {
                Log::warning('rox_ludo.gateway_init_failed', [
                    'betzono_url' => $betzonoUrl,
                    'http_status' => $response->status(),
                    'response_body' => $response->body(),
                    'payload' => $payload,
                    'transaction_id' => $transactionId,
                ]);

                if ($debugFallbackUrl !== '') {
                    return $this->buildDebugGatewayFallbackResponse(
                        $orderId,
                        $amount,
                        $transactionId,
                        $debugFallbackUrl,
                        'Gateway init failed, using debug fallback URL'
                    );
                }

                return response()->json([
                    'message' => 'Payment gateway error',
                    'code' => 500,
                ]);
            }

            $apiData = $response->json() ?? [];
            $intentData = data_get($apiData, 'data.payment_url')
                ?? (is_string(data_get($apiData, 'data')) ? data_get($apiData, 'data') : null)
                ?? data_get($apiData, 'payment_url')
                ?? data_get($apiData, 'intent_url')
                ?? data_get($apiData, 'intentData')
                ?? data_get($apiData, 'url');

            if (! $intentData) {
                return response()->json([
                    'message' => data_get($apiData, 'message', 'Gateway response invalid'),
                    'code' => 500,
                ]);
            }

            $gatewayTxn = data_get($apiData, 'traId')
                ?? data_get($apiData, 'data.order_id')
                ?? data_get($apiData, 'order_id')
                ?? data_get($apiData, 'data.traId');

            $wrapperUrl = $this->buildGatewayWrapperUrl($transactionId);

            $this->upsertGatewayInitTransaction(
                trx: $transactionId,
                userId: $user->id,
                legacyUserId: (int) $legacyUser->id,
                legacyOrderId: (int) $orderId,
                amount: $amount,
                currency: 'INR',
                status: 'pending',
                gatewayStatus: 'hosted_url_ready',
                requestPayload: $payload,
                responsePayload: $apiData,
                paymentUrl: (string) $intentData,
                gatewayTransactionId: $gatewayTxn ? (string) $gatewayTxn : null
            );

            if ($this->legacyTableExists('tbl_purchase')) {
                DB::table('tbl_purchase')
                    ->where('id', $orderId)
                    ->update([
                        'razor_payment_id' => $gatewayTxn ?: $transactionId,
                        'transaction_id' => $transactionId,
                        'json_response' => json_encode([
                            'payment_url' => $intentData,
                            'wrapper_url' => $wrapperUrl,
                            'gateway_response' => $apiData,
                        ]),
                        'updated_date' => Carbon::now(),
                    ]);
            }

            return response()->json([
                'order_id' => (int) $orderId,
                'Total_Amount' => (string) $amount,
                'intentData' => $wrapperUrl,
                'transaction_id' => $transactionId,
                'gateway_transaction_id' => $gatewayTxn ?: $transactionId,
                'message' => 'Success',
                'code' => 200,
            ]);
        } catch (\Throwable $exception) {
            Log::error('rox_ludo.gateway_init_exception', [
                'message' => $exception->getMessage(),
                'betzono_url' => $betzonoUrl ?? null,
                'merchant_id' => $merchantId ?? null,
                'proxy_user_id' => $proxyUserId ?? null,
                'transaction_id' => $transactionId ?? null,
            ]);

            if (($debugFallbackUrl ?? '') !== '' && isset($orderId, $amount, $transactionId)) {
                return $this->buildDebugGatewayFallbackResponse(
                    $orderId,
                    $amount,
                    $transactionId,
                    $debugFallbackUrl,
                    'Gateway exception, using debug fallback URL'
                );
            }

            return response()->json([
                'message' => 'Payment gateway error',
                'code' => 500,
            ]);
        }
    }

    public function getQr(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id'),
            $request->input('token') ?: $request->input('Token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 404,
            ]);
        }

        $qrUrl = (string) env('MANUAL_QR_URL', '');
        $upiId = (string) env('MANUAL_UPI_ID', '');

        if ($qrUrl === '' && $this->legacyTableExists('tbl_setting')) {
            $setting = DB::table('tbl_setting')->first();
            if ($setting) {
                $upiId = $upiId !== '' ? $upiId : (string) ($setting->upi_id ?? '');
                $qrImage = (string) ($setting->qr_image ?? '');
                if ($qrImage !== '') {
                    $qrUrl = str_starts_with($qrImage, 'http')
                        ? $qrImage
                        : url('data/Settings/'.$qrImage);
                }
            }
        }

        return response()->json([
            'code' => 200,
            'message' => 'Success',
            'qr_image' => $qrUrl,
            'upi_id' => $upiId,
        ]);
    }

    public function getUsdtQr(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id'),
            $request->input('token') ?: $request->input('Token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 404,
            ]);
        }

        $qrUrl = (string) env('MANUAL_USDT_QR_URL', '');
        $address = (string) env('MANUAL_USDT_ADDRESS', '');

        if (($qrUrl === '' || $address === '') && $this->legacyTableExists('tbl_setting')) {
            $setting = DB::table('tbl_setting')->first();
            if ($setting) {
                if ($address === '') {
                    $address = (string) ($setting->usdt_address ?? '');
                }
                if ($qrUrl === '') {
                    $qrImage = (string) ($setting->usdt_qr_image ?? '');
                    if ($qrImage !== '') {
                        $qrUrl = str_starts_with($qrImage, 'http')
                            ? $qrImage
                            : url('data/Settings/'.$qrImage);
                    }
                }
            }
        }

        return response()->json([
            'code' => 200,
            'message' => 'Success',
            'qr_image' => $qrUrl,
            'usdt_address' => $address,
        ]);
    }

    public function addCash(Request $request): JsonResponse
    {
        try {
            $user = $this->resolveLegacyUser(
                $request->input('user_id'),
                $request->input('token') ?: $request->input('Token')
            );

            if (! $user) {
                return response()->json([
                    'message' => 'Invalid User',
                    'code' => 404,
                ]);
            }

            $legacyUser = $this->resolveLegacyDbUser($user);
            $legacyUserId = $legacyUser?->id ?? (int) $user->id;

            $price = (float) $request->input('price', 0);
            if ($price <= 0) {
                return response()->json([
                    'message' => 'Invalid amount',
                    'code' => 422,
                ]);
            }

            if (! $this->legacyTableExists('tbl_purchase')) {
                return response()->json([
                    'code' => 500,
                    'message' => 'Manual payment unavailable',
                ]);
            }

            $screenshotFilename = $this->storeManualPaymentScreenshot(
                (string) $request->input('ss_image', '')
            );

            DB::table('tbl_purchase')->insert([
                'user_id' => $legacyUserId,
                'plan_id' => 0,
                'coin' => $price,
                'price' => $price,
                'payment' => 0,
                'status' => 0,
                'transaction_type' => 0,
                'transaction_id' => null,
                'extra' => 0,
                'razor_payment_id' => null,
                'json_response' => null,
                'photo' => $screenshotFilename !== '' ? $screenshotFilename : null,
                'utr' => (string) $request->input('utr', ''),
                'added_date' => Carbon::now(),
                'updated_date' => Carbon::now(),
                'isDeleted' => 0,
            ]);

            return response()->json([
                'code' => 200,
                'message' => 'Thank you Request Submitted',
                'Utr' => (string) $request->input('utr', ''),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'code' => 500,
                'message' => config('app.debug')
                    ? 'Manual payment failed: '.$exception->getMessage()
                    : 'Manual payment failed',
            ]);
        }
    }

    public function paymentStatus(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser(
            $request->input('user_id'),
            $request->input('token') ?: $request->input('Token')
        );

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 404,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser || ! $this->legacyTableExists('tbl_purchase')) {
            return response()->json([
                'message' => 'Payment request not found',
                'code' => 404,
            ]);
        }

        $query = DB::table('tbl_purchase')->where('user_id', $legacyUser->id);
        $orderId = trim((string) $request->input('order_id', ''));
        $transactionId = trim((string) $request->input('transaction_id', ''));

        if ($orderId !== '') {
            $query->where('id', $orderId);
        } elseif ($transactionId !== '') {
            $query->where('transaction_id', $transactionId);
        } else {
            return response()->json([
                'message' => 'Missing payment reference',
                'code' => 422,
            ]);
        }

        $purchase = $query->orderByDesc('id')->first();
        if (! $purchase) {
            return response()->json([
                'message' => 'Payment request not found',
                'code' => 404,
            ]);
        }

        $statusCode = (int) ($purchase->status ?? 0);

        return response()->json([
            'code' => 200,
            'message' => 'Success',
            'order_id' => (int) $purchase->id,
            'transaction_id' => (string) ($purchase->transaction_id ?? ''),
            'gateway_transaction_id' => (string) ($purchase->razor_payment_id ?? ''),
            'status' => (string) $statusCode,
            'status_label' => $this->mapLegacyPurchaseStatusLabel($statusCode),
            'is_terminal' => in_array($statusCode, [1, 2], true),
            'amount' => (string) ($purchase->price ?? '0'),
            'updated_date' => (string) ($purchase->updated_date ?? ''),
        ]);
    }

    protected function resolveLegacyUser($id, $token): ?User
    {
        if (! $id || ! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken((string) $token);
        if (! $accessToken) {
            return null;
        }

        $user = User::query()->find($accessToken->tokenable_id);
        if (! $user) {
            return null;
        }

        if ($user->user_code !== (string) $id && (string) $user->id !== (string) $id) {
            return null;
        }

        return $user;
    }

    protected function resolveLegacyDbUser(User $user): ?object
    {
        if (! $this->legacyTableExists('tbl_users')) {
            return null;
        }

        $hasUserId = Schema::hasColumn('tbl_users', 'user_id');
        $hasId = Schema::hasColumn('tbl_users', 'id');
        $hasMobile = Schema::hasColumn('tbl_users', 'mobile');
        $hasEmail = Schema::hasColumn('tbl_users', 'email');

        $query = DB::table('tbl_users');
        if ($hasUserId) {
            $query->where('user_id', $user->id);
        } elseif ($hasId) {
            $query->where('id', $user->id);
        } elseif ($hasMobile && $user->mobile) {
            $query->where('mobile', $user->mobile);
        } elseif ($hasEmail && $user->email) {
            $query->where('email', $user->email);
        }

        $legacy = $query->first();
        if ($legacy) {
            return $legacy;
        }

        $insert = [];
        if ($hasUserId) {
            $insert['user_id'] = $user->id;
        }
        if (Schema::hasColumn('tbl_users', 'name')) {
            $insert['name'] = $user->username;
        }
        if ($hasEmail) {
            $insert['email'] = $user->email ?? '';
        }
        if ($hasMobile) {
            $insert['mobile'] = $user->mobile ?? '';
        }
        if (Schema::hasColumn('tbl_users', 'profile_pic')) {
            $insert['profile_pic'] = '';
        }
        if (Schema::hasColumn('tbl_users', 'referral_code')) {
            $insert['referral_code'] = $user->referral_code ?? '';
        }
        if (Schema::hasColumn('tbl_users', 'status')) {
            $insert['status'] = 1;
        }
        if (Schema::hasColumn('tbl_users', 'created_date')) {
            $insert['created_date'] = now();
        }
        if (Schema::hasColumn('tbl_users', 'updated_date')) {
            $insert['updated_date'] = now();
        }

        $legacyId = DB::table('tbl_users')->insertGetId($insert);

        return DB::table('tbl_users')->where('id', $legacyId)->first();
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function storeManualPaymentScreenshot(string $base64): string
    {
        if ($base64 === '') {
            return '';
        }

        if (str_contains($base64, ',')) {
            $parts = explode(',', $base64, 2);
            $base64 = $parts[1];
        }

        $base64 = str_replace(' ', '+', $base64);
        $data = base64_decode($base64, true);

        if ($data === false) {
            return '';
        }

        $dir = public_path('data/ManualDeposit');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = 'manual_'.Str::lower(Str::random(16)).'.jpg';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, $data);

        return $filename;
    }

    private function mapLegacyPurchaseStatusLabel(int $status): string
    {
        if ($status === 1) {
            return 'success';
        }

        if ($status === 2) {
            return 'rejected';
        }

        return 'pending';
    }

    private function getBetzonoDepositUrl(): string
    {
        $candidates = [
            config('services.betzono.deposit_url'),
            env('BETZONO_DEPOSIT_URL'),
            getenv('BETZONO_DEPOSIT_URL') ?: null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate, " \t\n\r\0\x0B'\"");
            if ($value !== '') {
                return $value;
            }
        }

        $fallbackBase = trim((string) (config('services.betzono.callback_base_url')
            ?: env('BETZONO_CALLBACK_URL_BASE')
            ?: getenv('BETZONO_CALLBACK_URL_BASE')), " \t\n\r\0\x0B'\"");

        return $fallbackBase !== '' ? rtrim($fallbackBase, '/').'/api/deposit' : '';
    }

    private function getBetzonoMerchantId(): string
    {
        return $this->firstNonEmptyString([
            config('services.betzono.merchant_id'),
            env('BETZONO_MERCHANT_ID'),
            getenv('BETZONO_MERCHANT_ID') ?: null,
        ]);
    }

    private function getBetzonoProxyEmail(): string
    {
        return $this->firstNonEmptyString([
            config('services.betzono.proxy_email'),
            env('BETZONO_PROXY_EMAIL'),
            getenv('BETZONO_PROXY_EMAIL') ?: null,
        ]);
    }

    private function getBetzonoProxyPhone(): string
    {
        return $this->firstNonEmptyString([
            config('services.betzono.proxy_phone'),
            env('BETZONO_PROXY_PHONE'),
            getenv('BETZONO_PROXY_PHONE') ?: null,
        ]);
    }

    private function getBetzonoProxyUserId(): int
    {
        $value = $this->firstNonEmptyString([
            config('services.betzono.proxy_user_id'),
            env('BETZONO_PROXY_USER_ID'),
            getenv('BETZONO_PROXY_USER_ID') ?: null,
        ]);

        return $value !== '' ? (int) $value : 0;
    }

    private function getBetzonoDebugFallbackUrl(): string
    {
        return $this->firstNonEmptyString([
            config('services.betzono.debug_fallback_url'),
            env('BETZONO_DEBUG_FALLBACK_URL'),
            getenv('BETZONO_DEBUG_FALLBACK_URL') ?: null,
        ]);
    }

    private function buildGatewayWrapperUrl(string $trx): string
    {
        return URL::temporarySignedRoute(
            'payment.deposit.redirect',
            now()->addHours(4),
            ['trx' => $trx]
        );
    }

    private function upsertGatewayInitTransaction(
        string $trx,
        ?int $userId,
        ?int $legacyUserId,
        ?int $legacyOrderId,
        float $amount,
        string $currency,
        string $status,
        string $gatewayStatus,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $paymentUrl = null,
        ?string $gatewayTransactionId = null
    ): void {
        if (! Schema::hasTable('rox_gateway_transactions')) {
            return;
        }

        $existing = DB::table('rox_gateway_transactions')->where('trx', $trx)->first();

        $update = [
            'type' => 'deposit',
            'status' => $status,
            'gateway_status' => $gatewayStatus,
            'amount' => $amount,
            'currency' => $currency,
            'updated_at' => now(),
        ];

        if ($userId !== null) {
            $update['user_id'] = $userId;
        }
        if ($legacyUserId !== null && Schema::hasColumn('rox_gateway_transactions', 'legacy_user_id')) {
            $update['legacy_user_id'] = $legacyUserId;
        }
        if ($legacyOrderId !== null && Schema::hasColumn('rox_gateway_transactions', 'legacy_order_id')) {
            $update['legacy_order_id'] = $legacyOrderId;
        }
        if ($gatewayTransactionId !== null && Schema::hasColumn('rox_gateway_transactions', 'gateway_transaction_id')) {
            $update['gateway_transaction_id'] = $gatewayTransactionId;
        }
        if ($paymentUrl !== null && Schema::hasColumn('rox_gateway_transactions', 'payment_url')) {
            $update['payment_url'] = $paymentUrl;
        }
        if ($requestPayload !== null && Schema::hasColumn('rox_gateway_transactions', 'request_payload')) {
            $update['request_payload'] = json_encode($requestPayload);
        }
        if ($responsePayload !== null && Schema::hasColumn('rox_gateway_transactions', 'response_payload')) {
            $update['response_payload'] = json_encode($responsePayload);
        }

        if ($existing) {
            DB::table('rox_gateway_transactions')->where('id', $existing->id)->update($update);
            return;
        }

        $insert = array_merge($update, [
            'trx' => $trx,
            'created_at' => now(),
        ]);

        DB::table('rox_gateway_transactions')->insert($insert);
    }

    private function buildDebugGatewayFallbackResponse(
        string $orderId,
        float $amount,
        string $transactionId,
        string $fallbackUrl,
        string $message
    ): JsonResponse {
        if ($this->legacyTableExists('tbl_purchase')) {
            DB::table('tbl_purchase')
                ->where('id', $orderId)
                ->update([
                    'razor_payment_id' => $transactionId,
                    'transaction_id' => $transactionId,
                    'json_response' => json_encode([
                        'payment_url' => $fallbackUrl,
                        'wrapper_url' => $this->buildGatewayWrapperUrl($transactionId),
                    ]),
                    'updated_date' => Carbon::now(),
                ]);
        }

        $this->upsertGatewayInitTransaction(
            trx: $transactionId,
            userId: null,
            legacyUserId: null,
            legacyOrderId: (int) $orderId,
            amount: $amount,
            currency: 'INR',
            status: 'pending',
            gatewayStatus: 'debug_fallback',
            requestPayload: null,
            responsePayload: ['debug_fallback' => true],
            paymentUrl: $fallbackUrl,
            gatewayTransactionId: $transactionId
        );

        Log::info('rox_ludo.gateway_debug_fallback', [
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'fallback_url' => $fallbackUrl,
        ]);

        return response()->json([
            'order_id' => (int) $orderId,
            'Total_Amount' => (string) $amount,
            'intentData' => $this->buildGatewayWrapperUrl($transactionId),
            'transaction_id' => $transactionId,
            'gateway_transaction_id' => $transactionId,
            'message' => $message,
            'code' => 200,
        ]);
    }

    private function firstNonEmptyString(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate, " \t\n\r\0\x0B'\"");
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
