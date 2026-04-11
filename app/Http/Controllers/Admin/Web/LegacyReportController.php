<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyReportController extends Controller
{
    public function index()
    {
        return view('admin.legacy-reports.index');
    }

    public function purchaseHistory(Request $request)
    {
        [$exists, $query] = $this->baseQuery('tbl_purchase');
        $userId = (string) $request->query('user_id', '');

        if ($exists && $userId !== '') {
            $query->where('user_id', $userId);
        }

        $rows = $exists ? $query->orderByDesc('id')->limit(200)->get() : collect();

        return view('admin.legacy-reports.purchase-history', [
            'rows' => $rows,
            'exists' => $exists,
            'filters' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function depositBonus(Request $request)
    {
        [$exists, $query] = $this->baseQuery('tbl_purcharse_ref');
        $userId = (string) $request->query('user_id', '');
        $type = (string) $request->query('type', '');
        $purchaseUserId = (string) $request->query('purchase_user_id', '');
        $date = (string) $request->query('date', '');

        if ($exists) {
            if ($userId !== '') {
                $query->where('user_id', $userId);
            }
            if ($type !== '') {
                $query->where('type', $type);
            }
            if ($purchaseUserId !== '') {
                $query->where('purchase_user_id', $purchaseUserId);
            }
            if ($date !== '') {
                try {
                    $query->whereDate('added_date', Carbon::parse($date)->toDateString());
                } catch (\Throwable $exception) {
                    // ignore invalid date
                }
            }
        }

        $rows = $exists ? $query->orderByDesc('id')->limit(200)->get() : collect();

        return view('admin.legacy-reports.deposit-bonus', [
            'rows' => $rows,
            'exists' => $exists,
            'filters' => [
                'user_id' => $userId,
                'type' => $type,
                'purchase_user_id' => $purchaseUserId,
                'date' => $date,
            ],
        ]);
    }

    public function betCommission(Request $request)
    {
        $exists = $this->legacyTableExists('tbl_bet_income_log') && $this->legacyTableExists('tbl_users');
        $userId = (string) $request->query('user_id', '');

        $rows = $exists
            ? DB::table('tbl_bet_income_log')
                ->select('tbl_bet_income_log.*', 'tbl_users.name')
                ->join('tbl_users', 'tbl_bet_income_log.bet_user_id', '=', 'tbl_users.id')
                ->when($userId !== '', fn ($q) => $q->where('tbl_bet_income_log.to_user_id', $userId))
                ->orderByDesc('tbl_bet_income_log.id')
                ->limit(200)
                ->get()
            : collect();

        return view('admin.legacy-reports.bet-commission', [
            'rows' => $rows,
            'exists' => $exists,
            'filters' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function rebateHistory(Request $request)
    {
        [$exists, $query] = $this->baseQuery('tbl_rebate_income');
        $userId = (string) $request->query('user_id', '');

        if ($exists && $userId !== '') {
            $query->where('user_id', $userId);
        }

        $rows = $exists ? $query->orderByDesc('id')->limit(200)->get() : collect();

        return view('admin.legacy-reports.rebate-history', [
            'rows' => $rows,
            'exists' => $exists,
            'filters' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function welcomeBonus(Request $request)
    {
        $rewards = $this->legacyTableExists('tbl_welcome_reward')
            ? DB::table('tbl_welcome_reward')->orderBy('id')->get()
            : collect();

        $userId = (string) $request->query('user_id', '');
        $logs = $this->legacyTableExists('tbl_welcome_log')
            ? DB::table('tbl_welcome_log')
                ->when($userId !== '', fn ($q) => $q->where('user_id', $userId))
                ->orderByDesc('id')
                ->limit(200)
                ->get()
            : collect();

        return view('admin.legacy-reports.welcome-bonus', [
            'rewards' => $rewards,
            'logs' => $logs,
            'exists' => $this->legacyTableExists('tbl_welcome_reward'),
            'filters' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function withdrawalLogs(Request $request)
    {
        $exists = $this->legacyTableExists('tbl_withdrawal_log') && $this->legacyTableExists('tbl_users');
        $userId = (string) $request->query('user_id', '');

        $rows = $exists
            ? DB::table('tbl_withdrawal_log')
                ->select(
                    'tbl_withdrawal_log.*',
                    'tbl_users.name as user_name',
                    'tbl_users.mobile as user_mobile'
                )
                ->join('tbl_users', 'tbl_users.id', '=', 'tbl_withdrawal_log.user_id')
                ->when($userId !== '', fn ($q) => $q->where('tbl_withdrawal_log.user_id', $userId))
                ->where('tbl_withdrawal_log.isDeleted', 0)
                ->orderByDesc('tbl_withdrawal_log.id')
                ->limit(200)
                ->get()
            : collect();

        return view('admin.legacy-reports.withdrawal-logs', [
            'rows' => $rows,
            'exists' => $exists,
            'filters' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function redeemList(Request $request)
    {
        [$exists, $query] = $this->baseQuery('tbl_redeem');
        $rows = $exists ? $query->where('isDeleted', 0)->orderByDesc('id')->get() : collect();

        return view('admin.legacy-reports.redeem-list', [
            'rows' => $rows,
            'exists' => $exists,
        ]);
    }

    protected function baseQuery(string $table): array
    {
        $exists = $this->legacyTableExists($table);
        $query = $exists ? DB::table($table) : null;

        return [$exists, $query];
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
