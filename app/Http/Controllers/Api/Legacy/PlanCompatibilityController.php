<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
            $betzonoUrl = (string) env(
                'BETZONO_DEPOSIT_URL',
                rtrim((string) env('BETZONO_CALLBACK_URL_BASE', ''), '/').'/api/deposit'
            );

            $merchantId = (string) env('BETZONO_MERCHANT_ID', '');
            $proxyUserId = (int) env('BETZONO_PROXY_USER_ID', 0);
            $proxyEmail = (string) env('BETZONO_PROXY_EMAIL', '');
            $proxyPhone = (string) env('BETZONO_PROXY_PHONE', '');

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
                ],
            ];

            $response = Http::timeout(15)->acceptJson()->post($betzonoUrl, $payload);
            if (! $response->successful()) {
                return response()->json([
                    'message' => 'Payment gateway error',
                    'code' => 500,
                ]);
            }

            $apiData = $response->json() ?? [];
            $intentData = data_get($apiData, 'data.payment_url')
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

            if ($this->legacyTableExists('tbl_purchase')) {
                DB::table('tbl_purchase')
                    ->where('id', $orderId)
                    ->update([
                        'razor_payment_id' => $gatewayTxn ?: $transactionId,
                        'transaction_id' => $transactionId,
                        'updated_date' => Carbon::now(),
                    ]);
            }

            return response()->json([
                'order_id' => (int) $orderId,
                'Total_Amount' => (string) $amount,
                'intentData' => $intentData,
                'message' => 'Success',
                'code' => 200,
            ]);
        } catch (\Throwable $exception) {
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
                'photo' => null,
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
}
