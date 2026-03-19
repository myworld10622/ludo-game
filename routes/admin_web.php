<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Web\AuditLogController;
use App\Http\Controllers\Admin\Web\DashboardController;
use App\Http\Controllers\Admin\Web\GameController;
use App\Http\Controllers\Admin\Web\TournamentController;
use App\Http\Controllers\Admin\Web\UserController;
use App\Http\Controllers\Admin\Web\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

        Route::middleware(['admin.auth', 'admin.role:super_admin,admin'])->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('/dashboard', DashboardController::class);
            Route::get('/games', [GameController::class, 'index'])->name('games.index');
            Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
            Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
            Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
            Route::get('/tournaments/{tournament}/edit', [TournamentController::class, 'edit'])->name('tournaments.edit');
            Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/wallet-transactions', [WalletTransactionController::class, 'index'])->name('wallet-transactions.index');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        });
    });
});
