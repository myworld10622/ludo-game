<?php

use App\Http\Controllers\Api\Legacy\UserCompatibilityController;
use App\Http\Controllers\Api\Internal\V1\LudoMatchController as InternalLudoMatchController;
use App\Http\Controllers\Api\Internal\V1\TournamentLudoMatchController as InternalTournamentLudoMatchController;
use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LudoController;
use App\Http\Controllers\Api\V1\TournamentController as ApiTournamentController;
use App\Http\Controllers\Api\V1\TournamentRoomController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

$version = config('platform.api.default_version', 'v1');

Route::prefix('internal/v1')
    ->middleware(['internal.api'])
    ->group(function () {
        Route::prefix('ludo')->group(function () {
            Route::post('/rooms/{roomUuid}/start', [InternalLudoMatchController::class, 'start']);
            Route::post('/matches/{matchUuid}/complete', [InternalLudoMatchController::class, 'complete']);
        });
        Route::prefix('tournaments/ludo')->group(function () {
            Route::post('/rooms/{roomUuid}/complete', [InternalTournamentLudoMatchController::class, 'complete']);
        });
    });

foreach (['user', 'User'] as $legacyUserPrefix) {
    Route::prefix($legacyUserPrefix)->group(function () {
        Route::post('/guest_register', [UserCompatibilityController::class, 'guestRegister']);
        Route::post('/send_otp', [UserCompatibilityController::class, 'sendOtp']);
        Route::post('/register', [UserCompatibilityController::class, 'register']);
        Route::post('/login', [UserCompatibilityController::class, 'login']);
        Route::post('/profile', [UserCompatibilityController::class, 'profile']);
        Route::post('/wallet', [UserCompatibilityController::class, 'wallet']);
        Route::post('/randomBoatUsers', [UserCompatibilityController::class, 'randomBoatUsers']);
        Route::post('/game_on_off', [UserCompatibilityController::class, 'gameOnOff']);
        Route::post('/setting', [UserCompatibilityController::class, 'setting']);
        Route::post('/forgot_password', [UserCompatibilityController::class, 'forgotPassword']);
        Route::post('/update_password', [UserCompatibilityController::class, 'updatePassword']);
    });
}

Route::prefix($version)
    ->middleware(['api.version'])
    ->group(function () {
        Route::get('/health', HealthController::class);
        Route::get('/app-config', AppConfigController::class);
        Route::get('/games', [GameController::class, 'index']);
        Route::get('/home', [GameController::class, 'home']);

        Route::prefix('auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/signup', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/logout', [AuthController::class, 'logout'])->middleware('api.auth');
        });

        Route::get('/me', [AuthController::class, 'me'])->middleware('api.auth');
        Route::get('/me/profile', [AuthController::class, 'me'])->middleware('api.auth');

        Route::prefix('tournaments')->group(function () {
            Route::get('/me/entries', [ApiTournamentController::class, 'myEntries'])->middleware('api.auth');
            Route::get('/', [ApiTournamentController::class, 'index']);
            Route::get('/{tournament}', [ApiTournamentController::class, 'show']);
            Route::get('/{tournament}/leaderboard', [ApiTournamentController::class, 'leaderboard']);
            Route::post('/{tournament}/join', [ApiTournamentController::class, 'join'])->middleware('api.auth');
            Route::post('/{tournament}/claim-room', [TournamentRoomController::class, 'claim'])->middleware('api.auth');
        });

        Route::prefix('wallet')->middleware('api.auth')->group(function () {
            Route::get('/', [WalletController::class, 'summary']);
            Route::get('/history', [WalletController::class, 'history']);
        });

        Route::prefix('ludo')->middleware('api.auth')->group(function () {
            Route::post('/queue/join', [LudoController::class, 'joinQueue']);
            Route::get('/rooms/{roomUuid}', [LudoController::class, 'room']);
        });
    });
