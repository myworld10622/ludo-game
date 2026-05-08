<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManualDepositController extends Controller
{
    public function __construct(private WalletService $wallet) {}

    public function index(Request $request)
    {
        $tab    = $request->query('tab', 'pending');
        $search = trim((string) $request->query('search', ''));

        if (! Schema::hasTable('tbl_purchase')) {
            return view('admin.manual-deposits.index', [
                'pending'  => collect(),
                'approved' => collect(),
                'rejected' => collect(),
                'tab'      => $tab,
                'search'   => $search,
                'missing'  => true,
            ]);
        }

        $base = DB::table('tbl_purchase')
            ->leftJoin('tbl_users', 'tbl_users.id', '=', 'tbl_purchase.user_id')
            ->select(
                'tbl_purchase.*',
                'tbl_users.name   as user_name',
                'tbl_users.mobile as user_mobile',
                'tbl_users.email  as user_email',
            )
            ->where('tbl_purchase.isDeleted', 0)
            ->orderByDesc('tbl_purchase.id');

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('tbl_users.username', 'like', "%{$search}%")
                  ->orWhere('tbl_users.mobile',  'like', "%{$search}%")
                  ->orWhere('tbl_purchase.utr',   'like', "%{$search}%");
            });
        }

        $pending  = (clone $base)->where('tbl_purchase.status', 0)->get();
        $approved = (clone $base)->where('tbl_purchase.status', 1)->get();
        $rejected = (clone $base)->where('tbl_purchase.status', 2)->get();

        return view('admin.manual-deposits.index', compact('pending', 'approved', 'rejected', 'tab', 'search'));
    }

    public function changeStatus(Request $request)
    {
        $id     = (int) $request->input('id', 0);
        $status = (int) $request->input('status', -1);

        if (! $id || ! in_array($status, [0, 1, 2])) {
            return response()->json(['success' => false, 'message' => 'Invalid request.']);
        }

        if (! Schema::hasTable('tbl_purchase')) {
            return response()->json(['success' => false, 'message' => 'Table not found.']);
        }

        $row = DB::table('tbl_purchase')->where('id', $id)->first();
        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Deposit not found.']);
        }

        $current = (int) ($row->status ?? 0);

        // If approving (status=1) and not already approved → credit wallet
        if ($status === 1 && $current !== 1) {
            // Try to find Laravel user via legacy tbl_users lookup first
            $legacyUser = Schema::hasTable('tbl_users')
                ? DB::table('tbl_users')->where('id', $row->user_id)->first()
                : null;

            $laravelUser = null;
            if ($legacyUser) {
                $laravelUser = ($legacyUser->mobile
                    ? \App\Models\User::where('mobile', $legacyUser->mobile)->first()
                    : null)
                    ?? \App\Models\User::find($legacyUser->id);
            }
            // Fallback: user_id may be the Laravel users.id directly
            if (! $laravelUser) {
                $laravelUser = \App\Models\User::find($row->user_id);
            }

            if (! $laravelUser) {
                return response()->json(['success' => false, 'message' => 'Laravel user not found.']);
            }

            try {
                $this->wallet->credit(
                    user: $laravelUser,
                    amount: (float) ($row->coin ?? $row->price ?? 0),
                    referenceType: 'manual_deposit',
                    referenceId: $id,
                    description: 'Manual deposit approved (UTR: ' . ($row->utr ?? '-') . ')',
                );
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Wallet credit failed: ' . $e->getMessage()]);
            }
        }

        DB::table('tbl_purchase')
            ->where('id', $id)
            ->update(['status' => $status, 'updated_date' => now()]);

        $label = match ($status) { 1 => 'Approved', 2 => 'Rejected', default => 'Pending' };

        return response()->json(['success' => true, 'message' => "Deposit {$label} successfully."]);
    }
}
