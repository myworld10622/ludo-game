<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\Web\AuditLogController;
use App\Http\Controllers\Admin\Web\ClassicLudoTableController;
use App\Http\Controllers\Admin\Web\DashboardController;
use App\Http\Controllers\Admin\Web\GameController;
use App\Http\Controllers\Admin\Web\MatchMonitorController;
use App\Http\Controllers\Admin\Web\SupportTicketController;
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
            Route::post('/games/{game}', [GameController::class, 'update'])->name('games.update');
            Route::get('/games/ludo-tables', [ClassicLudoTableController::class, 'index'])->name('games.ludo-tables.index');
            Route::post('/games/ludo-tables', [ClassicLudoTableController::class, 'store'])->name('games.ludo-tables.store');
            Route::put('/games/ludo-tables/{classicLudoTable}', [ClassicLudoTableController::class, 'update'])->name('games.ludo-tables.update');
            Route::delete('/games/ludo-tables/{classicLudoTable}', [ClassicLudoTableController::class, 'destroy'])->name('games.ludo-tables.destroy');
            Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
            Route::get('/tournaments/matches', [MatchMonitorController::class, 'index'])->name('tournaments.matches');
            Route::post('/tournaments/run-scheduler', [TournamentController::class, 'runScheduler'])->name('tournaments.run-scheduler');
            Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
            Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
            Route::get('/tournaments/{tournament}/edit', [TournamentController::class, 'edit'])->name('tournaments.edit');
            Route::get('/tournaments/{tournament}/report', [TournamentController::class, 'report'])->name('tournaments.report');
            Route::get('/tournaments/{tournament}/export', [TournamentController::class, 'export'])->name('tournaments.export');
            Route::get('/tournaments/{tournament}/print', [TournamentController::class, 'print'])->name('tournaments.print');
            Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
            Route::post('/tournaments/{tournament}/approve', [TournamentController::class, 'approve'])->name('tournaments.approve');
            Route::post('/tournaments/{tournament}/reject', [TournamentController::class, 'reject'])->name('tournaments.reject');
            Route::post('/tournaments/{tournament}/force-live', [TournamentController::class, 'forceLive'])->name('tournaments.force-live');
            Route::post('/tournaments/{tournament}/fake-registrations', [TournamentController::class, 'adjustFakeRegistrations'])->name('tournaments.fake-registrations');
            Route::post('/matches/{match}/force-winner', [MatchMonitorController::class, 'forceWinner'])->name('matches.force-winner');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/matches', [UserController::class, 'userMatches'])->name('users.matches');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/panel-permissions', [UserController::class, 'updatePanelPermissions'])->name('users.panel-permissions');
            Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support.index');
            Route::get('/support-tickets/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
            Route::post('/support-tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
            Route::post('/support-tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('support.status');
            Route::get('/wallet-transactions', [WalletTransactionController::class, 'index'])->name('wallet-transactions.index');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        });
    });
});
