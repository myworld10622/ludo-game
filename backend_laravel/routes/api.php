<?php

use App\Http\Controllers\Api\Legacy\UserCompatibilityController;
use App\Http\Controllers\Api\Legacy\LudoCompatibilityController;
use App\Http\Controllers\Api\Internal\V1\LudoMatchController as InternalLudoMatchController;
use App\Http\Controllers\Api\Internal\V1\LudoRoomMessageController as InternalLudoRoomMessageController;
use App\Http\Controllers\Api\Internal\V1\TournamentMatchResultController as InternalTournamentMatchResult;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\FriendController;
use App\Http\Controllers\Api\V1\LudoController;
use App\Http\Controllers\Api\V1\LudoRoomMessageController;
use App\Http\Controllers\Api\V1\TournamentController;
use App\Http\Controllers\Api\V1\TournamentRegistrationController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

$version = config('platform.api.default_version', 'v1');

// ── Internal Routes (Node.js → Laravel, secured via internal.api middleware) ──
Route::prefix('internal/v1')
    ->middleware(['internal.api'])
    ->group(function () {
        // Non-tournament Ludo match callbacks (existing)
        Route::prefix('ludo')->group(function () {
            Route::post('/rooms/{roomUuid}/start', [InternalLudoMatchController::class, 'start']);
            Route::post('/matches/{matchUuid}/complete', [InternalLudoMatchController::class, 'complete']);
            Route::get('/rooms/{roomUuid}/messages', [InternalLudoRoomMessageController::class, 'index']);
            Route::post('/rooms/{roomUuid}/messages', [InternalLudoRoomMessageController::class, 'store']);
        });

        // Tournament match result (new — called by Node.js after each match ends)
        Route::prefix('tournaments')->group(function () {
            Route::post('/matches/{match}/result', [InternalTournamentMatchResult::class, 'submit']);
            Route::post('/matches/{match}/override', [InternalTournamentMatchResult::class, 'override']);
        });
    });

// ── Legacy Compatibility Routes ───────────────────────────────────────────────
foreach (['user', 'User'] as $legacyUserPrefix) {
    Route::prefix($legacyUserPrefix)->group(function () {
        Route::post('/guest_register', [UserCompatibilityController::class, 'guestRegister']);
        Route::post('/send_otp', [UserCompatibilityController::class, 'sendOtp']);
        Route::post('/register', [UserCompatibilityController::class, 'register']);
        Route::post('/login', [UserCompatibilityController::class, 'login']);
        Route::post('/social_login', [UserCompatibilityController::class, 'socialLogin']);
        Route::post('/profile', [UserCompatibilityController::class, 'profile']);
        Route::post('/wallet', [UserCompatibilityController::class, 'wallet']);
        Route::post('/randomBoatUsers', [UserCompatibilityController::class, 'randomBoatUsers']);
        Route::post('/game_on_off', [UserCompatibilityController::class, 'gameOnOff']);
        Route::post('/setting', [UserCompatibilityController::class, 'setting']);
        Route::post('/forgot_password', [UserCompatibilityController::class, 'forgotPassword']);
        Route::post('/update_password', [UserCompatibilityController::class, 'updatePassword']);
    });
}

Route::prefix('ludo')->group(function () {
    Route::post('/get_table_master', [LudoCompatibilityController::class, 'getTableMaster']);
    Route::post('/get_table_master_bachpan', [LudoCompatibilityController::class, 'getTableMasterBachpan']);
});

// ── API v1 Routes ─────────────────────────────────────────────────────────────
Route::prefix($version)
    ->middleware(['api.version'])
    ->group(function () {

        // Health & Config
        Route::get('/health', HealthController::class);
        Route::get('/app-config', AppConfigController::class);
        Route::get('/games', [GameController::class, 'index']);
        Route::get('/home', [GameController::class, 'home']);

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/signup', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/social-login', [AuthController::class, 'socialLogin']);
            Route::post('/logout', [AuthController::class, 'logout'])->middleware('api.auth');
        });

        Route::middleware('api.auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::get('/me/profile', [AuthController::class, 'me']);
        });

        // ── Tournament Routes ──────────────────────────────────────────────────
        Route::prefix('tournaments')->group(function () {

            // Public browsing (no auth required)
            Route::get('/', [TournamentController::class, 'index']);
            Route::get('/{tournament}/bracket', [TournamentController::class, 'bracket']);
            Route::get('/{tournament}/leaderboard', [TournamentController::class, 'leaderboard']);
            Route::get('/private/{invite_code}', [TournamentController::class, 'showByInviteCode']);
            Route::get('/{tournament}', [TournamentController::class, 'show']);

            // Authenticated user actions
            Route::middleware('api.auth')->group(function () {
                // Create tournament (user-created)
                Route::post('/', [TournamentController::class, 'store']);

                // Claim match room (called by Node.js with user's token)
                Route::post('/{tournament}/claim-room', [TournamentController::class, 'claimRoom']);

                // Register / unregister
                Route::post('/{tournament}/register', [TournamentRegistrationController::class, 'register']);
                Route::delete('/{tournament}/register', [TournamentRegistrationController::class, 'cancel']);

                // User history
                Route::get('/me/history', [TournamentRegistrationController::class, 'myHistory']);

                // Admin-only actions (additional admin middleware applied inside controllers)
                Route::post('/{tournament}/approve', [TournamentController::class, 'approve']);
                Route::post('/{tournament}/publish', [TournamentController::class, 'publish']);
                Route::post('/{tournament}/close-registration', [TournamentController::class, 'closeRegistration']);
                Route::post('/{tournament}/generate-bracket', [TournamentController::class, 'generateBracket']);
                Route::post('/{tournament}/cancel', [TournamentController::class, 'cancel']);
                Route::get('/{tournament}/financials', [TournamentController::class, 'financials']);
                Route::get('/{tournament}/registrations', [TournamentRegistrationController::class, 'list']);
                Route::post('/{tournament}/add-bot', [TournamentRegistrationController::class, 'addBot']);
                Route::delete('/{tournament}/bots/{registration}', [TournamentRegistrationController::class, 'removeBot']);
            });
        });

        // ── Wallet Routes ──────────────────────────────────────────────────────
        Route::prefix('wallet')->middleware('api.auth')->group(function () {
            Route::get('/', [WalletController::class, 'summary']);
            Route::get('/history', [WalletController::class, 'history']);
        });

        // ── Ludo Queue Routes ─────────────────────────────────────────────────
        Route::prefix('ludo')->middleware('api.auth')->group(function () {
            Route::post('/queue/join', [LudoController::class, 'joinQueue']);
            Route::get('/rooms/{roomUuid}', [LudoController::class, 'room']);
            Route::get('/rooms/{roomUuid}/messages', [LudoRoomMessageController::class, 'index']);
        });

        // ── Social / Friends Routes ───────────────────────────────────────────
        Route::prefix('friends')->middleware('api.auth')->group(function () {
            Route::get('/', [FriendController::class, 'index']);
            Route::get('/requests', [FriendController::class, 'requests']);
            Route::post('/request', [FriendController::class, 'send']);
            Route::post('/request/by-player-id', [FriendController::class, 'sendByPlayerId']);
            Route::post('/request/{requestUuid}/respond', [FriendController::class, 'respond']);
            Route::post('/request/{requestUuid}/accept', [FriendController::class, 'respond'])
                ->defaults('action', 'accept');
            Route::post('/request/{requestUuid}/reject', [FriendController::class, 'respond'])
                ->defaults('action', 'reject');
        });

        Route::prefix('users')->middleware('api.auth')->group(function () {
            Route::get('/search-by-player-id/{playerId}', [FriendController::class, 'searchByPlayerId']);
        });
    });
