<?php

use App\Http\Controllers\Api\Internal\V1\TournamentLudoMatchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['internal.api'])->prefix('api/internal/v1/tournaments')->group(function () {
    Route::post('/ludo/rooms/{roomUuid}/complete', [TournamentLudoMatchController::class, 'complete']);
});
