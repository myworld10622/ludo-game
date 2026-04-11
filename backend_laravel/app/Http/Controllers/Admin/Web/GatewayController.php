<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GatewayController extends Controller
{
    public function manualIndex()
    {
        $exists = $this->legacyTableExists('tbl_gateway');
        $gateways = $exists
            ? DB::table('tbl_gateway')->where('isDeleted', 0)->orderByDesc('id')->get()
            : collect();

        return view('admin.gateways.manual-index', [
            'exists' => $exists,
            'gateways' => $gateways,
        ]);
    }

    public function manualCreate()
    {
        return view('admin.gateways.manual-form', [
            'mode' => 'create',
            'gateway' => null,
        ]);
    }

    public function manualStore(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'role' => ['required', 'array'],
            'role.*' => ['string'],
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0'],
            'fixed_charge' => ['required', 'numeric', 'min:0'],
            'percent_charge' => ['required', 'numeric', 'min:0'],
            'instructions' => ['required', 'string'],
        ]);

        DB::table('tbl_gateway')->insert([
            'name' => $request->input('name'),
            'role' => implode(',', $request->input('role', [])),
            'currency' => $request->input('currency'),
            'rate' => $request->input('rate'),
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
            'fixed_charge' => $request->input('fixed_charge'),
            'percent_charge' => $request->input('percent_charge'),
            'instructions' => $request->input('instructions'),
            'status' => 1,
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.gateways.manual.index')->with('status', 'Gateway Added Successfully');
    }

    public function manualEdit(string $id)
    {
        $gateway = $this->legacyTableExists('tbl_gateway')
            ? DB::table('tbl_gateway')->where('id', $id)->first()
            : null;

        if (! $gateway) {
            return redirect()->route('admin.gateways.manual.index')->with('status', 'Gateway Not Found');
        }

        if ($gateway && is_string($gateway->role)) {
            $gateway->role = array_filter(array_map('trim', explode(',', $gateway->role)));
        }

        return view('admin.gateways.manual-form', [
            'mode' => 'edit',
            'gateway' => $gateway,
        ]);
    }

    public function manualUpdate(Request $request, string $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'role' => ['required', 'array'],
            'role.*' => ['string'],
            'currency' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'min:0'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0'],
            'fixed_charge' => ['required', 'numeric', 'min:0'],
            'percent_charge' => ['required', 'numeric', 'min:0'],
            'instructions' => ['required', 'string'],
        ]);

        DB::table('tbl_gateway')
            ->where('id', $id)
            ->update([
                'name' => $request->input('name'),
                'role' => implode(',', $request->input('role', [])),
                'currency' => $request->input('currency'),
                'rate' => $request->input('rate'),
                'min_amount' => $request->input('min_amount'),
                'max_amount' => $request->input('max_amount'),
                'fixed_charge' => $request->input('fixed_charge'),
                'percent_charge' => $request->input('percent_charge'),
                'instructions' => $request->input('instructions'),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.gateways.manual.index')->with('status', 'Gateway Updated Successfully');
    }

    public function manualToggleStatus(string $id)
    {
        $gateway = DB::table('tbl_gateway')->where('id', $id)->first();
        if (! $gateway) {
            return redirect()->route('admin.gateways.manual.index')->with('status', 'Gateway Not Found');
        }

        $newStatus = (int) ($gateway->status ?? 0) === 1 ? 0 : 1;

        DB::table('tbl_gateway')->where('id', $id)->update([
            'status' => $newStatus,
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        $msg = $newStatus === 1 ? 'Gateway Enabled Successfully' : 'Gateway Disabled Successfully';

        return redirect()->route('admin.gateways.manual.index')->with('status', $msg);
    }

    public function agentIndex()
    {
        $exists = $this->legacyTableExists('tbl_agent_gatway') && $this->legacyTableExists('tbl_gateway');
        $rows = $exists
            ? $this->agentGatewayBaseQuery('tbl_agent_gatway', 'agent_id')->orderByDesc('tbl_agent_gatway.id')->get()
            : collect();

        return view('admin.gateways.agent-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function agentCreate()
    {
        return view('admin.gateways.agent-form', [
            'mode' => 'create',
            'row' => null,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('2'),
        ]);
    }

    public function agentStore(Request $request)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_agent_gatway')->insert([
            'gateway_id' => $request->input('gateway_id'),
            'number' => $request->input('number'),
            'agent_id' => $request->input('agent_id', 0),
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.gateways.agent.index')->with('status', 'Agent Gateway Added Successfully');
    }

    public function agentEdit(string $id)
    {
        $row = $this->legacyTableExists('tbl_agent_gatway')
            ? DB::table('tbl_agent_gatway')->where('id', $id)->first()
            : null;

        if (! $row) {
            return redirect()->route('admin.gateways.agent.index')->with('status', 'Agent Gateway Not Found');
        }

        return view('admin.gateways.agent-form', [
            'mode' => 'edit',
            'row' => $row,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('2'),
        ]);
    }

    public function agentUpdate(Request $request, string $id)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_agent_gatway')
            ->where('id', $id)
            ->update([
                'gateway_id' => $request->input('gateway_id'),
                'number' => $request->input('number'),
                'agent_id' => $request->input('agent_id', 0),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.gateways.agent.index')->with('status', 'Agent Gateway Updated Successfully');
    }

    public function agentWithdrawIndex()
    {
        $exists = $this->legacyTableExists('tbl_agent_gatway_withdraw') && $this->legacyTableExists('tbl_gateway');
        $rows = $exists
            ? $this->agentGatewayBaseQuery('tbl_agent_gatway_withdraw', 'agent_id')->orderByDesc('tbl_agent_gatway_withdraw.id')->get()
            : collect();

        return view('admin.gateways.agent-withdraw-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function agentWithdrawCreate()
    {
        return view('admin.gateways.agent-withdraw-form', [
            'mode' => 'create',
            'row' => null,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('2'),
        ]);
    }

    public function agentWithdrawStore(Request $request)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_agent_gatway_withdraw')->insert([
            'gateway_id' => $request->input('gateway_id'),
            'number' => $request->input('number'),
            'agent_id' => $request->input('agent_id', 0),
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.gateways.agent-withdraw.index')->with('status', 'Agent Withdraw Gateway Added Successfully');
    }

    public function agentWithdrawEdit(string $id)
    {
        $row = $this->legacyTableExists('tbl_agent_gatway_withdraw')
            ? DB::table('tbl_agent_gatway_withdraw')->where('id', $id)->first()
            : null;

        if (! $row) {
            return redirect()->route('admin.gateways.agent-withdraw.index')->with('status', 'Agent Withdraw Gateway Not Found');
        }

        return view('admin.gateways.agent-withdraw-form', [
            'mode' => 'edit',
            'row' => $row,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('2'),
        ]);
    }

    public function agentWithdrawUpdate(Request $request, string $id)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'agent_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_agent_gatway_withdraw')
            ->where('id', $id)
            ->update([
                'gateway_id' => $request->input('gateway_id'),
                'number' => $request->input('number'),
                'agent_id' => $request->input('agent_id', 0),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.gateways.agent-withdraw.index')->with('status', 'Agent Withdraw Gateway Updated Successfully');
    }

    public function distributorIndex()
    {
        $exists = $this->legacyTableExists('tbl_distributor_gatway') && $this->legacyTableExists('tbl_gateway');
        $rows = $exists
            ? $this->distributorGatewayBaseQuery('tbl_distributor_gatway', 'distributor_id')->orderByDesc('tbl_distributor_gatway.id')->get()
            : collect();

        return view('admin.gateways.distributor-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function distributorCreate()
    {
        return view('admin.gateways.distributor-form', [
            'mode' => 'create',
            'row' => null,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('3'),
        ]);
    }

    public function distributorStore(Request $request)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'distributor_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_distributor_gatway')->insert([
            'gateway_id' => $request->input('gateway_id'),
            'number' => $request->input('number'),
            'distributor_id' => $request->input('distributor_id', 0),
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.gateways.distributor.index')->with('status', 'Distributor Gateway Added Successfully');
    }

    public function distributorEdit(string $id)
    {
        $row = $this->legacyTableExists('tbl_distributor_gatway')
            ? DB::table('tbl_distributor_gatway')->where('id', $id)->first()
            : null;

        if (! $row) {
            return redirect()->route('admin.gateways.distributor.index')->with('status', 'Distributor Gateway Not Found');
        }

        return view('admin.gateways.distributor-form', [
            'mode' => 'edit',
            'row' => $row,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('3'),
        ]);
    }

    public function distributorUpdate(Request $request, string $id)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'distributor_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_distributor_gatway')
            ->where('id', $id)
            ->update([
                'gateway_id' => $request->input('gateway_id'),
                'number' => $request->input('number'),
                'distributor_id' => $request->input('distributor_id', 0),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.gateways.distributor.index')->with('status', 'Distributor Gateway Updated Successfully');
    }

    public function distributorWithdrawIndex()
    {
        $exists = $this->legacyTableExists('tbl_distributor_gatway_withdraw') && $this->legacyTableExists('tbl_gateway');
        $rows = $exists
            ? $this->distributorGatewayBaseQuery('tbl_distributor_gatway_withdraw', 'distributor_id')
                ->orderByDesc('tbl_distributor_gatway_withdraw.id')
                ->get()
            : collect();

        return view('admin.gateways.distributor-withdraw-index', [
            'exists' => $exists,
            'rows' => $rows,
        ]);
    }

    public function distributorWithdrawCreate()
    {
        return view('admin.gateways.distributor-withdraw-form', [
            'mode' => 'create',
            'row' => null,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('3'),
        ]);
    }

    public function distributorWithdrawStore(Request $request)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'distributor_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_distributor_gatway_withdraw')->insert([
            'gateway_id' => $request->input('gateway_id'),
            'number' => $request->input('number'),
            'distributor_id' => $request->input('distributor_id', 0),
            'isDeleted' => 0,
            'created_date' => now()->format('Y-m-d H:i:s'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.gateways.distributor-withdraw.index')->with('status', 'Distributor Withdraw Gateway Added Successfully');
    }

    public function distributorWithdrawEdit(string $id)
    {
        $row = $this->legacyTableExists('tbl_distributor_gatway_withdraw')
            ? DB::table('tbl_distributor_gatway_withdraw')->where('id', $id)->first()
            : null;

        if (! $row) {
            return redirect()->route('admin.gateways.distributor-withdraw.index')->with('status', 'Distributor Withdraw Gateway Not Found');
        }

        return view('admin.gateways.distributor-withdraw-form', [
            'mode' => 'edit',
            'row' => $row,
            'gateways' => $this->manualGatewayOptions(),
            'owners' => $this->legacyAdminUsersByRole('3'),
        ]);
    }

    public function distributorWithdrawUpdate(Request $request, string $id)
    {
        $request->validate([
            'gateway_id' => ['required', 'integer'],
            'number' => ['required', 'numeric'],
            'distributor_id' => ['nullable', 'integer'],
        ]);

        DB::table('tbl_distributor_gatway_withdraw')
            ->where('id', $id)
            ->update([
                'gateway_id' => $request->input('gateway_id'),
                'number' => $request->input('number'),
                'distributor_id' => $request->input('distributor_id', 0),
                'updated_date' => now()->format('Y-m-d H:i:s'),
            ]);

        return redirect()->route('admin.gateways.distributor-withdraw.index')->with('status', 'Distributor Withdraw Gateway Updated Successfully');
    }

    protected function manualGatewayOptions()
    {
        if (! $this->legacyTableExists('tbl_gateway')) {
            return collect();
        }

        return DB::table('tbl_gateway')
            ->where('isDeleted', 0)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
    }

    protected function legacyAdminUsersByRole(string $role)
    {
        if (! $this->legacyTableExists('tbl_admin')) {
            return collect();
        }

        return DB::table('tbl_admin')
            ->where('isDeleted', 0)
            ->where('role', $role)
            ->orderBy('id')
            ->get();
    }

    protected function agentGatewayBaseQuery(string $table, string $ownerColumn)
    {
        $query = DB::table($table)
            ->select($table.'.*', 'tbl_gateway.name as gateway_name')
            ->join('tbl_gateway', 'tbl_gateway.id', '=', $table.'.gateway_id')
            ->where($table.'.isDeleted', 0)
            ->where('tbl_gateway.isDeleted', 0);

        if ($this->legacyTableExists('tbl_admin')) {
            $query->leftJoin('tbl_admin', 'tbl_admin.id', '=', $table.'.'.$ownerColumn)
                ->addSelect(
                    'tbl_admin.first_name as owner_first_name',
                    'tbl_admin.last_name as owner_last_name',
                    'tbl_admin.email as owner_email'
                );
        }

        return $query;
    }

    protected function distributorGatewayBaseQuery(string $table, string $ownerColumn)
    {
        return $this->agentGatewayBaseQuery($table, $ownerColumn);
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
