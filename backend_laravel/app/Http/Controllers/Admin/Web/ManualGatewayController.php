<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\ManualPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManualGatewayController extends Controller
{
    public function index()
    {
        $gateways = ManualPaymentGateway::orderBy('sort_order')->orderByDesc('id')->get();
        $globalEnabled = (bool) DB::table('tbl_setting')->value('manual_gateway_enabled') ?? true;

        return view('admin.manual-gateways.index', compact('gateways', 'globalEnabled'));
    }

    public function create()
    {
        return view('admin.manual-gateways.form', ['gateway' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gateway_name'   => ['required', 'string', 'max:100'],
            'type'           => ['required', 'in:upi,bank'],
            'bank_name'      => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code'      => ['nullable', 'string', 'max:20'],
            'account_holder' => ['nullable', 'string', 'max:100'],
            'upi_id'         => ['nullable', 'string', 'max:100'],
            'qr_image'       => ['nullable', 'image', 'max:2048'],
            'is_active'      => ['nullable', 'boolean'],
            'sort_order'     => ['nullable', 'integer'],
        ]);

        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $request->file('qr_image')->store('manual_gateways', 'public');
            $data['qr_image'] = basename($data['qr_image']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        ManualPaymentGateway::create($data);

        return redirect()->route('admin.manual-gateways.index')
            ->with('success', 'Gateway added successfully.');
    }

    public function edit(ManualPaymentGateway $manualGateway)
    {
        return view('admin.manual-gateways.form', ['gateway' => $manualGateway]);
    }

    public function update(Request $request, ManualPaymentGateway $manualGateway)
    {
        $data = $request->validate([
            'gateway_name'   => ['required', 'string', 'max:100'],
            'type'           => ['required', 'in:upi,bank'],
            'bank_name'      => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code'      => ['nullable', 'string', 'max:20'],
            'account_holder' => ['nullable', 'string', 'max:100'],
            'upi_id'         => ['nullable', 'string', 'max:100'],
            'qr_image'       => ['nullable', 'image', 'max:2048'],
            'is_active'      => ['nullable', 'boolean'],
            'sort_order'     => ['nullable', 'integer'],
        ]);

        if ($request->hasFile('qr_image')) {
            // Delete old image
            if ($manualGateway->qr_image) {
                Storage::disk('public')->delete('manual_gateways/'.$manualGateway->qr_image);
            }
            $data['qr_image'] = basename($request->file('qr_image')->store('manual_gateways', 'public'));
        } else {
            unset($data['qr_image']);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $manualGateway->update($data);

        return redirect()->route('admin.manual-gateways.index')
            ->with('success', 'Gateway updated successfully.');
    }

    public function destroy(ManualPaymentGateway $manualGateway)
    {
        if ($manualGateway->qr_image) {
            Storage::disk('public')->delete('manual_gateways/'.$manualGateway->qr_image);
        }
        $manualGateway->delete();

        return redirect()->route('admin.manual-gateways.index')
            ->with('success', 'Gateway deleted.');
    }

    public function toggleActive(ManualPaymentGateway $manualGateway)
    {
        $manualGateway->update(['is_active' => !$manualGateway->is_active]);

        return response()->json(['is_active' => $manualGateway->is_active]);
    }

    public function toggleGlobal(Request $request)
    {
        $enabled = $request->boolean('enabled');
        DB::table('tbl_setting')->update(['manual_gateway_enabled' => $enabled]);

        return response()->json(['manual_gateway_enabled' => $enabled]);
    }
}
