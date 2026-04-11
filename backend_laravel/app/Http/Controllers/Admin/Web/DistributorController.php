<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributorController extends Controller
{
    public function index()
    {
        $exists = $this->legacyTableExists('tbl_admin');
        $distributors = $exists
            ? DB::table('tbl_admin')
                ->where('isDeleted', 0)
                ->where('role', 3)
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('admin.distributors.index', [
            'exists' => $exists,
            'distributors' => $distributors,
        ]);
    }

    public function create()
    {
        return view('admin.distributors.form', [
            'mode' => 'create',
            'distributor' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email_id' => ['required', 'email', 'max:190'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:190'],
        ]);

        if ($this->legacyTableExists('tbl_admin')) {
            $exists = DB::table('tbl_admin')
                ->where('email_id', $request->input('email_id'))
                ->where('isDeleted', 0)
                ->exists();

            if ($exists) {
                return back()->withErrors(['email_id' => 'Email ID already exists'])->withInput();
            }

            DB::table('tbl_admin')->insert([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email_id' => $request->input('email_id'),
                'password' => $request->input('password'),
                'sw_password' => md5($request->input('password')),
                'mobile' => $request->input('mobile'),
                'role' => 3,
                'created_date' => now()->format('Y-m-d H:i:s'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
                'isDeleted' => 0,
            ]);
        }

        return redirect()->route('admin.distributors.index')->with('status', 'Distributor Added Successfully');
    }

    public function edit(string $id)
    {
        $distributor = $this->legacyTableExists('tbl_admin')
            ? DB::table('tbl_admin')->where('id', $id)->first()
            : null;

        if (! $distributor) {
            return redirect()->route('admin.distributors.index')->with('status', 'Distributor Not Found');
        }

        return view('admin.distributors.form', [
            'mode' => 'edit',
            'distributor' => $distributor,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email_id' => ['required', 'email', 'max:190'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'max:190'],
        ]);

        $payload = [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email_id' => $request->input('email_id'),
            'mobile' => $request->input('mobile'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->input('password');
            $payload['sw_password'] = md5($request->input('password'));
        }

        DB::table('tbl_admin')->where('id', $id)->update($payload);

        return redirect()->route('admin.distributors.index')->with('status', 'Distributor Updated Successfully');
    }

    public function users(string $id)
    {
        $exists = $this->legacyTableExists('tbl_admin');
        $agents = $exists
            ? DB::table('tbl_admin')
                ->where('addedby', $id)
                ->where('isDeleted', 0)
                ->where('role', 2)
                ->orderBy('id')
                ->get()
            : collect();

        return view('admin.distributors.users', [
            'exists' => $exists,
            'agents' => $agents,
        ]);
    }

    public function walletForm(string $id, string $type)
    {
        $distributor = $this->legacyTableExists('tbl_admin')
            ? DB::table('tbl_admin')->where('id', $id)->first()
            : null;

        if (! $distributor) {
            return redirect()->route('admin.distributors.index')->with('status', 'Distributor Not Found');
        }

        return view('admin.distributors.wallet', [
            'distributor' => $distributor,
            'type' => $type,
        ]);
    }

    public function updateWallet(Request $request, string $id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:add,deduct'],
        ]);

        $amount = (float) $request->input('amount');
        $type = $request->input('type');
        $delta = $type === 'add' ? $amount : -$amount;

        DB::table('tbl_admin')
            ->where('id', $id)
            ->update([
                'wallet' => DB::raw('wallet + '.$delta),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        DB::table('tbl_agentwallet_log')->insert([
            'user_id' => $id,
            'coin' => $delta,
        ]);

        return redirect()->route('admin.distributors.index')->with('status', 'Distributor Wallet Updated Successfully');
    }

    public function walletLogs(string $id)
    {
        $logs = $this->legacyTableExists('tbl_agentwallet_log')
            ? DB::table('tbl_agentwallet_log')->where('user_id', $id)->orderByDesc('id')->get()
            : collect();

        return view('admin.distributors.wallet-logs', [
            'logs' => $logs,
        ]);
    }

    public function paymentHistory(string $id)
    {
        $exists = $this->legacyTableExists('tbl_agentwallet_log');
        $distributorLogs = $exists
            ? DB::table('tbl_agentwallet_log')->where('user_id', $id)->orderByDesc('id')->get()
            : collect();

        $agentLogs = $exists && $this->legacyTableExists('tbl_admin')
            ? DB::table('tbl_admin')
                ->join('tbl_agentwallet_log', 'tbl_agentwallet_log.user_id', '=', 'tbl_admin.id')
                ->where('tbl_admin.addedby', $id)
                ->orderByDesc('tbl_agentwallet_log.id')
                ->select('tbl_agentwallet_log.*', 'tbl_admin.first_name as Username', 'tbl_admin.last_name as UserLastName')
                ->get()
            : collect();

        return view('admin.distributors.payment-history', [
            'distributorLogs' => $distributorLogs,
            'agentLogs' => $agentLogs,
        ]);
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
