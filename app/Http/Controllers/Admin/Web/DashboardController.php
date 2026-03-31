<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Game;
use App\Models\SupportTicket;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tournamentBase = Tournament::query();
        $recentTournaments = Tournament::query()
            ->with('creator')
            ->withCount([
                'registrations',
                'matches as completed_matches_count' => fn ($q) => $q->where('status', 'completed'),
                'matches as pending_matches_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'disputed', 'forfeited']),
            ])
            ->latest()
            ->limit(8)
            ->get();

        $topUsers = User::query()
            ->with('primaryWallet')
            ->withCount([
                'tournamentRegistrations',
                'tournaments as created_tournaments_count',
            ])
            ->latest('last_login_at')
            ->limit(8)
            ->get();

        $revenue = [
            'wallet_volume' => (float) WalletTransaction::query()->sum('amount'),
            'active_wallet_balance' => (float) Wallet::query()->where('is_active', true)->sum('balance'),
            'tournament_prize_pool' => (float) Tournament::query()->sum('total_prize_pool'),
            'tournament_platform_fee' => (float) Tournament::query()->sum('platform_fee_amount'),
        ];

        $pendingApprovalTournaments = Tournament::query()
            ->with('creator')
            ->where('creator_type', 'user')
            ->where('is_approved', false)
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', [
            'stats' => [
                'games' => Game::query()->count(),
                'visible_games' => Game::query()->where('is_visible', true)->count(),
                'tournaments' => $tournamentBase->count(),
                'users' => User::query()->count(),
                'wallet_transactions' => WalletTransaction::query()->count(),
                'audit_logs' => AuditLog::query()->count(),
                'support_tickets' => SupportTicket::query()->count(),
            ],
            'tournamentStats' => [
                'live' => Tournament::query()->whereIn('status', ['registration_open', 'in_progress'])->count(),
                'pending_approval' => Tournament::query()->where('is_approved', false)->count(),
                'completed' => Tournament::query()->where('status', 'completed')->count(),
                'drafts' => Tournament::query()->where('status', 'draft')->count(),
                'user_created' => Tournament::query()->where('creator_type', 'user')->count(),
                'admin_created' => Tournament::query()->where('creator_type', 'admin')->count(),
            ],
            'revenue' => $revenue,
            'pending_approval_tournaments' => $pendingApprovalTournaments,
            'recent_tournaments' => $recentTournaments,
            'top_users' => $topUsers,
            'recent_audits' => AuditLog::query()->latest()->limit(10)->get(),
        ]);
    }
}
