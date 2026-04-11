<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start_date', '');
        $end = $request->query('end_date', '');
        $tabActive = $request->query('tab_active', '1');

        [$exists, $baseQuery] = $this->withdrawalQuery($start, $end);

        $pending = $exists ? (clone $baseQuery)->where('tbl_withdrawal_log.status', 0)->get() : collect();
        $approved = $exists ? (clone $baseQuery)->where('tbl_withdrawal_log.status', 1)->get() : collect();
        $rejected = $exists ? (clone $baseQuery)->where('tbl_withdrawal_log.status', 2)->get() : collect();

        return view('admin.withdrawals.index', [
            'exists' => $exists,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'filters' => [
                'start_date' => $start,
                'end_date' => $end,
                'tab_active' => $tabActive,
            ],
        ]);
    }

    public function changeStatus(Request $request)
    {
        $id = (string) $request->input('id', '');
        $status = (string) $request->input('status', '');

        if ($id === '' || $status === '' || ! $this->legacyTableExists('tbl_withdrawal_log')) {
            return response()->json(['msg' => 'Invalid request', 'class' => 'error']);
        }

        $updated = DB::table('tbl_withdrawal_log')
            ->where('id', $id)
            ->update([
                'status' => (int) $status,
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'msg' => $updated ? 'Status Change Successfully' : 'Something went to wrong',
            'class' => $updated ? 'success' : 'error',
        ]);
    }

    public function redeemIndex()
    {
        $exists = $this->legacyTableExists('tbl_redeem');
        $rows = $exists
            ? DB::table('tbl_redeem')->where('isDeleted', 0)->orderByDesc('id')->get()
            : collect();

        return view('admin.withdrawals.redeem-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function redeemCreate()
    {
        return view('admin.withdrawals.redeem-form', [
            'mode' => 'create',
            'redeem' => null,
        ]);
    }

    public function redeemStore(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'coin' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'img' => ['nullable', 'image', 'max:10240'],
        ]);

        $img = $this->storeRedeemImage($request);

        DB::table('tbl_redeem')->insert([
            'title' => $request->input('title'),
            'coin' => $request->input('coin'),
            'amount' => $request->input('amount'),
            'img' => $img ?? '',
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.withdrawals.redeem.index')->with('status', 'Redeem Added Successfully');
    }

    public function redeemEdit(string $id)
    {
        $redeem = $this->legacyTableExists('tbl_redeem')
            ? DB::table('tbl_redeem')->where('id', $id)->first()
            : null;

        return view('admin.withdrawals.redeem-form', [
            'mode' => 'edit',
            'redeem' => $redeem,
        ]);
    }

    public function redeemUpdate(Request $request, string $id)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'coin' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'img' => ['nullable', 'image', 'max:10240'],
        ]);

        $payload = [
            'title' => $request->input('title'),
            'coin' => $request->input('coin'),
            'amount' => $request->input('amount'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ];

        $img = $this->storeRedeemImage($request);
        if ($img) {
            $payload['img'] = $img;
        }

        DB::table('tbl_redeem')->where('id', $id)->update($payload);

        return redirect()->route('admin.withdrawals.redeem.index')->with('status', 'Redeem Updated Successfully');
    }

    public function redeemDelete(string $id)
    {
        if ($this->legacyTableExists('tbl_redeem')) {
            DB::table('tbl_redeem')->where('id', $id)->update(['isDeleted' => 1]);
        }

        return redirect()->route('admin.withdrawals.redeem.index')->with('status', 'Redeem Removed Successfully');
    }

    protected function withdrawalQuery(string $start, string $end): array
    {
        $exists = $this->legacyTableExists('tbl_withdrawal_log') && $this->legacyTableExists('tbl_users');

        if (! $exists) {
            return [$exists, null];
        }

        $query = DB::table('tbl_withdrawal_log')
            ->select('tbl_withdrawal_log.*', 'tbl_users.name as user_name', 'tbl_users.mobile as user_mobile')
            ->join('tbl_users', 'tbl_users.id', '=', 'tbl_withdrawal_log.user_id')
            ->where('tbl_withdrawal_log.isDeleted', 0);

        if ($start !== '' && $end !== '') {
            try {
                $from = Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s');
                $to = Carbon::parse($end)->endOfDay()->format('Y-m-d H:i:s');
                $query->whereBetween('tbl_withdrawal_log.created_date', [$from, $to]);
            } catch (\Throwable $exception) {
                // ignore invalid dates
            }
        }

        return [$exists, $query->orderByDesc('tbl_withdrawal_log.id')];
    }

    protected function legacyTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    protected function storeRedeemImage(Request $request): ?string
    {
        if (! $request->hasFile('img')) {
            return null;
        }

        $file = $request->file('img');
        if (! $file || ! $file->isValid()) {
            return null;
        }

        $dir = public_path('data/Redeem');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $name = Str::lower(Str::random(12)).'.'.$file->getClientOriginalExtension();
        $file->move($dir, $name);

        return $name;
    }
}
