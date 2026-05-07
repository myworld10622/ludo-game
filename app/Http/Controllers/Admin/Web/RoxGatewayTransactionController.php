<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RoxGatewayTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $exists = Schema::hasTable('rox_gateway_transactions');
        $filters = [
            'status' => (string) $request->query('status', ''),
            'trx' => (string) $request->query('trx', ''),
            'user_id' => (string) $request->query('user_id', ''),
            'tra_id' => (string) $request->query('tra_id', ''),
            'utr_id' => (string) $request->query('utr_id', ''),
        ];

        $rows = collect();
        if ($exists) {
            $query = DB::table('rox_gateway_transactions as rgt')
                ->leftJoin('users', 'users.id', '=', 'rgt.user_id')
                ->select(
                    'rgt.*',
                    'users.username as app_username',
                    'users.mobile as app_mobile',
                    'users.email as app_email'
                );

            if ($filters['status'] !== '') {
                $query->where('rgt.status', $filters['status']);
            }
            if ($filters['trx'] !== '') {
                $query->where('rgt.trx', 'like', '%'.$filters['trx'].'%');
            }
            if ($filters['user_id'] !== '') {
                $query->where('rgt.user_id', $filters['user_id']);
            }
            if ($filters['tra_id'] !== '' && Schema::hasColumn('rox_gateway_transactions', 'tra_id')) {
                $query->where('rgt.tra_id', 'like', '%'.$filters['tra_id'].'%');
            }
            if ($filters['utr_id'] !== '' && Schema::hasColumn('rox_gateway_transactions', 'utr_id')) {
                $query->where('rgt.utr_id', 'like', '%'.$filters['utr_id'].'%');
            }

            $rows = $query->orderByDesc('rgt.id')->limit(200)->get();
        }

        return view('admin.gateway-transactions.index', [
            'exists' => $exists,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function updateStatus(Request $request, WalletService $walletService): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', 'in:success,rejected,pending'],
            'tra_id' => ['nullable', 'string', 'max:255'],
            'utr_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(Schema::hasTable('rox_gateway_transactions'), 404);

        $row = DB::table('rox_gateway_transactions')->where('id', $validated['id'])->first();
        abort_unless($row, 404);

        if ($validated['status'] === 'rejected' && ! empty($row->wallet_transaction_id)) {
            return back()->withErrors(['status' => 'Approved transaction cannot be manually rejected.']);
        }

        $user = ! empty($row->user_id) ? User::query()->find($row->user_id) : null;
        if ($validated['status'] === 'success' && ! $user) {
            return back()->withErrors(['status' => 'Linked app user not found for manual approval.']);
        }

        DB::transaction(function () use ($validated, $row, $user, $walletService) {
            $walletTransactionId = $row->wallet_transaction_id;

            if ($validated['status'] === 'success' && empty($walletTransactionId)) {
                $walletTx = $walletService->credit(
                    user: $user,
                    amount: (float) $row->amount,
                    referenceType: WalletTransaction::class,
                    referenceId: $row->id,
                    description: 'Manual deposit approval via Rox admin',
                    currency: (string) $row->currency,
                    meta: [
                        'gateway' => 'betzono',
                        'trx' => $row->trx,
                        'manual_approval' => true,
                    ],
                );

                $walletTransactionId = $walletTx->id;
            }

            $update = [
                'status' => $validated['status'],
                'gateway_status' => 'manual_'.$validated['status'],
                'updated_at' => now(),
            ];

            if ($walletTransactionId) {
                $update['wallet_transaction_id'] = $walletTransactionId;
            }
            if (Schema::hasColumn('rox_gateway_transactions', 'manual_status_by')) {
                $update['manual_status_by'] = auth('admin')->id();
            }
            if (Schema::hasColumn('rox_gateway_transactions', 'manual_status_note')) {
                $update['manual_status_note'] = $validated['note'] ?: null;
            }
            if (! empty($validated['tra_id']) && Schema::hasColumn('rox_gateway_transactions', 'tra_id')) {
                $update['tra_id'] = $validated['tra_id'];
            }
            if (! empty($validated['utr_id']) && Schema::hasColumn('rox_gateway_transactions', 'utr_id')) {
                $update['utr_id'] = $validated['utr_id'];
            }

            DB::table('rox_gateway_transactions')->where('id', $row->id)->update($update);

            $this->syncLegacyPurchaseStatus(
                trx: (string) $row->trx,
                status: $validated['status'],
                traId: (string) ($validated['tra_id'] ?? ''),
                utrId: (string) ($validated['utr_id'] ?? '')
            );

            if ($validated['status'] === 'success' && $user) {
                $this->syncLegacyUserBalance($user, (float) $row->amount, $row->wallet_transaction_id, $walletTransactionId);
            }
        });

        return redirect()
            ->route('admin.gateway-transactions.index', $request->query())
            ->with('status', 'Gateway transaction updated successfully.');
    }

    private function syncLegacyPurchaseStatus(string $trx, string $status, string $traId, string $utrId): void
    {
        if (! Schema::hasTable('tbl_purchase')) {
            return;
        }

        $mapped = $status === 'success' ? 1 : ($status === 'rejected' ? 2 : 0);
        $update = [
            'status' => $mapped,
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ];

        if ($traId !== '') {
            $update['razor_payment_id'] = $traId;
        }
        if ($utrId !== '') {
            $update['utr'] = $utrId;
        }

        DB::table('tbl_purchase')->where('transaction_id', $trx)->update($update);
    }

    private function syncLegacyUserBalance(User $user, float $amount, mixed $beforeWalletTransactionId, mixed $afterWalletTransactionId): void
    {
        if ($beforeWalletTransactionId || ! $afterWalletTransactionId || ! Schema::hasTable('tbl_users')) {
            return;
        }

        $legacy = DB::table('tbl_users')
            ->where('mobile', $user->mobile)
            ->orWhere('email', $user->email)
            ->orderByDesc('id')
            ->first();

        if (! $legacy) {
            return;
        }

        DB::table('tbl_users')
            ->where('id', $legacy->id)
            ->update([
                'wallet' => DB::raw('wallet + '.$amount),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);
    }
}
