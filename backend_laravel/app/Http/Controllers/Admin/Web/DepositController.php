<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepositController extends Controller
{
    public function index()
    {
        $exists = $this->legacyTableExists('tbl_distributor_to_admin_request')
            && $this->legacyTableExists('tbl_gateway')
            && $this->legacyTableExists('tbl_admin');

        $baseQuery = $exists
            ? DB::table('tbl_distributor_to_admin_request')
                ->select('tbl_distributor_to_admin_request.*', 'tbl_gateway.name as gateway_name', 'tbl_admin.first_name as distributor')
                ->join('tbl_gateway', 'tbl_gateway.id', '=', 'tbl_distributor_to_admin_request.gateway_id')
                ->join('tbl_admin', 'tbl_admin.id', '=', 'tbl_distributor_to_admin_request.distributor_id')
                ->where('tbl_distributor_to_admin_request.isDeleted', 0)
                ->where('tbl_gateway.isDeleted', 0)
                ->where('tbl_admin.isDeleted', 0)
            : null;

        $pending = $exists ? (clone $baseQuery)->where('tbl_distributor_to_admin_request.status', 0)->orderByDesc('id')->get() : collect();
        $approved = $exists ? (clone $baseQuery)->where('tbl_distributor_to_admin_request.status', 1)->orderByDesc('id')->get() : collect();
        $rejected = $exists ? (clone $baseQuery)->where('tbl_distributor_to_admin_request.status', 2)->orderByDesc('id')->get() : collect();

        return view('admin.deposits.index', [
            'exists' => $exists,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ]);
    }

    public function changeStatus(Request $request)
    {
        $id = (string) $request->input('id', '');
        $status = (string) $request->input('status', '');

        if ($id === '' || $status === '' || ! $this->legacyTableExists('tbl_distributor_to_admin_request')) {
            return response()->json(['msg' => 'Invalid request', 'class' => 'error']);
        }

        DB::table('tbl_distributor_to_admin_request')
            ->where('id', $id)
            ->update([
                'status' => (int) $status,
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        if ((int) $status === 1 && $this->legacyTableExists('tbl_admin')) {
            $requestRow = DB::table('tbl_distributor_to_admin_request')
                ->where('id', $id)
                ->where('isDeleted', 0)
                ->first();

            if ($requestRow) {
                DB::table('tbl_admin')
                    ->where('id', $requestRow->distributor_id)
                    ->update([
                        'wallet' => DB::raw('wallet + '.$requestRow->amount),
                        'updated_date' => now()->format('Y-m-d H:i:s'),
                    ]);
            }
        }

        return response()->json(['msg' => 'Status Change Successfully', 'class' => 'success']);
    }

    public function bonusIndex()
    {
        $exists = $this->legacyTableExists('tbl_deposit_bonus_master');
        $rows = $exists
            ? DB::table('tbl_deposit_bonus_master')->where('isDeleted', 0)->orderByDesc('id')->get()
            : collect();

        return view('admin.deposits.bonus-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function bonusCreate()
    {
        return view('admin.deposits.bonus-form', [
            'mode' => 'create',
            'bonus' => null,
        ]);
    }

    public function bonusStore(Request $request)
    {
        $request->validate([
            'min' => ['required', 'numeric', 'min:0'],
            'max' => ['required', 'numeric', 'min:0'],
            'self_bonus' => ['required', 'numeric', 'min:0'],
            'upline_bonus' => ['required', 'numeric', 'min:0'],
            'deposit_count' => ['required', 'integer', 'min:0'],
        ]);

        DB::table('tbl_deposit_bonus_master')->insert([
            'min' => $request->input('min'),
            'max' => $request->input('max'),
            'self_bonus' => $request->input('self_bonus'),
            'upline_bonus' => $request->input('upline_bonus'),
            'deposit_count' => $request->input('deposit_count'),
            'added_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
            'isDeleted' => 0,
        ]);

        return redirect()->route('admin.deposits.bonus.index')->with('status', 'Deposit Bonus Added Successfully');
    }

    public function bonusEdit(string $id)
    {
        $bonus = $this->legacyTableExists('tbl_deposit_bonus_master')
            ? DB::table('tbl_deposit_bonus_master')->where('id', $id)->first()
            : null;

        return view('admin.deposits.bonus-form', [
            'mode' => 'edit',
            'bonus' => $bonus,
        ]);
    }

    public function bonusUpdate(Request $request, string $id)
    {
        $request->validate([
            'min' => ['required', 'numeric', 'min:0'],
            'max' => ['required', 'numeric', 'min:0'],
            'self_bonus' => ['required', 'numeric', 'min:0'],
            'upline_bonus' => ['required', 'numeric', 'min:0'],
            'deposit_count' => ['required', 'integer', 'min:0'],
        ]);

        DB::table('tbl_deposit_bonus_master')
            ->where('id', $id)
            ->update([
                'min' => $request->input('min'),
                'max' => $request->input('max'),
                'self_bonus' => $request->input('self_bonus'),
                'upline_bonus' => $request->input('upline_bonus'),
                'deposit_count' => $request->input('deposit_count'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.deposits.bonus.index')->with('status', 'Deposit Bonus Updated Successfully');
    }

    public function bonusDelete(string $id)
    {
        if ($this->legacyTableExists('tbl_deposit_bonus_master')) {
            DB::table('tbl_deposit_bonus_master')->where('id', $id)->update(['isDeleted' => 1]);
        }

        return redirect()->route('admin.deposits.bonus.index')->with('status', 'Deposit Bonus Deleted Successfully');
    }

    public function percentageIndex()
    {
        $exists = $this->legacyTableExists('tbl_deposit_percentage_master');
        $rows = $exists
            ? DB::table('tbl_deposit_percentage_master')->where('isDeleted', 0)->orderByDesc('id')->get()
            : collect();

        return view('admin.deposits.percentage-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function percentageCreate()
    {
        return view('admin.deposits.percentage-form', [
            'mode' => 'create',
            'percent' => null,
        ]);
    }

    public function percentageStore(Request $request)
    {
        $request->validate([
            'user_type' => ['required', 'in:0,2,3'],
            'percentage' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('tbl_deposit_percentage_master')->insert([
            'user_type' => $request->input('user_type'),
            'percentage' => $request->input('percentage'),
            'added_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
            'isDeleted' => 0,
        ]);

        return redirect()->route('admin.deposits.percentage.index')->with('status', 'Deposit Percentage Added Successfully');
    }

    public function percentageEdit(string $id)
    {
        $percent = $this->legacyTableExists('tbl_deposit_percentage_master')
            ? DB::table('tbl_deposit_percentage_master')->where('id', $id)->first()
            : null;

        return view('admin.deposits.percentage-form', [
            'mode' => 'edit',
            'percent' => $percent,
        ]);
    }

    public function percentageUpdate(Request $request, string $id)
    {
        $request->validate([
            'user_type' => ['required', 'in:0,2,3'],
            'percentage' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('tbl_deposit_percentage_master')
            ->where('id', $id)
            ->update([
                'user_type' => $request->input('user_type'),
                'percentage' => $request->input('percentage'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.deposits.percentage.index')->with('status', 'Deposit Percentage Updated Successfully');
    }

    public function percentageDelete(string $id)
    {
        if ($this->legacyTableExists('tbl_deposit_percentage_master')) {
            DB::table('tbl_deposit_percentage_master')->where('id', $id)->update(['isDeleted' => 1]);
        }

        return redirect()->route('admin.deposits.percentage.index')->with('status', 'Deposit Percentage Deleted Successfully');
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
