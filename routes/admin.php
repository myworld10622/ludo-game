<?php

use App\Http\Controllers\Admin\V1\AdminHealthController;
use App\Http\Controllers\Admin\V1\GameController as AdminGameController;
use App\Http\Controllers\Admin\V1\TournamentController as AdminTournamentController;
use Illuminate\Support\Facades\Route;

$version = config('platform.api.default_version', 'v1');
$adminPrefix = trim(config('platform.api.admin_prefix', 'admin/api'), '/');

Route::prefix($adminPrefix.'/'.$version)
    ->middleware(['api.version', 'admin.auth'])
    ->group(function () {
        Route::get('/health', AdminHealthController::class);
        Route::get('/dashboard', fn () => response()->json(['message' => 'Admin dashboard placeholder']));
        Route::get('/users', fn () => response()->json(['message' => 'Admin users placeholder']));
        Route::get('/games', [AdminGameController::class, 'index']);
        Route::post('/games', [AdminGameController::class, 'store']);
        Route::put('/games/{game}', [AdminGameController::class, 'update']);
        Route::patch('/games/{game}', [AdminGameController::class, 'update']);
        Route::get('/tournaments', [AdminTournamentController::class, 'index']);
        Route::post('/tournaments', [AdminTournamentController::class, 'store']);
        Route::put('/tournaments/{tournament}', [AdminTournamentController::class, 'update']);
        Route::patch('/tournaments/{tournament}', [AdminTournamentController::class, 'update']);
    });
require_once __DIR__.'/admin_tournaments.php';
