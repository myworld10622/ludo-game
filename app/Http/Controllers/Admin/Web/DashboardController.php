<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard.index', [
            'stats' => [
                'games' => Game::query()->count(),
                'visible_games' => Game::query()->where('is_visible', true)->count(),
                'tournaments' => Tournament::query()->count(),
                'users' => User::query()->count(),
                'wallet_transactions' => WalletTransaction::query()->count(),
                'audit_logs' => AuditLog::query()->count(),
            ],
            'recent_audits' => AuditLog::query()->latest()->limit(10)->get(),
        ]);
    }
}
