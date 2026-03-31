<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\LegacyOtp;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $password = (string) $request->input('password');
        $name = (string) $request->input('name', 'Player');
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');
        $referralCode = (string) $request->input('referral_code', '');

        if ($mobile === '' || $password === '' || $otpId === '' || $otp === '') {
            return response()->json([
                'message' => 'Required signup fields are missing.',
                'user_id' => '',
                'token' => '',
                'code' => 400,
            ]);
        }

        if (! $this->validateOtp($otpId, $mobile, $otp, ['register', 'signup'])) {
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
            : null;

        $user = DB::transaction(function () use ($mobile, $password, $name, $referrer) {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'username' => $this->makeUsername($name, $mobile),
                'mobile' => $mobile,
                'password' => Hash::make($password),
                'referral_code' => strtoupper(Str::random(8)),
                'referred_by_user_id' => $referrer?->id,
                'is_active' => true,
                'is_banned' => false,
                'mobile_verified_at' => now(),
            ]);

            $user->profile()->create([
                'first_name' => $name,
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

    public function forgotPassword(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $user = User::query()->where('mobile', $mobile)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Mobile number not found.',
                'otp_id' => '',
                'code' => 404,
            ]);
        }

        $otpId = random_int(100000, 999999);
        $otpCode = (string) random_int(1000, 9999);

        LegacyOtp::query()->where('mobile', $mobile)->where('type', 'forgot')->where('is_used', false)->delete();

        $otpRecord = LegacyOtp::query()->create([
            'id' => $otpId,
            'mobile' => $mobile,
            'type' => 'forgot',
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp_id' => (string) $otpRecord->id,
            'code' => 200,
            'otp' => $otpCode,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $mobile = (string) $request->input('mobile');
        $otpId = (string) $request->input('otp_id');
        $otp = (string) $request->input('otp');
        $newPassword = (string) $request->input('new_password');

        if (! $this->validateOtp($otpId, $mobile, $otp, ['forgot'])) {
            return response()->json([
                'message' => 'Invalid OTP.',
                'code' => 404,
            ]);
        }

        $user = User::query()->where('mobile', $mobile)->first();
        if (! $user) {
            return response()->json([
                'message' => 'Mobile number not found.',
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
        return response()->json([
            'message' => 'Settings fetched successfully.',
            'setting' => $this->settingsPayload(),
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
                'profile_pic' => '',
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
            'user_kyc' => [],
            'user_bank_details' => [],
            'avatar' => [],
            'setting' => $this->settingsPayload(),
            'notification_image' => '',
            'app_banner' => [],
            'code' => 200,
        ];
    }

    protected function settingsPayload(): array
    {
        return [
            'min_redeem' => '0',
            'referral_amount' => '0',
            'contact_us' => config('app.url').'/contact-us',
            'terms' => config('app.url').'/terms-conditions',
            'privacy_policy' => config('app.url').'/privacy-policy',
            'help_support' => config('app.url').'/support',
            'app_version' => config('app.public_version', '1.0.0'),
            'share_text' => 'Play and win',
            'dollar' => '1',
            'referral_link' => config('app.url'),
            'referral_id' => '',
            'maintenance_mode' => config('platform.app.maintenance.enabled', false) ? '1' : '0',
            'maintenance_message' => (string) config('platform.app.maintenance.message', ''),
        ];
    }

    protected function validateOtp(string $otpId, string $mobile, string $otp, array $types): bool
    {
        $otpRecord = LegacyOtp::query()
            ->whereKey($otpId)
            ->where('mobile', $mobile)
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

    protected function makeUsername(string $name, string $mobile): string
    {
        $base = Str::lower(Str::slug($name ?: 'player', '_'));
        $suffix = substr(preg_replace('/\D+/', '', $mobile), -4) ?: random_int(1000, 9999);

        return Str::limit($base.'_'.$suffix.'_'.Str::lower(Str::random(4)), 50, '');
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
}
