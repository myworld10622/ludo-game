<?php

use App\Http\Controllers\Web\UserPanelAuthController;
use App\Http\Controllers\Web\UserPanelMatchController;
use App\Http\Controllers\Web\UserPanelSupportController;
use App\Http\Controllers\Web\UserPanelTournamentController;
use Illuminate\Support\Facades\Route;

// Public API: homepage tournament cards
Route::get('/api/homepage-cards', function () {
    return response()->json(
        \App\Models\HomepageTournamentCard::visible()->get([
            'id', 'name', 'icon', 'description',
            'card_color', 'status_badge', 'status_text',
            'meta1_label', 'meta1_value',
            'meta2_label', 'meta2_value',
            'meta3_label', 'meta3_value',
        ])
    );
});

Route::get('/', function () {
    return view('ludo.landing', [
        'apkUrl' => env('LUDO_APK_URL', 'https://ludo.betzono.com/ludo.apk'),
        'playUrl' => route('ludo.play'),
    ]);
});

Route::get('/tournament-guide', fn() => response()->file(base_path('docs/user-tournament-guide.html')))->name('tournament.guide');

Route::get('/terms', fn() => view('ludo.terms'))->name('terms');
Route::get('/privacy', fn() => view('ludo.privacy'))->name('privacy');
Route::get('/fair-play', fn() => view('ludo.fair-play'))->name('fair-play');
Route::get('/responsible-gaming', fn() => view('ludo.responsible-gaming'))->name('responsible-gaming');

Route::get('/ludo', function () {
    return view('ludo.play', [
        'landingUrl' => url('/'),
        'buildBaseUrl' => asset('ludo-webgl/Build'),
    ]);
})->name('ludo.play');

Route::middleware('guest')->group(function () {
    Route::get('/login', [UserPanelAuthController::class, 'showLogin'])->name('login');
    Route::get('/panel/login', [UserPanelAuthController::class, 'showLogin'])->name('user.login');
    Route::post('/login', [UserPanelAuthController::class, 'login'])->name('user.login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/panel', [UserPanelAuthController::class, 'panel'])->name('panel.index');
    Route::get('/panel/tournaments', [UserPanelTournamentController::class, 'index'])->name('panel.tournaments.index');
    Route::post('/panel/tournaments', [UserPanelTournamentController::class, 'store'])->name('panel.tournaments.store');
    Route::get('/panel/tournaments/{tournament}/edit', [UserPanelTournamentController::class, 'edit'])->name('panel.tournaments.edit');
    Route::get('/panel/tournaments/{tournament}/report', [UserPanelTournamentController::class, 'report'])->name('panel.tournaments.report');
    Route::get('/panel/tournaments/{tournament}/export', [UserPanelTournamentController::class, 'export'])->name('panel.tournaments.export');
    Route::get('/panel/tournaments/{tournament}/print', [UserPanelTournamentController::class, 'print'])->name('panel.tournaments.print');
    Route::put('/panel/tournaments/{tournament}', [UserPanelTournamentController::class, 'update'])->name('panel.tournaments.update');
    Route::post('/panel/tournaments/{tournament}/approve', [UserPanelTournamentController::class, 'approve'])->name('panel.tournaments.approve');
    Route::post('/panel/tournaments/{tournament}/force-live', [UserPanelTournamentController::class, 'forceLive'])->name('panel.tournaments.force-live');
    Route::post('/panel/tournaments/{tournament}/fake-registrations', [UserPanelTournamentController::class, 'adjustFakeRegistrations'])->name('panel.tournaments.fake-registrations');
    Route::get('/panel/matches', [UserPanelMatchController::class, 'index'])->name('panel.matches.index');
    Route::post('/panel/matches/{match}/force-winner', [UserPanelMatchController::class, 'forceWinner'])->name('panel.matches.force-winner');
    Route::get('/panel/support', [UserPanelSupportController::class, 'index'])->name('panel.support.index');
    Route::post('/panel/support', [UserPanelSupportController::class, 'store'])->name('panel.support.store');
    Route::get('/panel/support/{ticket}', [UserPanelSupportController::class, 'show'])->name('panel.support.show');
    Route::post('/panel/support/{ticket}/reply', [UserPanelSupportController::class, 'reply'])->name('panel.support.reply');
    Route::post('/logout', [UserPanelAuthController::class, 'logout'])->name('panel.logout');
});
