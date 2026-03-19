<?php

use App\Http\Controllers\Admin\TournamentReportController;
use App\Http\Controllers\Admin\TournamentEntryController;
use App\Http\Controllers\Admin\TournamentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin'])->prefix('admin/tournaments')->group(function () {
    Route::get('/', [TournamentController::class, 'index']);
    Route::post('/', [TournamentController::class, 'store']);
    Route::get('/{tournament}', [TournamentController::class, 'show']);
    Route::put('/{tournament}', [TournamentController::class, 'update']);
    Route::post('/{tournament}/publish', [TournamentController::class, 'publish']);
    Route::post('/{tournament}/lock', [TournamentController::class, 'lock']);
    Route::post('/{tournament}/cancel', [TournamentController::class, 'cancel']);
    Route::post('/{tournament}/seed-ludo', [TournamentController::class, 'seedLudo']);
    Route::post('/{tournament}/provision-ludo-rooms', [TournamentController::class, 'provisionLudoRooms']);
    Route::post('/{tournament}/retry-round-lifecycle', [TournamentController::class, 'retryRoundLifecycle']);
    Route::post('/{tournament}/retry-round-seeding', [TournamentController::class, 'retryRoundSeeding']);
    Route::post('/{tournament}/retry-round-provisioning', [TournamentController::class, 'retryRoundProvisioning']);
    Route::post('/{tournament}/settle', [TournamentController::class, 'settle']);
    Route::get('/{tournament}/entries', [TournamentEntryController::class, 'index']);
    Route::get('/{tournament}/leaderboard', [TournamentReportController::class, 'leaderboard']);
    Route::get('/{tournament}/health', [TournamentReportController::class, 'health']);
    Route::get('/{tournament}/matches', [TournamentReportController::class, 'matches']);
    Route::get('/{tournament}/match-links', [TournamentReportController::class, 'matchLinks']);
});
