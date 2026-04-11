<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgentController extends Controller
{
    public function index()
    {
        $exists = $this->legacyTableExists('tbl_admin');
        $agents = $exists
            ? DB::table('tbl_admin as a')
                ->leftJoin('tbl_admin as d', 'a.addedby', '=', 'd.id')
                ->where('a.isDeleted', 0)
                ->where('a.role', 2)
                ->select('a.*', 'd.first_name as distributor_fname', 'd.last_name as distributor_lname')
                ->orderByDesc('a.id')
                ->get()
            : collect();

        return view('admin.agents.index', [
            'exists' => $exists,
            'agents' => $agents,
        ]);
    }

    public function create()
    {
        return view('admin.agents.form', [
            'mode' => 'create',
            'agent' => null,
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
            'addedby' => ['nullable', 'integer'],
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
                'role' => 2,
                'addedby' => $request->input('addedby', 0),
                'created_date' => now()->format('Y-m-d H:i:s'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
                'isDeleted' => 0,
            ]);
        }

        return redirect()->route('admin.agents.index')->with('status', 'Agent Added Successfully');
    }

    public function edit(string $id)
    {
        $agent = $this->legacyTableExists('tbl_admin')
            ? DB::table('tbl_admin')->where('id', $id)->first()
            : null;

        if (! $agent) {
            return redirect()->route('admin.agents.index')->with('status', 'Agent Not Found');
        }

        return view('admin.agents.form', [
            'mode' => 'edit',
            'agent' => $agent,
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

        return redirect()->route('admin.agents.index')->with('status', 'Agent Updated Successfully');
    }

    public function users(string $id)
    {
        $exists = $this->legacyTableExists('tbl_users');
        $users = $exists
            ? DB::table('tbl_users')
                ->where('created_by', $id)
                ->where('isDeleted', 0)
                ->orderBy('id')
                ->get()
            : collect();

        return view('admin.agents.users', [
            'exists' => $exists,
            'users' => $users,
        ]);
    }

    public function walletForm(string $id, string $type)
    {
        $agent = $this->legacyTableExists('tbl_admin')
            ? DB::table('tbl_admin')->where('id', $id)->first()
            : null;

        if (! $agent) {
            return redirect()->route('admin.agents.index')->with('status', 'Agent Not Found');
        }

        return view('admin.agents.wallet', [
            'agent' => $agent,
            'type' => $type,
        ]);
    }

    public function updateWallet(Request $request, string $id)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:add,deduct'],
            'source_distributor_id' => ['nullable', 'integer'],
        ]);

        $amount = (float) $request->input('amount');
        $type = $request->input('type');
        $sourceDistributorId = (int) $request->input('source_distributor_id', 0);

        if ($type === 'add' && $sourceDistributorId > 0) {
            $balance = DB::table('tbl_admin')->where('id', $sourceDistributorId)->value('wallet');
            if ($balance !== null && (float) $balance < $amount) {
                return back()->withErrors(['amount' => 'Distributor balance is insufficient'])->withInput();
            }
        }

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
            'added_by' => $sourceDistributorId ?: 0,
        ]);

        if ($type === 'add' && $sourceDistributorId > 0) {
            DB::table('tbl_admin')
                ->where('id', $sourceDistributorId)
                ->update([
                    'wallet' => DB::raw('wallet - '.$amount),
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]);
        }

        return redirect()->route('admin.agents.index')->with('status', 'Agent Wallet Updated Successfully');
    }

    public function walletLogs(string $id)
    {
        $logs = $this->legacyTableExists('tbl_agentwallet_log')
            ? DB::table('tbl_agentwallet_log')->where('user_id', $id)->orderByDesc('id')->get()
            : collect();

        return view('admin.agents.wallet-logs', [
            'logs' => $logs,
        ]);
    }

    public function paymentMethods(string $id)
    {
        $methods = $this->legacyTableExists('tbl_payment_method')
            ? DB::table('tbl_payment_method')->where('user_id', $id)->orderByDesc('created_at')->get()
            : collect();

        return view('admin.agents.payment-methods', [
            'methods' => $methods,
            'userId' => $id,
        ]);
    }

    public function paymentMethodCreate(string $id)
    {
        return view('admin.agents.payment-method-form', [
            'userId' => $id,
        ]);
    }

    public function paymentMethodStore(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $filename = null;
        if ($request->hasFile('image')) {
            $filename = $this->storePaymentMethodImage($request->file('image'));
        }

        DB::table('tbl_payment_method')->insert([
            'user_id' => $id,
            'name' => $request->input('name'),
            'image' => $filename,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.agents.payment-methods', $id)->with('status', 'Payment Method Added Successfully');
    }

    public function paymentMethodDelete(string $id, string $methodId)
    {
        DB::table('tbl_payment_method')->where('id', $methodId)->delete();

        return redirect()->route('admin.agents.payment-methods', $id)->with('status', 'Payment Method Deleted Successfully');
    }

    protected function storePaymentMethodImage($file): string
    {
        $dir = public_path('data/PaymentMethods');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = 'pm_'.time().'_'.mt_rand(1000, 9999).'.'.$extension;
        $file->move($dir, $filename);

        return $filename;
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
