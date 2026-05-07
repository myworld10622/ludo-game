<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\LegacyOtp;
use App\Models\User;
use App\Models\UserRecoveryChannel;
use App\Models\UserSecurityReminder;
use App\Models\UserSocialAccount;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Models\WalletTransaction;
use App\Services\Auth\AuthService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class UserCompatibilityController extends Controller
{
    public function guestRegister(Request $request): JsonResponse
    {
        $uniqueToken = (string) $request->input('unique_token', Str::uuid()->toString());

        $user = DB::transaction(function () use ($uniqueToken) {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'username' => 'guest_'.Str::lower(Str::random(10)),
                'password' => Hash::make(Str::random(32)),
                'referral_code' => strtoupper(Str::random(8)),
                'is_active' => true,
                'is_banned' => false,
            ]);

            $user->profile()->create([
                'first_name' => 'Guest',
                'language' => 'en',
                'preferences' => [
                    'guest' => true,
                    'unique_token' => $uniqueToken,
                ],
            ]);

            $this->ensureWallets($user);

            return $user;
        });

        $token = $user->createToken('guest-mobile-app')->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'Guest login successful.',
            'user_id' => (string) $user->user_code,
            'token' => $token,
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $type = (string) $request->input('type', 'register');

        if ($mobile === '') {
            return response()->json([
                'message' => 'Mobile is required.',
                'otp_id' => 0,
                'code' => 400,
            ]);
        }

        $cooldownOtp = $this->findActiveOtpWithinCooldown($mobile, $type);
        if ($cooldownOtp) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
                'otp_id' => (string) $cooldownOtp->id,
                'code' => 429,
                'retry_after' => $this->otpRetryAfterSeconds($cooldownOtp),
                'otp' => $this->shouldExposeOtpForTesting() ? (string) $cooldownOtp->otp_code : '',
            ]);
        }

        $otpId = random_int(100000, 999999);
        $otpCode = (string) random_int(1000, 9999);

        LegacyOtp::query()->where('mobile', $mobile)->where('type', $type)->where('is_used', false)->delete();

        $otp = LegacyOtp::query()->create([
            'id' => $otpId,
            'mobile' => $mobile,
            'type' => $type,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp_id' => $otp->id,
            'code' => 200,
            'otp' => $otpCode,
            'retry_after' => $this->otpCooldownSeconds(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $password = (string) $request->input('password');
        $name = (string) $request->input('name', 'Player');
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');
        $skipOtp = (string) $request->input('skip_otp', '');
        $referralCode = (string) $request->input('referral_code', '');

        if ($mobile === '' || $password === '' || ($skipOtp !== '1' && ($otpId === '' || $otp === ''))) {
            return response()->json([
                'message' => 'Required signup fields are missing.',
                'user_id' => '',
                'token' => '',
                'code' => 400,
            ]);
        }

        if ($skipOtp !== '1' && ! $this->validateOtp($otpId, $mobile, $otp, ['register', 'signup'])) {
            return response()->json([
                'message' => 'Invalid OTP.',
                'user_id' => '',
                'token' => '',
                'code' => 404,
            ]);
        }

        $existing = User::query()->where('mobile', $mobile)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Mobile number already registered.',
                'user_id' => '',
                'token' => '',
                'code' => 404,
            ]);
        }

        $referrer = $referralCode !== ''
            ? $this->resolveReferrer($referralCode)
            : $this->resolveDefaultReferrer();

        $user = DB::transaction(function () use ($mobile, $password, $name, $referrer) {
            $normalizedFirstName = $this->normalizeProfileFirstName($name, $mobile);

            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'username' => $this->makeUsername($name, $mobile),
                'mobile' => $mobile,
                'password' => Hash::make($password),
                'referred_by_user_id' => $referrer?->id,
                'is_active' => true,
                'is_banned' => false,
                'mobile_verified_at' => now(),
            ]);

            $user->forceFill([
                'referral_code' => (string) $user->user_code,
            ])->save();

            $user->profile()->create([
                'first_name' => $normalizedFirstName,
                'gender' => 'male',
                'language' => 'en',
            ]);

            $this->ensureWallets($user);

            return $user;
        });

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registered Successfully',
            'user_id' => (string) $user->user_code,
            'username' => (string) $user->username,
            'login_id' => (string) $user->username,
            'token' => $token,
            'code' => 200,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $password = (string) $request->input('password');

        $user = User::query()
            ->where('mobile', $mobile)
            ->orWhere('email', $mobile)
            ->orWhere('username', $mobile)
            ->orWhere('user_code', $mobile)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials.',
                'user_data' => [],
                'user_kyc' => [],
                'user_bank_details' => [],
                'avatar' => [],
                'setting' => $this->settingsPayload(),
                'notification_image' => '',
                'app_banner' => [],
                'code' => 404,
            ]);
        }

        $this->ensureWallets($user);
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json($this->loginPayload($user, $token, 'Login successful.'));
    }

    public function socialLogin(Request $request, AuthService $authService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => ['required', 'string', 'in:google,facebook,instagram'],
            'provider_user_id' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'avatar_url' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first() ?: 'Invalid social login payload.',
                'user_data' => [],
                'user_kyc' => [],
                'user_bank_details' => [],
                'avatar' => [],
                'setting' => $this->settingsPayload(),
                'notification_image' => '',
                'app_banner' => [],
                'code' => 404,
            ]);
        }

        try {
            $result = $authService->socialLogin([
                'provider' => $request->input('provider'),
                'provider_user_id' => $request->input('provider_user_id'),
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'avatar_url' => $request->input('avatar_url'),
                'device_name' => 'mobile-social-login',
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'user_data' => [],
                'user_kyc' => [],
                'user_bank_details' => [],
                'avatar' => [],
                'setting' => $this->settingsPayload(),
                'notification_image' => '',
                'app_banner' => [],
                'code' => 404,
            ]);
        }

        return response()->json(
            $this->loginPayload($result['user'], $result['token'], 'Social login successful.')
        );
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Session expired.',
                'user_data' => [],
                'user_kyc' => [],
                'user_bank_details' => [],
                'avatar' => [],
                'setting' => $this->settingsPayload(),
                'notification_image' => '',
                'app_banner' => [],
                'code' => 411,
            ]);
        }

        $this->ensureWallets($user);

        return response()->json($this->loginPayload($user, (string) $request->input('token'), 'Profile fetched successfully.'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        $profile = $this->ensureProfile($user);
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => (string) ($validator->errors()->first() ?: 'Invalid profile details.'),
                'code' => 406,
            ]);
        }

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $gender = trim((string) $request->input('gender', ''));
        $dateOfBirth = trim((string) $request->input('date_of_birth', ''));
        $state = trim((string) $request->input('state', ''));
        $city = trim((string) $request->input('city', ''));
        $profilePic = (string) $request->input('profile_pic', '');

        if ($request->exists('name')) {
            $profile->first_name = $name !== '' ? $name : null;
        }

        if ($request->exists('email') && $email !== '') {
            $user->email = $email;
        }

        if ($request->exists('gender')) {
            $profile->gender = $gender !== '' ? $gender : null;
        }

        if ($request->exists('date_of_birth')) {
            $profile->date_of_birth = $dateOfBirth !== '' ? Carbon::parse($dateOfBirth)->toDateString() : null;
        }

        if ($request->exists('state')) {
            $profile->state = $state !== '' ? $state : null;
        }

        if ($request->exists('city')) {
            $profile->city = $city !== '' ? $city : null;
        }

        if ($profilePic !== '') {
            $filename = $this->storeBase64Image($profilePic, 'profile_');
            if ($filename !== '') {
                $profile->avatar_url = $filename;
            }
        }

        $user->save();
        $profile->save();

        return response()->json([
            'message' => 'Profile Updated Successfully',
            'code' => 200,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        $oldPassword = (string) $request->input('old_password', '');
        $newPassword = (string) $request->input('new_password', '');

        if ($oldPassword === '' || $newPassword === '') {
            return response()->json([
                'message' => 'Password fields are required.',
                'code' => 406,
            ]);
        }

        if (! Hash::check($oldPassword, $user->password)) {
            return response()->json([
                'message' => 'Old password does not match.',
                'code' => 406,
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully.',
            'code' => 200,
        ]);
    }

    public function updateBankDetails(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        $profile = $this->ensureProfile($user);
        $preferences = $profile->preferences ?? [];

        $bankDetails = $preferences['bank_details'] ?? [];
        $cryptoDetails = $preferences['crypto_details'] ?? [];

        $bankDetails['bank_name'] = (string) $request->input('bank_name', $bankDetails['bank_name'] ?? '');
        $bankDetails['acc_holder_name'] = (string) $request->input('acc_holder_name', $bankDetails['acc_holder_name'] ?? '');
        $bankDetails['acc_no'] = (string) $request->input('acc_no', $bankDetails['acc_no'] ?? '');
        $bankDetails['ifsc_code'] = (string) $request->input('ifsc_code', $bankDetails['ifsc_code'] ?? '');
        $bankDetails['upi_id'] = (string) $request->input('upi_id', $bankDetails['upi_id'] ?? '');

        $passbookImg = (string) $request->input('passbook_img', '');
        if ($passbookImg !== '') {
            $filename = $this->storeBase64Image($passbookImg, 'passbook_');
            if ($filename !== '') {
                $bankDetails['passbook_img'] = $filename;
            }
        }

        $cryptoDetails['crypto_address'] = (string) $request->input('crypto_address', $cryptoDetails['crypto_address'] ?? '');
        $cryptoDetails['crypto_wallet_type'] = (string) $request->input('crypto_wallet_type', $cryptoDetails['crypto_wallet_type'] ?? '');

        $cryptoQr = (string) $request->input('crypto_qr', '');
        if ($cryptoQr !== '') {
            $filename = $this->storeBase64Image($cryptoQr, 'crypto_');
            if ($filename !== '') {
                $cryptoDetails['crypto_qr'] = $filename;
            }
        }

        $preferences['bank_details'] = $bankDetails;
        $preferences['crypto_details'] = $cryptoDetails;
        $profile->preferences = $preferences;
        $profile->save();

        return response()->json([
            'message' => 'Bank details updated successfully.',
            'code' => 200,
        ]);
    }

    public function updateKyc(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        $profile = $this->ensureProfile($user);
        $preferences = $profile->preferences ?? [];
        $kyc = $preferences['kyc_details'] ?? [];

        $kyc['aadhar_no'] = (string) $request->input('aadhar_no', $kyc['aadhar_no'] ?? '');
        $kyc['pan_no'] = (string) $request->input('pan_no', $kyc['pan_no'] ?? '');

        $aadharImg = (string) $request->input('aadhar_img', '');
        if ($aadharImg !== '') {
            $filename = $this->storeBase64Image($aadharImg, 'aadhar_');
            if ($filename !== '') {
                $kyc['aadhar_img'] = $filename;
            }
        }

        $panImg = (string) $request->input('pan_img', '');
        if ($panImg !== '') {
            $filename = $this->storeBase64Image($panImg, 'pan_');
            if ($filename !== '') {
                $kyc['pan_img'] = $filename;
            }
        }

        $kyc['status'] = $kyc['status'] ?? 'pending';
        $preferences['kyc_details'] = $kyc;
        $profile->preferences = $preferences;
        $profile->save();

        return response()->json([
            'message' => 'KYC updated successfully.',
            'code' => 200,
        ]);
    }

    public function getStatement(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'statement' => [],
                'code' => 411,
            ]);
        }

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        $statement = $transactions->map(function (WalletTransaction $tx) use ($user) {
            $amount = (string) ($tx->direction === 'debit' ? -1 * (float) $tx->amount : (float) $tx->amount);
            $source = (string) ($tx->description ?: $tx->type);

            if ($tx->description === 'Withdrawal request' && $this->legacyTableExists('tbl_withdrawal_log')) {
                $withdrawalRow = DB::table('tbl_withdrawal_log')
                    ->select('status')
                    ->where('id', $tx->reference_id)
                    ->first();

                if ($withdrawalRow) {
                    $statusLabel = match ((int) $withdrawalRow->status) {
                        0 => 'Pending',
                        1 => 'Approved',
                        2 => 'Rejected',
                        default => 'Pending',
                    };

                    $source = 'Withdrawal request ('.$statusLabel.')';
                }
            }

            if ($tx->description === 'Withdrawal rejected refund') {
                $source = 'Withdrawal refund (Rejected)';
            }

            return [
                'id' => (string) $tx->id,
                'user_id' => (string) $user->user_code,
                'source' => $source,
                'source_id' => (string) ($tx->reference_id ?? $tx->transaction_uuid),
                'amount' => $amount,
                'admin_commission' => '0',
                'current_wallet' => (string) $tx->balance_after,
                'added_date' => optional($tx->processed_at ?? $tx->created_at)->format('Y-m-d H:i:s'),
                'isDeleted' => '0',
            ];
        })->values();

        return response()->json([
            'message' => 'Success',
            'statement' => $statement,
            'code' => 200,
        ]);
    }

    public function walletHistoryAll(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'GameLog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildGenericGameLogs($user);

        return response()->json([
            'message' => 'Success',
            'GameLog' => $logs,
            'MinRedeem' => $this->legacyMinRedeem(),
            'code' => 200,
        ]);
    }

    public function walletHistoryHeadTail(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Head & Tail');
    }

    public function walletHistoryRoulette(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Roulette');
    }

    public function walletHistoryColorPrediction(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Color Prediction');
    }

    public function walletHistoryColorPrediction1Min(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Color Prediction 1 Min');
    }

    public function walletHistoryColorPrediction3Min(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Color Prediction 3 Min');
    }

    public function walletHistoryColorPrediction5Min(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Color Prediction 5 Min');
    }

    public function walletHistoryAndarBahar(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Andar Bahar', 'AB');
    }

    public function walletHistoryAndarBaharPlus(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Andar Bahar Plus', 'AB');
    }

    public function walletHistoryDragonTiger(Request $request): JsonResponse
    {
        return $this->respondGameLogArray($request, 'Dragon Tiger', 'DNT');
    }

    public function walletHistorySevenUp(Request $request): JsonResponse
    {
        return $this->respondGameLogArray($request, 'Seven Up Down', 'SEVEN');
    }

    public function walletHistoryCarRoulette(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Car Roulette');
    }

    public function walletHistoryAnimalRoulette(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Animal Roulette');
    }

    public function walletHistoryBaccarat(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Baccarat');
    }

    public function walletHistoryJackpot(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Jackpot Teen Patti');
    }

    public function walletHistoryRedBlack(Request $request): JsonResponse
    {
        return $this->respondGameLogList($request, 'Red Black');
    }

    public function walletHistoryRummyPool(Request $request): JsonResponse
    {
        return $this->respondRummyLogList($request, 'Pool Rummy');
    }

    public function walletHistoryRummyDeal(Request $request): JsonResponse
    {
        return $this->respondRummyLogList($request, 'Deal Rummy');
    }

    public function walletHistoryRummyPoint(Request $request): JsonResponse
    {
        return $this->respondRummyPointLog($request);
    }

    public function walletHistoryPoker(Request $request): JsonResponse
    {
        return $this->respondPokerLog($request);
    }

    public function walletHistoryJhandiMunda(Request $request): JsonResponse
    {
        return $this->respondJhandiMundaLog($request);
    }

    public function purchaseHistory(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token') ?: $request->input('Token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'purchase_history' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        // Use same fallback ID logic as addCash so IDs always match
        $lookupId = $legacyUser?->id ?? (int) $user->id;

        if (! $this->legacyTableExists('tbl_purchase')) {
            return response()->json([
                'message' => 'Success',
                'purchase_history' => [],
                'code' => 200,
            ]);
        }

        // Cover both lookup strategies: PlanCompatibilityController uses tbl_users.id = laravel user id,
        // while this controller resolves via mobile/email. Include both to catch all deposits.
        $ids = array_unique(array_filter([$lookupId, (int) $user->id], fn ($v) => $v > 0));

        $history = DB::table('tbl_purchase')
            ->whereIn('user_id', $ids)
            ->orderByDesc('id')
            ->get();

        if ($history->isEmpty()) {
            return response()->json([
                'message' => 'No data',
                'purchase_history' => [],
                'code' => 404,
            ]);
        }

        return response()->json([
            'message' => 'Success',
            'purchase_history' => $history,
            'code' => 200,
        ]);
    }

    public function getDepositBonus(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'activation_list' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser || ! $this->legacyTableExists('tbl_purcharse_ref')) {
            return response()->json([
                'message' => 'Success',
                'activation_list' => [],
                'code' => 200,
            ]);
        }

        $type = (string) $request->input('type', '');
        $purchaseUserId = (string) $request->input('purchase_user_id', '');
        $date = (string) $request->input('date', '');

        $query = DB::table('tbl_purcharse_ref')
            ->where('user_id', $legacyUser->id);

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($purchaseUserId !== '') {
            $query->where('purchase_user_id', $purchaseUserId);
        }

        if ($date !== '') {
            try {
                $query->whereDate('added_date', Carbon::parse($date)->toDateString());
            } catch (\Throwable $exception) {
                // Ignore invalid dates.
            }
        }

        $activationList = $query->orderByDesc('id')->get();

        if ($activationList->isEmpty()) {
            return response()->json([
                'message' => 'No records found.',
                'activation_list' => [],
                'code' => 404,
            ]);
        }

        return response()->json([
            'message' => 'Success',
            'activation_list' => $activationList,
            'code' => 200,
        ]);
    }

    public function betCommissionLog(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'bet_commission_log' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser || ! $this->legacyTableExists('tbl_bet_income_log') || ! $this->legacyTableExists('tbl_users')) {
            return response()->json([
                'message' => 'Success',
                'bet_commission_log' => [],
                'code' => 200,
            ]);
        }

        $logs = DB::table('tbl_bet_income_log')
            ->select('tbl_bet_income_log.*', 'tbl_users.name')
            ->join('tbl_users', 'tbl_bet_income_log.bet_user_id', '=', 'tbl_users.id')
            ->where('tbl_bet_income_log.to_user_id', $legacyUser->id)
            ->orderByDesc('tbl_bet_income_log.id')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'message' => 'No Data',
                'bet_commission_log' => [],
                'code' => 406,
            ]);
        }

        return response()->json([
            'message' => 'Success',
            'bet_commission_log' => $logs,
            'code' => 200,
        ]);
    }

    public function rebateHistory(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'data' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser || ! $this->legacyTableExists('tbl_rebate_income')) {
            return response()->json([
                'message' => 'Success',
                'data' => [],
                'code' => 200,
            ]);
        }

        $history = DB::table('tbl_rebate_income')
            ->where('user_id', $legacyUser->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        if ($history->isEmpty()) {
            return response()->json([
                'message' => 'No logs',
                'data' => [],
                'code' => 406,
            ]);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $history,
            'code' => 200,
        ]);
    }

    public function welcomeBonus(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'welcome_bonus' => [],
                'today_collected' => '0',
                'collected_days' => 0,
                'code' => 411,
            ]);
        }

        if (! $this->legacyTableExists('tbl_welcome_reward')) {
            $welcome = [
                ['id' => '1', 'coin' => '10', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '2', 'coin' => '20', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '3', 'coin' => '30', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '4', 'coin' => '40', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '5', 'coin' => '50', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '6', 'coin' => '60', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
                ['id' => '7', 'coin' => '70', 'game_played' => '0', 'added_date' => '', 'updated_date' => ''],
            ];

            return response()->json([
                'message' => 'Success',
                'welcome_bonus' => $welcome,
                'today_collected' => '0',
                'collected_days' => 0,
                'code' => 200,
            ]);
        }

        $welcome = DB::table('tbl_welcome_reward')->orderBy('id')->get();
        if ($welcome->isEmpty()) {
            return response()->json([
                'message' => 'Invalid Bonus',
                'welcome_bonus' => [],
                'today_collected' => '0',
                'collected_days' => 0,
                'code' => 406,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        $collectedDays = 0;
        $todayCollected = '0';

        if ($legacyUser && $this->legacyTableExists('tbl_welcome_log')) {
            $logs = DB::table('tbl_welcome_log')
                ->where('user_id', $legacyUser->id)
                ->orderByDesc('id')
                ->get();
            $collectedDays = $logs->count();
            if ($collectedDays > 0) {
                $lastDate = Carbon::parse($logs->first()->added_date)->toDateString();
                $todayCollected = $lastDate === now()->toDateString() ? '1' : '0';
            }
        }

        return response()->json([
            'message' => 'Success',
            'welcome_bonus' => $welcome,
            'today_collected' => $todayCollected,
            'collected_days' => $collectedDays,
            'code' => 200,
        ]);
    }

    public function collectWelcomeBonus(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        if (! $this->legacyTableExists('tbl_welcome_reward') || ! $this->legacyTableExists('tbl_welcome_log')) {
            return response()->json([
                'message' => 'Invalid Bonus',
                'code' => 406,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser) {
            return response()->json([
                'message' => 'Invalid User',
                'code' => 411,
            ]);
        }

        $welcome = DB::table('tbl_welcome_reward')->orderBy('id')->get();
        if ($welcome->isEmpty()) {
            return response()->json([
                'message' => 'Invalid Bonus',
                'code' => 406,
            ]);
        }

        $bonusLogs = DB::table('tbl_welcome_log')
            ->where('user_id', $legacyUser->id)
            ->orderByDesc('id')
            ->get();

        $lastDate = $bonusLogs->isEmpty()
            ? null
            : Carbon::parse($bonusLogs->first()->added_date)->toDateString();

        if ($lastDate === now()->toDateString()) {
            return response()->json([
                'message' => "Today's Bonus Already Collected",
                'code' => 406,
            ]);
        }

        $collectedDays = $bonusLogs->count();
        if ($collectedDays >= $welcome->count()) {
            return response()->json([
                'message' => 'All Bonus Already Collected',
                'code' => 406,
            ]);
        }

        $reward = $welcome[$collectedDays];
        $gamePlayed = (int) ($legacyUser->game_played ?? 0);
        $required = (int) ($reward->game_played ?? 0);

        if ($required > $gamePlayed) {
            return response()->json([
                'message' => 'You Have To Play '.($required - $gamePlayed).' More Games to Collect Bonus',
                'code' => 406,
            ]);
        }

        $amount = (float) $reward->coin;
        try {
            app(WalletService::class)->credit(
                user: $user,
                amount: $amount,
                referenceType: WalletTransaction::class,
                referenceId: $reward->id,
                description: 'Welcome bonus',
                currency: 'INR',
                meta: [
                    'legacy' => true,
                    'legacy_table' => 'tbl_welcome_reward',
                ],
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Unable to apply bonus',
                'code' => 500,
            ]);
        }

        DB::table('tbl_welcome_log')->insert([
            'user_id' => $legacyUser->id,
            'coin' => $amount,
            'added_date' => now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('tbl_users')
            ->where('id', $legacyUser->id)
            ->update([
                'wallet' => DB::raw('wallet + '.$amount),
                'bonus_wallet' => DB::raw('bonus_wallet + '.$amount),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        $this->applyWelcomeReferralBonus($legacyUser, $user, (int) $reward->id, $amount);

        return response()->json([
            'message' => 'Success',
            'coin' => (string) $amount,
            'code' => 200,
        ]);
    }

    public function refferLevel(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'refferearnlog' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser || ! $this->legacyTableExists('tbl_welcome_ref')) {
            return response()->json([
                'message' => 'Success',
                'refferearnlog' => [],
                'code' => 200,
            ]);
        }

        $requestedLevel = (int) $request->input('level', 0);
        $lookupIds = array_unique(array_filter(
            [$legacyUser?->id, (int) $user->id],
            fn ($value) => $value !== null && $value > 0
        ));

        $query = DB::table('tbl_welcome_ref')
            ->select(
                'tbl_welcome_ref.id',
                'tbl_welcome_ref.user_id',
                'tbl_welcome_ref.bonus_user_id',
                'tbl_welcome_ref.coin',
                'tbl_welcome_ref.added_date',
                'tbl_welcome_ref.level',
                DB::raw("COALESCE(tbl_users.name, '') as name"),
                DB::raw("COALESCE(tbl_users.mobile, '') as referred_mobile")
            )
            ->leftJoin('tbl_users', 'tbl_users.id', '=', 'tbl_welcome_ref.bonus_user_id')
            ->whereIn('tbl_welcome_ref.user_id', $lookupIds);

        if ($requestedLevel > 0) {
            $query->where('tbl_welcome_ref.level', $requestedLevel);
        }

        $logs = $query
            ->orderByDesc('tbl_welcome_ref.id')
            ->get()
            ->map(function ($row) {
                $displayId = trim((string) ($row->bonus_user_id ?? ''));
                if ($displayId === '') {
                    $displayId = trim((string) ($row->referred_mobile ?? ''));
                }

                return [
                    'id' => (string) ($row->id ?? ''),
                    'user_id' => (string) ($row->user_id ?? ''),
                    'referred_user_id' => $displayId,
                    'coin' => (string) ($row->coin ?? '0'),
                    'added_date' => (string) ($row->added_date ?? ''),
                    'name' => (string) ($row->name ?? ''),
                    'refer_count' => (string) ($row->level ?? '0'),
                    'level' => (string) ($row->level ?? '0'),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Success',
            'refferearnlog' => $logs,
            'code' => 200,
        ]);
    }

    public function withdrawalLog(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'data' => [],
                'code' => 411,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);

        // If legacy tables missing OR no legacy user, fall back to wallet_transactions withdrawal entries
        if (! $legacyUser || ! $this->legacyTableExists('tbl_withdrawal_log')) {
            $walletRows = \App\Models\WalletTransaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->whereIn('reference_type', ['withdrawal', 'withdraw', 'redeem'])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn ($tx) => (object) [
                    'id'           => (string) $tx->id,
                    'user_id'      => (string) $tx->user_id,
                    'redeem_id'    => (string) ($tx->reference_id ?? ''),
                    'coin'         => (string) $tx->amount,
                    'mobile'       => '',
                    'status'       => '1',
                    'created_date' => (string) $tx->created_at,
                    'updated_date' => (string) $tx->updated_at,
                    'isDeleted'    => '0',
                    'user_name'    => $user->username ?? '',
                    'user_mobile'  => $user->mobile ?? '',
                    'bank_detail'  => '',
                    'adhar_card'   => '',
                    'upi'          => '',
                ]);

            return response()->json(['message' => 'Success', 'data' => $walletRows, 'code' => 200]);
        }

        // Try both legacy ID and Laravel user ID to cover all save strategies
        $lookupIds = array_unique(array_filter(
            [$legacyUser?->id, (int) $user->id],
            fn ($v) => $v !== null && $v > 0
        ));

        $logs = DB::table('tbl_withdrawal_log')
            ->select(
                'tbl_withdrawal_log.*',
                DB::raw("COALESCE(tbl_users.name, '') as user_name"),
                DB::raw("COALESCE(tbl_users.mobile, '') as user_mobile"),
                DB::raw("COALESCE(tbl_users.bank_detail, '') as bank_detail"),
                DB::raw("COALESCE(tbl_users.adhar_card, '') as adhar_card"),
                DB::raw("COALESCE(tbl_users.upi, '') as upi"),
            )
            ->leftJoin('tbl_users', 'tbl_users.id', '=', 'tbl_withdrawal_log.user_id')
            ->where('tbl_withdrawal_log.isDeleted', 0)
            ->whereIn('tbl_withdrawal_log.user_id', $lookupIds)
            ->orderByDesc('tbl_withdrawal_log.id')
            ->get();

        // Also include wallet_transactions-based withdrawal entries not in tbl_withdrawal_log
        $walletWithdrawals = \App\Models\WalletTransaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereIn('reference_type', ['withdrawal', 'withdraw', 'redeem'])
            ->whereNotIn('reference_id', $logs->pluck('id')->filter()->map(fn ($id) => (string) $id)->toArray())
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($tx) => (object) [
                'id'           => 'wt-'.$tx->id,
                'user_id'      => (string) $tx->user_id,
                'redeem_id'    => (string) ($tx->reference_id ?? ''),
                'coin'         => (string) $tx->amount,
                'mobile'       => '',
                'status'       => '0',
                'created_date' => (string) ($tx->created_at ?? ''),
                'updated_date' => (string) ($tx->updated_at ?? ''),
                'isDeleted'    => '0',
                'user_name'    => $user->username ?? '',
                'user_mobile'  => $user->mobile ?? '',
                'bank_detail'  => '',
                'adhar_card'   => '',
                'upi'          => '',
            ]);

        $transferRows = collect($this->buildTransferHistoryForViewer($user))
            ->map(function (array $item) use ($legacyUser) {
                return (object) [
                    'id' => 'transfer-'.$item['id'],
                    'user_id' => (string) $legacyUser->id,
                    'redeem_id' => 'wallet_transfer',
                    'coin' => (string) ($item['amount'] ?? '0'),
                    'mobile' => '',
                    'status' => (string) ($item['direction'] ?? 'sent'),
                    'created_date' => (string) ($item['added_date'] ?? ''),
                    'updated_date' => (string) ($item['added_date'] ?? ''),
                    'isDeleted' => '0',
                    'user_name' => (string) ($item['username'] ?? ''),
                    'user_mobile' => (string) ($item['mobile'] ?? ''),
                    'bank_detail' => '',
                    'adhar_card' => '',
                    'upi' => (string) ($item['user_id'] ?? ''),
                ];
            });

        $merged = collect($logs)
            ->concat($walletWithdrawals)
            ->concat($transferRows)
            ->sortByDesc(function ($row) {
                try {
                    return Carbon::parse((string) ($row->created_date ?? ''))->timestamp;
                } catch (\Throwable $exception) {
                    return 0;
                }
            })
            ->values();

        return response()->json([
            'message' => 'Success',
            'data' => $merged,
            'code' => 200,
        ]);
    }

    public function redeemList(Request $request): JsonResponse
    {
        try {
            $this->setLegacySessionLockTimeouts();
            $list = DB::table('tbl_redeem')
                ->where('isDeleted', 0)
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No Redeem Available',
                'code' => 404,
            ]);
        }

        if ($list->isEmpty()) {
            return response()->json([
                'message' => 'No Redeem Available',
                'code' => 404,
            ]);
        }

        return response()->json([
            'List' => $list,
            'message' => 'Success',
            'code' => 200,
        ]);
    }

    public function redeemWithdraw(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        $redeemId = (string) $request->input('redeem_id', '');
        $type = (string) $request->input('type', '0');

        if (! $user || $redeemId === '') {
            return response()->json([
                'message' => 'Invalid Param',
                'code' => 404,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser) {
            return response()->json([
                'message' => 'Invalid User ID',
                'code' => 404,
            ]);
        }

        try {
            $this->setLegacySessionLockTimeouts();
            $redeem = DB::table('tbl_redeem')->where('id', $redeemId)->first();
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Invalid Redeem ID',
                'code' => 404,
            ]);
        }
        if (! $redeem) {
            return response()->json([
                'message' => 'Invalid Redeem ID',
                'code' => 404,
            ]);
        }

        $minWithdraw = $this->legacyMinWithdrawal();
        if ((float) $redeem->coin < (float) $minWithdraw) {
            return response()->json([
                'message' => 'You can not withdraw less then '.$minWithdraw.'$',
                'code' => 404,
            ]);
        }

        $wallet = $this->ensureWallets($user);
        if ((float) $wallet->balance < (float) $redeem->coin) {
            return response()->json([
                'message' => 'Insufficient Coins',
                'code' => 404,
            ]);
        }

        $bankInfo = $this->legacyBankInfo($user);
        if ($bankInfo === null) {
            return response()->json([
                'message' => 'Please Fill Account Details First From Profile',
                'code' => 404,
            ]);
        }

        $withdrawalId = $this->createWithdrawalLog(
            legacyUserId: (int) $legacyUser->id,
            redeemId: (int) $redeem->id,
            amount: (float) $redeem->coin,
            bankInfo: $bankInfo,
            type: (int) $type,
            agentId: 0,
            price: 0.0,
            laravelUserId: $user->id,
        );

        if (! $withdrawalId) {
            return response()->json([
                'message' => 'Withdrawal request could not be created right now. Please try again.',
                'code' => 500,
            ]);
        }

        $this->applyWithdrawalDebit($user, $legacyUser, (float) $redeem->coin, $withdrawalId);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully. It is pending admin approval.',
            'code' => 200,
        ]);
    }

    public function redeemWithdrawCustom(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        $amount = (float) $request->input('amount', 0);
        $type = (string) $request->input('type', '0');

        if (! $user || $amount <= 0) {
            return response()->json([
                'message' => 'Invalid Param',
                'code' => 404,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser) {
            return response()->json([
                'message' => 'Invalid User ID',
                'code' => 404,
            ]);
        }

        $minWithdraw = $this->legacyMinWithdrawal();
        if ($amount < (float) $minWithdraw) {
            return response()->json([
                'message' => 'You can not withdraw less then '.$minWithdraw,
                'code' => 404,
            ]);
        }

        $wallet = $this->ensureWallets($user);
        if ((float) $wallet->balance < $amount) {
            return response()->json([
                'message' => 'Insufficient Coins',
                'code' => 404,
            ]);
        }

        $bankInfo = $this->legacyBankInfo($user);
        if ($bankInfo === null) {
            return response()->json([
                'message' => 'Please Fill Account Details First From Profile',
                'code' => 404,
            ]);
        }

        $withdrawalId = $this->createWithdrawalLog(
            legacyUserId: (int) $legacyUser->id,
            redeemId: 0,
            amount: $amount,
            bankInfo: $bankInfo,
            type: (int) $type,
            agentId: 0,
            price: 0.0,
            laravelUserId: $user->id,
        );

        if (! $withdrawalId) {
            return response()->json([
                'message' => 'Withdrawal request could not be created right now. Please try again.',
                'code' => 500,
            ]);
        }

        $this->applyWithdrawalDebit($user, $legacyUser, $amount, $withdrawalId);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully. It is pending admin approval.',
            'code' => 200,
        ]);
    }

    public function redeemWithdrawCustomCrypto(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        $amount = (float) $request->input('amount', 0);
        $cryptoAddress = (string) $request->input('crypto_address', '');
        $mobile = (string) $request->input('mobile', '');

        if (! $user || $amount <= 0) {
            return response()->json([
                'message' => 'Invalid Param',
                'code' => 404,
            ]);
        }

        if ($amount > 100000) {
            return response()->json([
                'message' => 'Maximum limit 100000',
                'code' => 404,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser) {
            return response()->json([
                'message' => 'Invalid User ID',
                'code' => 404,
            ]);
        }

        $wallet = $this->ensureWallets($user);
        if ((float) $wallet->balance < $amount) {
            return response()->json([
                'message' => 'Insufficient Coins',
                'code' => 404,
            ]);
        }

        $bankInfo = $this->legacyBankInfo($user);
        if ($bankInfo === null) {
            return response()->json([
                'message' => 'Please update your bank details Or crypto details.',
                'code' => 404,
            ]);
        }

        if ($cryptoAddress !== '') {
            $bankInfo['crypto_address'] = $cryptoAddress;
        }

        if ($mobile !== '') {
            $bankInfo['mobile'] = $mobile;
        }

        $withdrawalId = $this->createWithdrawalLog(
            legacyUserId: (int) $legacyUser->id,
            redeemId: 0,
            amount: $amount,
            bankInfo: $bankInfo,
            type: 1,
            agentId: 0,
            price: 0.0,
            laravelUserId: $user->id,
        );

        if (! $withdrawalId) {
            return response()->json([
                'message' => 'Something Went Wrong',
                'code' => 404,
            ]);
        }

        $this->applyWithdrawalDebit($user, $legacyUser, $amount, $withdrawalId);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully. It is pending admin approval.',
            'code' => 200,
        ]);
    }

    public function withdrawRequestForAgent(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        $coins = (float) $request->input('coins', 0);
        $agentId = (string) $request->input('agent_id', '');
        $type = (string) $request->input('type', '0');

        if (! $user || $coins <= 0 || $agentId === '') {
            return response()->json([
                'message' => 'Invalid Param',
                'code' => 404,
            ]);
        }

        $legacyUser = $this->resolveLegacyDbUser($user);
        if (! $legacyUser) {
            return response()->json([
                'message' => 'Invalid User ID',
                'code' => 404,
            ]);
        }

        if (! $this->legacyTableExists('tbl_admin')) {
            return response()->json([
                'message' => 'Agent Not Found.',
                'code' => 406,
            ]);
        }

        $agent = DB::table('tbl_admin')->where('id', $agentId)->first();
        if (! $agent) {
            return response()->json([
                'message' => 'Agent Not Found.',
                'code' => 406,
            ]);
        }

        $minWithdraw = $this->legacyMinWithdrawal();
        if ($coins < (float) $minWithdraw) {
            return response()->json([
                'message' => 'You can not withdraw less then '.$minWithdraw.'$',
                'code' => 404,
            ]);
        }

        $wallet = $this->ensureWallets($user);
        if ((float) $wallet->balance < $coins) {
            return response()->json([
                'message' => 'Insufficient Coins',
                'code' => 404,
            ]);
        }

        $bankInfo = $this->legacyBankInfo($user);
        if ($bankInfo === null) {
            return response()->json([
                'message' => 'Please Fill Account Details First From Profile',
                'code' => 404,
            ]);
        }

        $price = round(($coins / 100) * (float) ($agent->agent_withdraw_rate ?? 0));

        $withdrawalId = $this->createWithdrawalLog(
            legacyUserId: (int) $legacyUser->id,
            redeemId: 0,
            amount: $coins,
            bankInfo: $bankInfo,
            type: (int) $type,
            agentId: (int) $agentId,
            price: $price,
            laravelUserId: $user->id,
        );

        if (! $withdrawalId) {
            return response()->json([
                'message' => 'Something Went Wrong',
                'code' => 404,
            ]);
        }

        $this->applyWithdrawalDebit($user, $legacyUser, $coins, $withdrawalId);

        return response()->json([
            'message' => 'Withdrawal request submitted successfully. It is pending admin approval.',
            'code' => 200,
        ]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('id') ?: $request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Session expired.',
                'wallet' => '0',
                'winning_wallet' => '0',
                'unutilized_wallet' => '0',
                'bonus_wallet' => '0',
                'code' => 411,
            ]);
        }

        $cashWallet = $this->ensureWallets($user);

        return response()->json([
            'message' => 'Wallet fetched successfully.',
            'wallet' => (string) $cashWallet->balance,
            'winning_wallet' => '0',
            'unutilized_wallet' => (string) $cashWallet->balance,
            'bonus_wallet' => '0',
            'code' => 200,
        ]);
    }

    public function transferLookup(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('id') ?: $request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Session expired.',
                'player' => null,
                'code' => 411,
            ]);
        }

        $query = trim((string) ($request->input('query')
            ?: $request->input('player_id')
            ?: $request->input('mobile')
            ?: $request->input('email')
            ?: $request->input('username')));

        if ($query === '') {
            return response()->json([
                'message' => 'Please enter player ID, mobile, email or username.',
                'player' => null,
                'code' => 400,
            ]);
        }

        $target = $this->resolveTransferTargetUser($query);

        if (! $target) {
            return response()->json([
                'message' => 'Player not found.',
                'player' => null,
                'code' => 404,
            ]);
        }

        if ($target->id === $user->id) {
            return response()->json([
                'message' => 'You cannot transfer to your own account.',
                'player' => null,
                'code' => 422,
            ]);
        }

        $target->loadMissing('profile');

        return response()->json([
            'message' => 'Player found.',
            'player' => [
                'user_id' => (string) $target->user_code,
                'username' => (string) $target->username,
                'name' => (string) ($target->profile?->first_name ?: $target->username),
                'mobile' => $this->maskTransferMobile($target->mobile),
                'email' => $this->maskTransferEmail($target->email),
            ],
            'code' => 200,
        ]);
    }

    public function transferWallet(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('id') ?: $request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Session expired.',
                'transfer' => null,
                'code' => 411,
            ]);
        }

        $amount = (float) $request->input('amount', 0);
        $receiverInput = trim((string) ($request->input('receiver_user_id')
            ?: $request->input('receiver_id')
            ?: $request->input('query')
            ?: $request->input('player_id')
            ?: $request->input('mobile')
            ?: $request->input('email')
            ?: $request->input('username')));

        if ($receiverInput === '' || $amount <= 0) {
            return response()->json([
                'message' => 'Receiver and valid amount are required.',
                'transfer' => null,
                'code' => 400,
            ]);
        }

        $receiver = $this->resolveTransferTargetUser($receiverInput);
        if (! $receiver) {
            return response()->json([
                'message' => 'Player not found.',
                'transfer' => null,
                'code' => 404,
            ]);
        }

        if ($receiver->id === $user->id) {
            return response()->json([
                'message' => 'You cannot transfer to your own account.',
                'transfer' => null,
                'code' => 422,
            ]);
        }

        try {
            $transfer = app(WalletService::class)->transfer(
                sender: $user,
                receiver: $receiver,
                amount: $amount,
                currency: 'INR',
                note: trim((string) $request->input('note', '')),
                meta: [
                    'source' => 'rox_ludo_legacy_mobile',
                    'sender_user_code' => (string) $user->user_code,
                    'receiver_user_code' => (string) $receiver->user_code,
                ],
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'transfer' => null,
                'code' => $exception->getStatusCode(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Transfer failed. Please try again.',
                'transfer' => null,
                'code' => 500,
            ]);
        }

        $updatedWallet = $this->ensureWallets($user);

        return response()->json([
            'message' => 'Transfer completed successfully.',
            'transfer' => $this->buildTransferHistoryItem($transfer, $user),
            'wallet' => (string) $updatedWallet->balance,
            'code' => 200,
        ]);
    }

    public function transferHistory(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('id') ?: $request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Session expired.',
                'transfer_history' => [],
                'code' => 411,
            ]);
        }

        $history = $this->buildTransferHistoryForViewer($user);

        return response()->json([
            'message' => 'Transfer history fetched successfully.',
            'transfer_history' => $history,
            'code' => 200,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = (string) $request->input('channel_value');
        $mobile = (string) $request->input('mobile');

        if ($channelType === '' && $mobile !== '') {
            $channelType = 'mobile';
            $channelValue = $mobile;
        }

        $channelValue = $this->normalizeRecoveryChannelValue($channelType, $channelValue);
        $user = $this->findUserByRecoveryChannel($channelType, $channelValue);

        if (! $user) {
            return response()->json([
                'message' => $channelType === 'email'
                    ? 'Email address not found or not verified.'
                    : ($channelType === 'whatsapp'
                        ? 'WhatsApp number not found or not verified.'
                        : 'Mobile number not found.'),
                'otp_id' => '',
                'code' => 404,
            ]);
        }

        return $this->issueOtpResponse($channelType, $channelValue, 'forgot_password');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = (string) $request->input('channel_value');
        $mobile = (string) $request->input('mobile');
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');
        $newPassword = (string) $request->input('new_password');

        if ($channelType === '' && $mobile !== '') {
            $channelType = 'mobile';
            $channelValue = $mobile;
        }

        $channelValue = $this->normalizeRecoveryChannelValue($channelType, $channelValue);

        if (! $this->validateOtp($otpId, $channelValue, $otp, ['forgot', 'forgot_password_'.$channelType], $channelType)) {
            return response()->json([
                'message' => 'Invalid OTP.',
                'code' => 404,
            ]);
        }

        if (mb_strlen($newPassword) < 6) {
            return response()->json([
                'message' => 'Password must be at least 6 characters.',
                'code' => 422,
            ]);
        }

        $user = $this->findUserByRecoveryChannel($channelType, $channelValue);
        if (! $user) {
            return response()->json([
                'message' => 'Account not found for this recovery channel.',
                'code' => 404,
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully.',
            'code' => 200,
        ]);
    }

    public function forgotUsername(Request $request): JsonResponse
    {
        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = $this->normalizeRecoveryChannelValue($channelType, (string) $request->input('channel_value'));

        $user = $this->findUserByRecoveryChannel($channelType, $channelValue);
        if (! $user) {
            return response()->json([
                'message' => 'Account not found for this recovery channel.',
                'otp_id' => '',
                'code' => 404,
            ]);
        }

        return $this->issueOtpResponse($channelType, $channelValue, 'forgot_username');
    }

    public function recoverUsername(Request $request): JsonResponse
    {
        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = $this->normalizeRecoveryChannelValue($channelType, (string) $request->input('channel_value'));
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');

        if (! $this->validateOtp($otpId, $channelValue, $otp, ['forgot_username_'.$channelType], $channelType)) {
            return response()->json([
                'message' => 'Invalid OTP.',
                'code' => 404,
            ]);
        }

        $user = $this->findUserByRecoveryChannel($channelType, $channelValue);
        if (! $user) {
            return response()->json([
                'message' => 'Account not found for this recovery channel.',
                'code' => 404,
            ]);
        }

        return response()->json([
            'message' => 'Username recovered successfully.',
            'username' => (string) $user->username,
            'user_code' => (string) $user->user_code,
            'code' => 200,
        ]);
    }

    public function randomBoatUsers(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Notifications fetched successfully.',
            'data' => [],
            'users' => [],
            'code' => 200,
        ]);
    }

    public function gameOnOff(Request $request): JsonResponse
    {
        $games = Game::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payload = [];

        foreach ($games as $game) {
            $slug = (string) $game->slug;
            $payload[$slug] = [
                'game' => $slug,
                'name' => (string) $game->name,
                'status' => $game->is_active ? '1' : '0',
                'visibility' => $game->is_visible ? '1' : '0',
                'tournament_status' => $game->tournaments_enabled ? '1' : '0',
                'client_route' => (string) ($game->client_route ?? ''),
                'socket_namespace' => (string) ($game->socket_namespace ?? ''),
                'sort_order' => (string) $game->sort_order,
            ];
        }

        return response()->json([
            'message' => 'Game settings fetched successfully.',
            'data' => $payload,
            'games' => array_values($payload),
            'code' => 200,
        ]);
    }

    public function setting(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        return response()->json([
            'message' => 'Settings fetched successfully.',
            'setting' => $this->settingsPayload($user),
            'app_banner' => [],
            'notification_image' => '',
            'social_link' => [
                'telegram' => '',
                'instagram' => '',
                'youtube' => '',
                'facebook' => '',
            ],
            'code' => 200,
        ]);
    }

    public function recoveryStatus(Request $request): JsonResponse
    {
        if (! $this->hasRecoveryTables()) {
            return response()->json([
                'message' => 'Recovery channels are not configured yet.',
                'code' => 503,
            ]);
        }

        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'User session is invalid.',
                'code' => 401,
            ]);
        }

        return response()->json([
            'message' => 'Recovery status fetched successfully.',
            'setting' => $this->settingsPayload($user),
            'code' => 200,
        ]);
    }

    public function recoverySendOtp(Request $request): JsonResponse
    {
        if (! $this->hasRecoveryTables()) {
            return response()->json([
                'message' => 'Recovery channels are not configured yet.',
                'otp_id' => '',
                'code' => 503,
            ]);
        }

        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        if (! $user) {
            return response()->json([
                'message' => 'User session is invalid.',
                'code' => 401,
            ]);
        }

        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = $this->normalizeRecoveryChannelValue($channelType, (string) $request->input('channel_value'));
        $validationError = $this->validateRecoveryChannel($user, $channelType, $channelValue);

        if ($validationError !== null) {
            return response()->json([
                'message' => $validationError,
                'otp_id' => '',
                'code' => 422,
            ]);
        }

        return $this->issueOtpResponse($channelType, $channelValue, 'recovery');
    }

    public function recoveryVerifyOtp(Request $request): JsonResponse
    {
        if (! $this->hasRecoveryTables()) {
            return response()->json([
                'message' => 'Recovery channels are not configured yet.',
                'code' => 503,
            ]);
        }

        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        if (! $user) {
            return response()->json([
                'message' => 'User session is invalid.',
                'code' => 401,
            ]);
        }

        $channelType = strtolower(trim((string) $request->input('channel_type')));
        $channelValue = $this->normalizeRecoveryChannelValue($channelType, (string) $request->input('channel_value'));
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');

        $validationError = $this->validateRecoveryChannel($user, $channelType, $channelValue);
        if ($validationError !== null) {
            return response()->json([
                'message' => $validationError,
                'code' => 422,
            ]);
        }

        if (! $this->validateOtp($otpId, $channelValue, $otp, ['recovery_'.$channelType], $channelType)) {
            return response()->json([
                'message' => 'Invalid OTP.',
                'code' => 404,
            ]);
        }

        if ($channelType === 'email') {
            if (! empty($user->email) && strcasecmp((string) $user->email, $channelValue) !== 0) {
                return response()->json([
                    'message' => 'Another email is already linked to this account.',
                    'code' => 422,
                ]);
            }

            $user->forceFill([
                'email' => $channelValue,
                'email_verified_at' => now(),
            ])->save();
        }

        $this->upsertRecoveryChannel($user, $channelType, $channelValue, true);
        $this->markRecoveryReminderCompletedIfReady($user->fresh());

        return response()->json([
            'message' => ucfirst($channelType).' verified successfully.',
            'setting' => $this->settingsPayload($user->fresh()),
            'code' => 200,
        ]);
    }

    public function recoveryReminderDismiss(Request $request): JsonResponse
    {
        if (! $this->hasRecoveryTables()) {
            return response()->json([
                'message' => 'Recovery channels are not configured yet.',
                'code' => 503,
            ]);
        }

        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));
        if (! $user) {
            return response()->json([
                'message' => 'User session is invalid.',
                'code' => 401,
            ]);
        }

        $reminder = $this->securityReminderRecord($user);
        $reminder->forceFill([
            'last_shown_at' => now(),
            'dismissed_until' => now()->addDay(),
        ])->save();

        return response()->json([
            'message' => 'Recovery reminder dismissed for now.',
            'setting' => $this->settingsPayload($user),
            'code' => 200,
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

        $publicId = (string) $id;
        if ($publicId !== '' && $publicId !== (string) $user->id && $publicId !== (string) $user->user_code) {
            return null;
        }

        return $user;
    }

    protected function ensureWallets(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'wallet_type' => 'cash',
                'currency' => 'INR',
            ],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    protected function loginPayload(User $user, string $token, string $message): array
    {
        $user->load('profile');
        $wallet = $this->ensureWallets($user);
        $bankDetails = $this->legacyBankDetails($user);
        $kycDetails = $this->legacyKycDetails($user);

        return [
            'message' => $message,
            'user_data' => [[
                'id' => (string) $user->user_code,
                'user_id' => (string) $user->user_code,
                'name' => (string) ($user->profile?->first_name ?: $user->username),
                'user_type' => 'user',
                'bank_detail' => '',
                'adhar_card' => '',
                'upi' => '',
                'password' => '',
                'mobile' => (string) ($user->mobile ?? ''),
                'email' => (string) ($user->email ?? ''),
                'source' => 'laravel',
                'gender' => (string) ($user->profile?->gender ?? ''),
                'date_of_birth' => (string) optional($user->profile?->date_of_birth)->format('Y-m-d'),
                'state' => (string) ($user->profile?->state ?? ''),
                'city' => (string) ($user->profile?->city ?? ''),
                'country_code' => (string) ($user->profile?->country_code ?? ''),
                'profile_pic' => (string) ($user->profile?->avatar_url ?? ''),
                'referral_code' => (string) ($user->user_code ?? ''),
                'referred_by' => (string) ($user->referrer?->user_code ?? ''),
                'wallet' => (string) $wallet->balance,
                'unutilized_wallet' => (string) $wallet->balance,
                'winning_wallet' => '0',
                'bonus_wallet' => '0',
                'spin_remaining' => '0',
                'fcm' => '',
                'table_id' => '',
                'poker_table_id' => '',
                'head_tail_room_id' => '',
                'rummy_table_id' => '',
                'ander_bahar_room_id' => '',
                'dragon_tiger_room_id' => '',
                'jackpot_room_id' => '',
                'seven_up_room_id' => '',
                'rummy_pool_table_id' => '',
                'rummy_deal_table_id' => '',
                'color_prediction_room_id' => '',
                'color_prediction_1_min_room_id' => '',
                'color_prediction_3_min_room_id' => '',
                'car_roulette_room_id' => '',
                'animal_roulette_room_id' => '',
                'ludo_table_id' => '',
                'red_black_id' => '',
                'baccarat_id' => '',
                'jhandi_munda_id' => '',
                'roulette_id' => '',
                'rummy_tournament_table_id' => '',
                'target_room_id' => '',
                'ander_bahar_plus_room_id' => '',
                'golden_wheel_room_id' => '',
                'golden_wheel_star' => '',
                'game_played' => '0',
                'token' => $token,
                'status' => $user->is_active ? '1' : '0',
                'premium' => '0',
                'app_version' => config('app.public_version', '1.0.0'),
                'user_category_id' => '',
                'unique_token' => '',
                'added_date' => optional($user->created_at)?->toDateTimeString() ?? '',
                'updated_date' => optional($user->updated_at)?->toDateTimeString() ?? '',
                'isDeleted' => '0',
                'user_category' => '',
            ]],
            'user_kyc' => $kycDetails,
            'user_bank_details' => $bankDetails,
            'avatar' => [],
                'setting' => $this->settingsPayload($user),
            'notification_image' => '',
            'app_banner' => [],
            'code' => 200,
        ];
    }

    protected function settingsPayload(?User $user = null): array
    {
        $settings = $this->legacySettingsRow();
        $minRedeem = $settings ? (string) ($settings->min_redeem ?? '0') : '0';
        $dollar = $settings ? (string) ($settings->dollar ?? '1') : '1';
        $recovery = $this->buildRecoveryState($user);

        return [
            'min_redeem' => $minRedeem,
            'referral_amount' => '0',
            'contact_us' => config('app.url').'/contact-us',
            'terms' => config('app.url').'/terms-conditions',
            'privacy_policy' => config('app.url').'/privacy-policy',
            'help_support' => config('app.url').'/support',
            'app_version' => config('app.public_version', '1.0.0'),
            'share_text' => 'Play and win',
            'dollar' => $dollar,
            'referral_link' => config('app.url'),
            'referral_id' => '',
            'maintenance_mode' => config('platform.app.maintenance.enabled', false) ? '1' : '0',
            'maintenance_message' => (string) config('platform.app.maintenance.message', ''),
            'daily_bonus_status' => (string) ($settings->daily_bonus_status ?? '1'),
            'app_popop_status' => (string) ($settings->app_popop_status ?? '0'),
            'app_popup_title' => (string) ($settings->app_popup_title ?? ''),
            'app_popup_message' => (string) ($settings->app_popup_message ?? ''),
            'app_popup_button_text' => (string) ($settings->app_popup_button_text ?? 'Open'),
            'app_popup_url' => (string) ($settings->app_popup_url ?? ''),
            'app_popup_image' => ! empty($settings->app_popup_image)
                ? url('data/Settings/'.$settings->app_popup_image)
                : '',
            'recovery_has_verified_channel' => $recovery['has_verified_channel'],
            'recovery_mobile_verified' => $recovery['mobile_verified'],
            'recovery_email_verified' => $recovery['email_verified'],
            'recovery_whatsapp_verified' => $recovery['whatsapp_verified'],
            'recovery_whatsapp_value' => $recovery['whatsapp_value'],
            'recovery_should_show_reminder' => $recovery['should_show_reminder'],
            'recovery_reminder_title' => 'Secure Your Account',
            'recovery_reminder_message' => 'Add WhatsApp or email recovery now so you can recover your username and password later.',
        ];
    }

    protected function validateOtp(string $otpId, string $channelValue, string $otp, array $types, ?string $channelType = null): bool
    {
        $storageKey = $this->otpStorageKey($channelType ?? 'mobile', $channelValue);
        $otpRecord = LegacyOtp::query()
            ->whereKey($otpId)
            ->where('mobile', $storageKey)
            ->whereIn('type', $types)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpRecord || $otpRecord->otp_code !== $otp) {
            return false;
        }

        $otpRecord->forceFill([
            'is_used' => true,
            'used_at' => now(),
        ])->save();

        return true;
    }

    protected function buildRecoveryState(?User $user): array
    {
        if (! $user || ! $this->hasRecoveryTables()) {
            return [
                'has_verified_channel' => '0',
                'mobile_verified' => '0',
                'email_verified' => '0',
                'whatsapp_verified' => '0',
                'whatsapp_value' => '',
                'should_show_reminder' => '0',
            ];
        }

        $user->loadMissing('recoveryChannels', 'securityReminder');

        $mobileVerified = ! empty($user->mobile) && $user->mobile_verified_at ? '1' : '0';
        $emailVerified = ! empty($user->email) && $user->email_verified_at ? '1' : '0';
        $verifiedWhatsapp = $user->recoveryChannels
            ->first(function (UserRecoveryChannel $channel) {
                return $channel->channel_type === 'whatsapp' && $channel->is_verified;
            });

        $whatsappVerified = $verifiedWhatsapp ? '1' : '0';
        $hasVerifiedSecondary = $emailVerified === '1' || $whatsappVerified === '1';

        $reminder = $this->securityReminderRecord($user);
        if ($hasVerifiedSecondary && ! $reminder->is_completed) {
            $reminder->forceFill([
                'is_completed' => true,
                'dismissed_until' => null,
            ])->save();
        }

        $shouldShowReminder = ! $hasVerifiedSecondary
            && ! $reminder->is_completed
            && (! $reminder->dismissed_until || $reminder->dismissed_until->lte(now()));

        return [
            'has_verified_channel' => $hasVerifiedSecondary ? '1' : '0',
            'mobile_verified' => $mobileVerified,
            'email_verified' => $emailVerified,
            'whatsapp_verified' => $whatsappVerified,
            'whatsapp_value' => $verifiedWhatsapp?->channel_value ?? '',
            'should_show_reminder' => $shouldShowReminder ? '1' : '0',
        ];
    }

    protected function securityReminderRecord(User $user): UserSecurityReminder
    {
        if (! $this->hasRecoveryTables()) {
            return new UserSecurityReminder([
                'user_id' => $user->id,
                'last_shown_at' => null,
                'dismissed_until' => null,
                'is_completed' => false,
            ]);
        }

        return UserSecurityReminder::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'last_shown_at' => null,
                'dismissed_until' => null,
                'is_completed' => false,
            ]
        );
    }

    protected function normalizeRecoveryChannelValue(string $channelType, string $channelValue): string
    {
        $value = trim($channelValue);

        if ($channelType === 'email') {
            return strtolower($value);
        }

        if ($channelType === 'whatsapp') {
            return preg_replace('/\D+/', '', $value) ?? '';
        }

        return $value;
    }

    protected function validateRecoveryChannel(User $user, string $channelType, string $channelValue): ?string
    {
        if (! $this->hasRecoveryTables()) {
            return 'Recovery channels are not configured yet.';
        }

        if (! in_array($channelType, ['email', 'whatsapp'], true)) {
            return 'Unsupported recovery channel.';
        }

        if ($channelValue === '') {
            return ucfirst($channelType).' is required.';
        }

        if ($channelType === 'email') {
            if (! filter_var($channelValue, FILTER_VALIDATE_EMAIL)) {
                return 'Please enter a valid email address.';
            }

            $duplicateEmailUser = User::query()
                ->where('id', '!=', $user->id)
                ->whereRaw('LOWER(email) = ?', [strtolower($channelValue)])
                ->first();

            if ($duplicateEmailUser) {
                return 'This email is already linked to another account.';
            }

            return null;
        }

        if (strlen($channelValue) < 10 || strlen($channelValue) > 15) {
            return 'Please enter a valid WhatsApp number.';
        }

        $duplicateWhatsapp = UserRecoveryChannel::query()
            ->where('user_id', '!=', $user->id)
            ->where('channel_type', 'whatsapp')
            ->where('channel_value', $channelValue)
            ->where('is_verified', true)
            ->exists();

        if ($duplicateWhatsapp) {
            return 'This WhatsApp number is already linked to another account.';
        }

        return null;
    }

    protected function issueOtpResponse(string $channelType, string $channelValue, string $purpose): JsonResponse
    {
        if (! in_array($channelType, ['mobile', 'email', 'whatsapp'], true)) {
            return response()->json([
                'message' => 'Unsupported recovery channel.',
                'otp_id' => '',
                'code' => 422,
            ]);
        }

        $otpType = $purpose.'_'.$channelType;
        if ($purpose === 'forgot_password' && $channelType === 'mobile') {
            $otpType = 'forgot';
        }

        $storageKey = $this->otpStorageKey($channelType, $channelValue);
        $cooldownOtp = $this->findActiveOtpWithinCooldown($channelValue, $otpType);
        if ($cooldownOtp) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
                'otp_id' => (string) $cooldownOtp->id,
                'code' => 429,
                'retry_after' => $this->otpRetryAfterSeconds($cooldownOtp),
                'otp' => $this->shouldExposeOtpForTesting() ? (string) $cooldownOtp->otp_code : '',
            ]);
        }

        $otpId = random_int(100000, 999999);
        $otpCode = (string) random_int(1000, 9999);

        LegacyOtp::query()
            ->where('mobile', $storageKey)
            ->where('type', $otpType)
            ->where('is_used', false)
            ->delete();

        $otpRecord = LegacyOtp::query()->create([
            'id' => $otpId,
            'mobile' => $storageKey,
            'type' => $otpType,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        $deliveryAttempted = $this->dispatchOtpToChannel($channelType, $channelValue, $otpCode, $purpose);

        return response()->json([
            'message' => $deliveryAttempted
                ? 'OTP sent successfully.'
                : 'OTP generated successfully.',
            'otp_id' => (string) $otpRecord->id,
            'code' => 200,
            'otp' => $this->shouldExposeOtpForTesting() ? $otpCode : '',
            'retry_after' => $this->otpCooldownSeconds(),
        ]);
    }

    protected function dispatchOtpToChannel(string $channelType, string $channelValue, string $otpCode, string $purpose): bool
    {
        $message = $this->buildOtpMessage($otpCode, $purpose);

        if ($channelType === 'email') {
            if (! $this->isRealMailDeliveryConfigured()) {
                Log::warning('Recovery email OTP skipped due to non-delivery mailer configuration.', [
                    'mailer' => (string) config('mail.default', env('MAIL_MAILER', 'log')),
                    'purpose' => $purpose,
                    'to' => $channelValue,
                ]);

                return false;
            }

            try {
                Mail::raw($message, function ($mail) use ($channelValue, $purpose) {
                    $mail->to($channelValue)->subject($purpose === 'forgot_username' ? 'Recover your Rox Ludo username' : 'Rox Ludo verification code');
                });

                return true;
            } catch (\Throwable $exception) {
                Log::error('Recovery email OTP failed.', [
                    'purpose' => $purpose,
                    'to' => $channelValue,
                    'error' => $exception->getMessage(),
                ]);

                return false;
            }
        }

        if ($channelType === 'whatsapp') {
            $endpoint = trim((string) env('AISENSY_API_URL', ''));
            $apiKey = trim((string) env('AISENSY_API_KEY', ''));
            $campaignName = trim((string) env('AISENSY_OTP_CAMPAIGN_NAME', ''));

            if ($endpoint === '' || $apiKey === '' || $campaignName === '') {
                Log::warning('AiSensy OTP skipped due to missing configuration.', [
                    'endpoint_present' => $endpoint !== '',
                    'api_key_present' => $apiKey !== '',
                    'campaign_present' => $campaignName !== '',
                    'purpose' => $purpose,
                ]);
                return false;
            }

            try {
                $payload = [
                    'apiKey' => $apiKey,
                    'campaignName' => $campaignName,
                    'destination' => $channelValue,
                    'userName' => $this->buildAiSensyUserName($channelValue),
                    'templateParams' => [$otpCode],
                    'source' => 'rox-ludo recovery',
                    'media' => new \stdClass(),
                    'buttons' => [[
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [[
                            'type' => 'text',
                            'text' => $otpCode,
                        ]],
                    ]],
                    'carouselCards' => [],
                    'location' => new \stdClass(),
                    'attributes' => [
                        'purpose' => $purpose,
                    ],
                    'paramsFallbackValue' => [
                        'FirstName' => $otpCode,
                    ],
                ];

                $response = Http::timeout(20)
                    ->acceptJson()
                    ->asJson()
                    ->post($endpoint, $payload);

                Log::info('AiSensy OTP response', [
                    'purpose' => $purpose,
                    'destination' => $channelValue,
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'body' => $response->body(),
                ]);

                return $response->successful();
            } catch (\Throwable $exception) {
                Log::error('AiSensy OTP request failed.', [
                    'purpose' => $purpose,
                    'destination' => $channelValue,
                    'message' => $exception->getMessage(),
                ]);
                return false;
            }
        }

        return false;
    }

    protected function buildOtpMessage(string $otpCode, string $purpose): string
    {
        if ($purpose === 'forgot_username') {
            return "Your Rox Ludo username recovery OTP is {$otpCode}. It will expire in 10 minutes.";
        }

        if ($purpose === 'forgot_password') {
            return "Your Rox Ludo password reset OTP is {$otpCode}. It will expire in 10 minutes.";
        }

        return "Your Rox Ludo verification OTP is {$otpCode}. It will expire in 10 minutes.";
    }

    protected function buildAiSensyUserName(string $channelValue): string
    {
        $digits = preg_replace('/\D+/', '', $channelValue) ?? '';
        if ($digits === '') {
            return 'Rox User';
        }

        $suffix = substr($digits, -4);
        return 'Rox'.$suffix;
    }

    protected function isRealMailDeliveryConfigured(): bool
    {
        $mailer = strtolower(trim((string) config('mail.default', env('MAIL_MAILER', 'log'))));
        if ($mailer === '' || in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer !== 'smtp') {
            return true;
        }

        $host = trim((string) env('MAIL_HOST', ''));
        $port = trim((string) env('MAIL_PORT', ''));
        $fromAddress = trim((string) env('MAIL_FROM_ADDRESS', ''));
        $username = trim((string) env('MAIL_USERNAME', ''));
        $password = trim((string) env('MAIL_PASSWORD', ''));

        return $host !== ''
            && $port !== ''
            && $fromAddress !== ''
            && $username !== ''
            && strtolower($username) !== 'null'
            && $password !== ''
            && strtolower($password) !== 'null';
    }

    protected function shouldExposeOtpForTesting(): bool
    {
        return app()->environment('local')
            || (bool) config('app.debug')
            || trim((string) env('AISENSY_API_URL', '')) === ''
            || trim((string) env('AISENSY_API_KEY', '')) === ''
            || trim((string) env('AISENSY_OTP_CAMPAIGN_NAME', '')) === ''
            || config('mail.default') === 'log';
    }

    protected function otpCooldownSeconds(): int
    {
        return 60;
    }

    protected function findActiveOtpWithinCooldown(string $channelValue, string $otpType): ?LegacyOtp
    {
        $channelType = 'mobile';
        if (str_contains($otpType, '_email')) {
            $channelType = 'email';
        } elseif (str_contains($otpType, '_whatsapp')) {
            $channelType = 'whatsapp';
        }

        return LegacyOtp::query()
            ->where('mobile', $this->otpStorageKey($channelType, $channelValue))
            ->where('type', $otpType)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->where('created_at', '>=', now()->subSeconds($this->otpCooldownSeconds()))
            ->latest('created_at')
            ->first();
    }

    protected function otpStorageKey(string $channelType, string $channelValue): string
    {
        $normalizedType = strtolower(trim($channelType));
        if ($normalizedType === 'email') {
            return 'em_'.substr(md5(strtolower(trim($channelValue))), 0, 17);
        }

        if ($normalizedType === 'whatsapp') {
            $digits = preg_replace('/\D+/', '', $channelValue) ?? '';

            return 'wa_'.$digits;
        }

        return trim($channelValue);
    }

    protected function otpRetryAfterSeconds(LegacyOtp $otp): int
    {
        if (! $otp->created_at) {
            return $this->otpCooldownSeconds();
        }

        return max(
            1,
            $this->otpCooldownSeconds() - (int) $otp->created_at->diffInSeconds(now())
        );
    }

    protected function findUserByRecoveryChannel(string $channelType, string $channelValue): ?User
    {
        if ($channelType === 'mobile') {
            if ($channelValue === '') {
                return null;
            }

            return User::query()->where('mobile', $channelValue)->first();
        }

        if ($channelType === 'email') {
            if ($channelValue === '') {
                return null;
            }

            return User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($channelValue)])
                ->whereNotNull('email_verified_at')
                ->first();
        }

        if ($channelType === 'whatsapp') {
            if (! $this->hasRecoveryTables() || $channelValue === '') {
                return null;
            }

            $channel = UserRecoveryChannel::query()
                ->where('channel_type', 'whatsapp')
                ->where('channel_value', $channelValue)
                ->where('is_verified', true)
                ->first();

            return $channel?->user;
        }

        return null;
    }

    protected function upsertRecoveryChannel(User $user, string $channelType, string $channelValue, bool $verified): UserRecoveryChannel
    {
        if (! $this->hasRecoveryTables()) {
            return new UserRecoveryChannel([
                'user_id' => $user->id,
                'channel_type' => $channelType,
                'channel_value' => $channelValue,
                'is_verified' => $verified,
                'verified_at' => $verified ? now() : null,
                'is_primary' => false,
            ]);
        }

        $channel = UserRecoveryChannel::query()->firstOrNew([
            'user_id' => $user->id,
            'channel_type' => $channelType,
        ]);

        $channel->forceFill([
            'channel_value' => $channelValue,
            'is_verified' => $verified,
            'verified_at' => $verified ? now() : null,
            'is_primary' => $channelType === 'email' && ! empty($user->email) && strcasecmp((string) $user->email, $channelValue) === 0,
        ])->save();

        return $channel;
    }

    protected function hasRecoveryTables(): bool
    {
        return Schema::hasTable('user_recovery_channels') && Schema::hasTable('user_security_reminders');
    }

    protected function markRecoveryReminderCompletedIfReady(User $user): void
    {
        $state = $this->buildRecoveryState($user);
        if ($state['has_verified_channel'] !== '1') {
            return;
        }

        $reminder = $this->securityReminderRecord($user);
        if (! $reminder->is_completed) {
            $reminder->forceFill([
                'is_completed' => true,
                'dismissed_until' => null,
            ])->save();
        }
    }

    protected function makeUsername(string $name, string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile);
        $lastFive = substr($digits, -5);
        if ($lastFive === '' || strlen($lastFive) < 5) {
            $lastFive = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        }

        $base = 'rox' . $lastFive;
        $candidate = $base;

        $attempts = 0;
        while (User::query()->where('username', $candidate)->exists() && $attempts < 50) {
            $candidate = $base . random_int(0, 9);
            $attempts++;
        }

        if (User::query()->where('username', $candidate)->exists()) {
            $candidate = $base . Str::lower(Str::random(2));
        }

        return Str::limit($candidate, 50, '');
    }

    protected function normalizeProfileFirstName(string $name, string $mobile): string
    {
        $trimmed = trim($name);
        if ($trimmed !== '' && ! preg_match('/^user\d*$/i', $trimmed)) {
            return $trimmed;
        }

        $digits = preg_replace('/\D+/', '', $mobile);
        $lastFour = substr($digits, -4);
        if ($lastFour === '' || strlen($lastFour) < 4) {
            $lastFour = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        return 'Rox'.$lastFour;
    }

    protected function resolveReferrer(string $referralCode): ?User
    {
        $normalizedCode = strtoupper(trim($referralCode));
        $normalizedCode = preg_replace('/^777-/i', '', $normalizedCode);

        if ($normalizedCode === '') {
            return null;
        }

        return User::query()
            ->where('referral_code', $normalizedCode)
            ->orWhere('user_code', $normalizedCode)
            ->first();
    }

    protected function resolveTransferTargetUser(string $query): ?User
    {
        $normalized = trim($query);
        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->where('user_code', $normalized)
            ->orWhere('mobile', $normalized)
            ->orWhere('email', $normalized)
            ->orWhere('username', $normalized)
            ->first();
    }

    protected function maskTransferMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if ($digits === '' || strlen($digits) < 4) {
            return '';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    protected function maskTransferEmail(?string $email): string
    {
        $value = trim((string) $email);
        if ($value === '' || ! str_contains($value, '@')) {
            return '';
        }

        [$name, $domain] = explode('@', $value, 2);
        if ($name === '') {
            return '***@'.$domain;
        }

        $visible = strlen($name) > 2 ? substr($name, 0, 2) : substr($name, 0, 1);

        return $visible.str_repeat('*', max(1, strlen($name) - strlen($visible))).'@'.$domain;
    }

    protected function buildTransferHistoryItem(WalletTransfer $transfer, User $viewer): array
    {
        $isSender = $transfer->sender_user_id === $viewer->id;
        $counterparty = $isSender ? $transfer->receiver : $transfer->sender;

        return [
            'id' => (string) $transfer->id,
            'transfer_id' => (string) $transfer->transfer_uuid,
            'direction' => $isSender ? 'sent' : 'received',
            'status' => (string) $transfer->status,
            'amount' => (string) $transfer->amount,
            'currency' => (string) $transfer->currency,
            'user_id' => (string) ($counterparty?->user_code ?? ''),
            'username' => (string) ($counterparty?->username ?? ''),
            'mobile' => $this->maskTransferMobile($counterparty?->mobile),
            'email' => $this->maskTransferEmail($counterparty?->email),
            'note' => (string) ($transfer->note ?? ''),
            'added_date' => optional($transfer->processed_at ?: $transfer->created_at)?->toDateTimeString() ?? '',
        ];
    }

    protected function buildTransferHistoryForViewer(User $user): array
    {
        $history = WalletTransfer::query()
            ->with(['sender', 'receiver'])
            ->where(function ($query) use ($user) {
                $query->where('sender_user_id', $user->id)
                    ->orWhere('receiver_user_id', $user->id);
            })
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (WalletTransfer $transfer) => $this->buildTransferHistoryItem($transfer, $user))
            ->values()
            ->all();

        if (! empty($history)) {
            return $history;
        }

        return WalletTransaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['transfer_sent', 'transfer_received'])
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (WalletTransaction $transaction) => $this->buildTransferHistoryItemFromTransaction($transaction))
            ->filter()
            ->values()
            ->all();
    }

    protected function buildTransferHistoryItemFromTransaction(WalletTransaction $transaction): ?array
    {
        $meta = is_array($transaction->meta) ? $transaction->meta : [];
        $direction = $transaction->type === 'transfer_received' ? 'received' : 'sent';

        return [
            'id' => 'txn-'.$transaction->id,
            'transfer_id' => (string) ($meta['transfer_uuid'] ?? $transaction->transaction_uuid ?? ''),
            'direction' => $direction,
            'status' => (string) ($transaction->status ?? 'completed'),
            'amount' => (string) $transaction->amount,
            'currency' => (string) ($transaction->currency ?? 'INR'),
            'user_id' => (string) ($meta['counterparty_user_code'] ?? ''),
            'username' => (string) ($meta['counterparty_username'] ?? ''),
            'mobile' => '',
            'email' => '',
            'note' => (string) ($transaction->description ?? ''),
            'added_date' => optional($transaction->processed_at ?: $transaction->created_at)?->toDateTimeString() ?? '',
        ];
    }

    protected function respondGameLogList(Request $request, string $label, string $mode = 'GENERIC'): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'GameLog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildGenericGameLogs($user, $label, $mode);

        return response()->json([
            'message' => 'Success',
            'GameLog' => $logs,
            'MinRedeem' => $this->legacyMinRedeem(),
            'code' => 200,
        ]);
    }

    protected function respondGameLogArray(Request $request, string $label, string $mode): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'GameLog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildGenericGameLogs($user, $label, $mode);

        return response()->json([
            'message' => 'Success',
            'GameLog' => $logs,
            'MinRedeem' => $this->legacyMinRedeem(),
            'code' => 200,
        ]);
    }

    protected function respondRummyLogList(Request $request, string $label): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'GameLog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildRummyGameLogs($user, $label);

        return response()->json([
            'message' => 'Success',
            'GameLog' => $logs,
            'MinRedeem' => $this->legacyMinRedeem(),
            'code' => 200,
        ]);
    }

    protected function respondRummyPointLog(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'RummyGameLog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildRummyPointLogs($user);

        return response()->json([
            'message' => 'Success',
            'RummyGameLog' => $logs,
            'MinRedeem' => $this->legacyMinRedeem(),
            'code' => 200,
        ]);
    }

    protected function respondPokerLog(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'Pokerlog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildPokerLogs($user);

        return response()->json([
            'message' => 'Success',
            'Pokerlog' => $logs,
            'code' => 200,
        ]);
    }

    protected function respondJhandiMundaLog(Request $request): JsonResponse
    {
        $user = $this->resolveLegacyUser($request->input('user_id'), $request->input('token'));

        if (! $user) {
            return response()->json([
                'message' => 'Invalid User',
                'JhandiMundalog' => [],
                'code' => 411,
            ]);
        }

        $logs = $this->buildJhandiMundaLogs($user);

        return response()->json([
            'message' => 'Success',
            'JhandiMundalog' => $logs,
            'code' => 200,
        ]);
    }

    protected function buildGenericGameLogs(User $user, string $label = 'Game', string $mode = 'GENERIC'): array
    {
        $transactions = $this->fetchWalletTransactions($user);

        return $transactions->map(function (WalletTransaction $tx) use ($user, $label, $mode) {
            $amount = (string) $tx->amount;
            $userAmount = (string) ($tx->balance_after ?? $tx->balance_before ?? 0);
            $commission = '0';
            $winning = $tx->direction === 'credit' ? $amount : '0';
            $date = $this->formatLegacyDate($tx);
            $referenceId = (string) ($tx->reference_id ?? $tx->transaction_uuid);

            if ($mode === 'AB') {
                return [
                    'id' => (string) $tx->id,
                    'ander_baher_id' => $referenceId,
                    'user_id' => (string) $user->user_code,
                    'bet' => $label,
                    'amount' => $amount,
                    'winning_amount' => $winning,
                    'user_amount' => $userAmount,
                    'comission_amount' => $commission,
                    'added_date' => $date,
                    'room_id' => '',
                ];
            }

            if ($mode === 'DNT') {
                return [
                    'id' => (string) $tx->id,
                    'dragon_tiger_id' => $referenceId,
                    'user_id' => (string) $user->user_code,
                    'bet' => $label,
                    'amount' => $amount,
                    'winning_amount' => $winning,
                    'user_amount' => $userAmount,
                    'comission_amount' => $commission,
                    'minus_unutilized_wallet' => '0',
                    'minus_winning_wallet' => '0',
                    'minus_bonus_wallet' => '0',
                    'added_date' => $date,
                    'room_id' => '',
                ];
            }

            if ($mode === 'SEVEN') {
                return [
                    'id' => (string) $tx->id,
                    'seven_up_id' => $referenceId,
                    'user_id' => (string) $user->user_code,
                    'bet' => $label,
                    'amount' => $amount,
                    'winning_amount' => $winning,
                    'user_amount' => $userAmount,
                    'comission_amount' => $commission,
                    'added_date' => $date,
                    'room_id' => '',
                ];
            }

            return [
                'id' => (string) $tx->id,
                'user_id' => (string) $user->user_code,
                'bet' => $label,
                'amount' => $amount,
                'winning_amount' => $winning,
                'user_amount' => $userAmount,
                'comission_amount' => $commission,
                'added_date' => $date,
                'room_id' => '',
            ];
        })->values()->all();
    }

    protected function buildRummyGameLogs(User $user, string $label): array
    {
        $transactions = $this->fetchWalletTransactions($user);

        return $transactions->map(function (WalletTransaction $tx) use ($user, $label) {
            $amount = (string) $tx->amount;
            $userAmount = (string) ($tx->balance_after ?? $tx->balance_before ?? 0);
            $commission = '0';
            $winning = $tx->direction === 'credit' ? $amount : '0';

            return [
                'game_id' => (string) ($tx->reference_id ?? $tx->transaction_uuid),
                'user_id' => (string) $user->user_code,
                'action' => $label,
                'amount' => $amount,
                'user_amount' => $userAmount,
                'winning_amount' => $winning,
                'commission_amount' => $commission,
                'added_date' => $this->formatLegacyDate($tx),
            ];
        })->values()->all();
    }

    protected function buildRummyPointLogs(User $user): array
    {
        $transactions = $this->fetchWalletTransactions($user);

        return $transactions->map(function (WalletTransaction $tx) use ($user) {
            return [
                'game_id' => (string) ($tx->reference_id ?? $tx->transaction_uuid),
                'user_id' => (string) $user->user_code,
                'action' => 'Point Rummy',
                'amount' => (string) $tx->amount,
                'user_amount' => (string) ($tx->balance_after ?? $tx->balance_before ?? 0),
                'comission_amount' => '0',
                'added_date' => $this->formatLegacyDate($tx),
            ];
        })->values()->all();
    }

    protected function buildPokerLogs(User $user): array
    {
        $transactions = $this->fetchWalletTransactions($user);

        return $transactions->map(function (WalletTransaction $tx) use ($user) {
            return [
                'game_id' => (string) ($tx->reference_id ?? $tx->transaction_uuid),
                'user_id' => (string) $user->user_code,
                'action' => (string) ($tx->description ?: 'Poker'),
                'amount' => (string) $tx->amount,
                'user_amount' => (string) ($tx->balance_after ?? $tx->balance_before ?? 0),
                'comission_amount' => '0',
                'added_date' => $this->formatLegacyDate($tx),
            ];
        })->values()->all();
    }

    protected function buildJhandiMundaLogs(User $user): array
    {
        $transactions = $this->fetchWalletTransactions($user);

        return $transactions->map(function (WalletTransaction $tx) use ($user) {
            $amount = (string) $tx->amount;
            $userAmount = (string) ($tx->balance_after ?? $tx->balance_before ?? 0);
            $winning = $tx->direction === 'credit' ? $amount : '0';

            return [
                'id' => (string) $tx->id,
                'jhandi_munda_id' => (string) ($tx->reference_id ?? $tx->transaction_uuid),
                'user_id' => (string) $user->user_code,
                'bet' => 'Jhandi Munda',
                'amount' => $amount,
                'winning_amount' => $winning,
                'user_amount' => $userAmount,
                'comission_amount' => '0',
                'minus_unutilized_wallet' => '0',
                'minus_winning_wallet' => '0',
                'minus_bonus_wallet' => '0',
                'added_date' => $this->formatLegacyDate($tx),
            ];
        })->values()->all();
    }

    protected function fetchWalletTransactions(User $user, int $limit = 50)
    {
        return WalletTransaction::query()
            ->where('user_id', $user->id)
            ->whereNotIn('type', ['transfer_sent', 'transfer_received'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function formatLegacyDate(WalletTransaction $tx): string
    {
        return optional($tx->processed_at ?? $tx->created_at)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');
    }

    protected function ensureProfile(User $user)
    {
        return $user->profile ?: $user->profile()->create([]);
    }

    protected function legacyBankDetails(User $user): array
    {
        $user->loadMissing('profile');
        $prefs = $user->profile?->preferences ?? [];
        $bank = $prefs['bank_details'] ?? [];
        $crypto = $prefs['crypto_details'] ?? [];

        if (empty($bank) && empty($crypto)) {
            return [];
        }

        return [[
            'id' => '0',
            'user_id' => (string) $user->user_code,
            'bank_name' => (string) ($bank['bank_name'] ?? ''),
            'ifsc_code' => (string) ($bank['ifsc_code'] ?? ''),
            'acc_holder_name' => (string) ($bank['acc_holder_name'] ?? ''),
            'acc_no' => (string) ($bank['acc_no'] ?? ''),
            'passbook_img' => (string) ($bank['passbook_img'] ?? ''),
            'upi_id' => (string) ($bank['upi_id'] ?? ''),
            'crypto_address' => (string) ($crypto['crypto_address'] ?? ''),
            'crypto_wallet_type' => (string) ($crypto['crypto_wallet_type'] ?? ''),
            'crypto_qr' => (string) ($crypto['crypto_qr'] ?? ''),
            'added_date' => '',
            'updated_date' => '',
            'isDeleted' => '0',
        ]];
    }

    protected function legacyKycDetails(User $user): array
    {
        $user->loadMissing('profile');
        $prefs = $user->profile?->preferences ?? [];
        $kyc = $prefs['kyc_details'] ?? [];

        if (empty($kyc)) {
            return [];
        }

        return [[
            'id' => '0',
            'user_id' => (string) $user->user_code,
            'pan_no' => (string) ($kyc['pan_no'] ?? ''),
            'pan_img' => (string) ($kyc['pan_img'] ?? ''),
            'aadhar_no' => (string) ($kyc['aadhar_no'] ?? ''),
            'aadhar_img' => (string) ($kyc['aadhar_img'] ?? ''),
            'status' => (string) ($kyc['status'] ?? 'pending'),
            'reason' => '',
            'added_date' => '',
            'updated_date' => '',
            'isDeleted' => '0',
        ]];
    }

    protected function storeBase64Image(string $base64, string $prefix): string
    {
        if ($base64 === '') {
            return '';
        }

        if (str_contains($base64, ',')) {
            $parts = explode(',', $base64, 2);
            $base64 = $parts[1];
        }

        $base64 = str_replace(' ', '+', $base64);
        $data = base64_decode($base64);

        if ($data === false) {
            return '';
        }

        $dir = public_path('data/post');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = $prefix.Str::lower(Str::random(12)).'.jpg';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;
        file_put_contents($path, $data);

        return $filename;
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function setLegacySessionLockTimeouts(): void
    {
        try {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
        } catch (\Throwable $exception) {
            // Ignore when the current driver/server does not support this setting.
        }

        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
        } catch (\Throwable $exception) {
            // Ignore when the current driver/server does not support this setting.
        }
    }

    protected function legacySettingsRow(): ?object
    {
        if (! $this->legacyTableExists('tbl_setting')) {
            return null;
        }

        return DB::table('tbl_setting')
            ->where('isDeleted', 0)
            ->orderByDesc('id')
            ->first();
    }

    protected function legacyMinRedeem(): string
    {
        $settings = $this->legacySettingsRow();
        if (! $settings || ! isset($settings->min_redeem)) {
            return '0';
        }

        return (string) $settings->min_redeem;
    }

    protected function legacyMinWithdrawal(): string
    {
        $settings = $this->legacySettingsRow();
        if (! $settings || ! isset($settings->min_withdrawal)) {
            return '0';
        }

        return (string) $settings->min_withdrawal;
    }

    protected function legacyBankInfo(User $user): ?array
    {
        $user->loadMissing('profile');
        $prefs = $user->profile?->preferences ?? [];
        $bank = $prefs['bank_details'] ?? [];
        $crypto = $prefs['crypto_details'] ?? [];

        if (empty($bank) && empty($crypto)) {
            return null;
        }

        return [
            'bank_name' => (string) ($bank['bank_name'] ?? ''),
            'ifsc_code' => (string) ($bank['ifsc_code'] ?? ''),
            'acc_holder_name' => (string) ($bank['acc_holder_name'] ?? ''),
            'acc_no' => (string) ($bank['acc_no'] ?? ''),
            'passbook_img' => (string) ($bank['passbook_img'] ?? ''),
            'upi_id' => (string) ($bank['upi_id'] ?? ''),
            'crypto_wallet_type' => (string) ($crypto['crypto_wallet_type'] ?? ''),
            'crypto_qr' => (string) ($crypto['crypto_qr'] ?? ''),
            'crypto_address' => (string) ($crypto['crypto_address'] ?? ''),
            'mobile' => (string) ($user->mobile ?? ''),
        ];
    }

    protected function createWithdrawalLog(
        int $legacyUserId,
        int $redeemId,
        float $amount,
        array $bankInfo,
        int $type,
        int $agentId,
        float $price,
        ?int $laravelUserId = null
    ): ?int {
        $transactionId = $laravelUserId
            ? 'ROX-'.$laravelUserId.'-'.Str::upper(Str::random(8))
            : null;

        $payload = [
            'user_id' => $legacyUserId,
            'redeem_id' => $redeemId,
            'bank_name' => $bankInfo['bank_name'] ?? '',
            'ifsc_code' => $bankInfo['ifsc_code'] ?? '',
            'acc_holder_name' => $bankInfo['acc_holder_name'] ?? '',
            'acc_no' => $bankInfo['acc_no'] ?? '',
            'passbook_img' => $bankInfo['passbook_img'] ?? '',
            'crypto_wallet_type' => $bankInfo['crypto_wallet_type'] ?? '',
            'crypto_qr' => $bankInfo['crypto_qr'] ?? '',
            'crypto_address' => $bankInfo['crypto_address'] ?? '',
            'coin' => $amount,
            'price' => $price,
            'agent_id' => $agentId,
            'mobile' => $bankInfo['mobile'] ?? '',
            'type' => $type,
            'status' => 0,
            'transaction_id' => $transactionId,
            'payout_response' => '',
            'upi_id' => $bankInfo['upi_id'] ?? '',
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
            'isDeleted' => 0,
        ];

        $this->setLegacySessionLockTimeouts();

        try {
            return (int) DB::table('tbl_withdrawal_log')->insertGetId($payload);
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'Unknown column') && str_contains($message, 'upi_id')) {
                unset($payload['upi_id']);

                return (int) DB::table('tbl_withdrawal_log')->insertGetId($payload);
            }

            report($exception);

            return null;
        }
    }

    protected function applyWithdrawalDebit(User $user, object $legacyUser, float $amount, int $withdrawalId): void
    {
        $this->setLegacySessionLockTimeouts();

        app(WalletService::class)->debit(
            user: $user,
            amount: $amount,
            referenceType: 'withdrawal',
            referenceId: $withdrawalId,
            description: 'Withdrawal request',
            currency: 'INR',
            meta: [
                'legacy' => true,
                'legacy_table' => 'tbl_withdrawal_log',
            ],
        );

        try {
            DB::table('tbl_users')
                ->where('id', $legacyUser->id)
                ->update([
                    'wallet' => DB::raw('wallet - '.$amount),
                    'winning_wallet' => DB::raw('winning_wallet - '.$amount),
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    protected function applyWelcomeReferralBonus(object $legacyUser, User $user, int $day, float $amount): void
    {
        if (! $this->legacyTableExists('tbl_setting') || ! $this->legacyTableExists('tbl_welcome_ref')) {
            return;
        }

        if (! $this->legacyTableExists('tbl_users')) {
            return;
        }

        $settings = $this->legacySettingsRow();
        if (! $settings) {
            return;
        }

        $referrerId = (int) ($legacyUser->referred_by ?? 0);
        if ($referrerId <= 0) {
            return;
        }

        for ($level = 1; $level <= 3; $level++) {
            $levelKey = 'level_'.$level;
            $percent = (float) ($settings->{$levelKey} ?? 0);
            if ($percent <= 0) {
                continue;
            }

            $coin = round(($amount * $percent) / 100, 2);
            if ($coin <= 0) {
                continue;
            }

            DB::table('tbl_users')
                ->where('id', $referrerId)
                ->update([
                    'wallet' => DB::raw('wallet + '.$coin),
                    'bonus_wallet' => DB::raw('bonus_wallet + '.$coin),
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]);

            DB::table('tbl_welcome_ref')->insert([
                'user_id' => $referrerId,
                'day' => $day,
                'bonus_user_id' => $legacyUser->id,
                'coin' => $coin,
                'added_date' => now()->format('Y-m-d H:i:s'),
                'level' => $level,
            ]);

            $refLegacyUser = DB::table('tbl_users')->where('id', $referrerId)->first();
            $laravelRef = $refLegacyUser ? $this->resolveLaravelUserFromLegacy($refLegacyUser) : null;
            if ($laravelRef) {
                app(WalletService::class)->credit(
                    user: $laravelRef,
                    amount: $coin,
                    referenceType: WalletTransaction::class,
                    referenceId: $day,
                    description: 'Welcome bonus referral',
                    currency: 'INR',
                    meta: [
                        'legacy' => true,
                        'legacy_table' => 'tbl_welcome_ref',
                        'level' => $level,
                    ],
                );
            }

            $referrerId = (int) ($refLegacyUser->referred_by ?? 0);
            if ($referrerId <= 0) {
                break;
            }
        }
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

    protected function resolveLegacyDbUser(User $user): ?object
    {
        try {
            $this->setLegacySessionLockTimeouts();

            $query = DB::table('tbl_users');
            $hasCriteria = false;

            if (! empty($user->mobile)) {
                $query->orWhere('mobile', $user->mobile);
                $hasCriteria = true;
            }

            if (! empty($user->email)) {
                $query->orWhere('email', $user->email);
                $hasCriteria = true;
            }

            if (! $hasCriteria) {
                if (is_numeric($user->user_code ?? null)) {
                    $legacy = DB::table('tbl_users')
                        ->where('id', (int) $user->user_code)
                        ->first();
                    if ($legacy) {
                        return $legacy;
                    }
                }

                return $this->ensureLegacyUserRow($user);
            }

            $legacy = $query->orderByDesc('id')->first();
            if ($legacy) {
                return $this->syncLegacyReferralLink($legacy, $user);
            }

            return $this->ensureLegacyUserRow($user);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    protected function ensureLegacyUserRow(User $user): ?object
    {
        try {
            $this->setLegacySessionLockTimeouts();

            $wallet = $this->ensureWallets($user);
            $name = $user->profile?->first_name ?: $user->username ?: 'Player';
            $referrerLegacyId = $this->resolveLegacyReferrerId($user);

            $id = DB::table('tbl_users')->insertGetId([
                'name' => $name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'wallet' => $wallet?->balance ?? 0,
                'bonus_wallet' => 0,
                'winning_wallet' => 0,
                'bank_detail' => '',
                'adhar_card' => '',
                'upi' => '',
                'referred_by' => $referrerLegacyId,
                'game_played' => 0,
                'isDeleted' => 0,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

            return DB::table('tbl_users')->where('id', $id)->first();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    protected function syncLegacyReferralLink(object $legacyUser, User $user): object
    {
        if (! $this->legacyTableExists('tbl_users')) {
            return $legacyUser;
        }

        $referrerLegacyId = $this->resolveLegacyReferrerId($user);
        $currentReferrerId = (int) ($legacyUser->referred_by ?? 0);

        if ($referrerLegacyId > 0 && $currentReferrerId !== $referrerLegacyId) {
            DB::table('tbl_users')
                ->where('id', $legacyUser->id)
                ->update([
                    'referred_by' => $referrerLegacyId,
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]);

            $legacyUser = DB::table('tbl_users')->where('id', $legacyUser->id)->first() ?? $legacyUser;
        }

        return $legacyUser;
    }

    protected function resolveLegacyReferrerId(User $user): int
    {
        $referrer = $user->referrer instanceof User
            ? $user->referrer
            : $this->resolveDefaultReferrer();

        if (! $referrer instanceof User) {
            return 0;
        }

        $referrerLegacy = $this->resolveLegacyDbUser($referrer);

        return (int) ($referrerLegacy->id ?? 0);
    }

    protected function resolveDefaultReferrer(): ?User
    {
        return User::query()
            ->where('user_code', '12345678')
            ->orWhere('id', 1)
            ->orderByRaw("CASE WHEN user_code = '12345678' THEN 0 ELSE 1 END")
            ->first();
    }
}
