<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingController extends Controller
{
    public function appConfiguration()
    {
        $exists = $this->legacyTableExists('tbl_setting');
        $setting = $exists ? DB::table('tbl_setting')->first() : null;

        return view('admin.settings.app-configuration', [
            'exists' => $exists,
            'setting' => $setting,
        ]);
    }

    public function updateAppConfiguration(Request $request)
    {
        if (! $this->legacyTableExists('tbl_setting')) {
            return redirect()->route('admin.settings.app')->with('status', 'Settings table not found.');
        }

        $request->validate([
            'referral_amount' => ['required', 'numeric', 'min:0'],
            'level_1' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_2' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_3' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_4' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_5' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_6' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_7' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_8' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_9' => ['required', 'numeric', 'min:0', 'max:100'],
            'level_10' => ['required', 'numeric', 'min:0', 'max:100'],
            'referral_id' => ['required', 'string', 'max:190'],
            'referral_link' => ['required', 'string', 'max:255'],
            'share_text' => ['required', 'string', 'max:255'],
            'min_withdrawal' => ['required', 'numeric', 'min:0'],
            'admin_commission' => ['required', 'numeric', 'min:0'],
            'distribute_precent' => ['required', 'numeric', 'min:0'],
            'bonus' => ['required', 'in:0,1'],
            'bonus_amount' => ['required', 'numeric', 'min:0'],
            'upi_id' => ['nullable', 'string', 'max:190'],
            'usdt_address' => ['nullable', 'string', 'max:190'],
            'upi_gateway_key' => ['nullable', 'string', 'max:255'],
            'dollar' => ['required', 'numeric', 'min:0'],
            'daily_bonus_status' => ['required', 'string', 'max:50'],
            'app_popop_status' => ['required', 'string', 'max:50'],
            'fcm_server_key' => ['required', 'string'],
            'qr_image' => ['nullable', 'image', 'max:2048'],
            'usdt_qr_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'referral_amount' => $request->input('referral_amount'),
            'level_1' => $request->input('level_1'),
            'level_2' => $request->input('level_2'),
            'level_3' => $request->input('level_3'),
            'level_4' => $request->input('level_4'),
            'level_5' => $request->input('level_5'),
            'level_6' => $request->input('level_6'),
            'level_7' => $request->input('level_7'),
            'level_8' => $request->input('level_8'),
            'level_9' => $request->input('level_9'),
            'level_10' => $request->input('level_10'),
            'referral_id' => $request->input('referral_id'),
            'referral_link' => $request->input('referral_link'),
            'share_text' => $request->input('share_text'),
            'min_withdrawal' => $request->input('min_withdrawal'),
            'admin_commission' => $request->input('admin_commission'),
            'distribute_precent' => $request->input('distribute_precent'),
            'bonus' => $request->input('bonus'),
            'bonus_amount' => $request->input('bonus_amount'),
            'upi_id' => $request->input('upi_id'),
            'usdt_address' => $request->input('usdt_address'),
            'upi_gateway_api_key' => $request->input('upi_gateway_key'),
            'dollar' => $request->input('dollar'),
            'daily_bonus_status' => $request->input('daily_bonus_status'),
            'app_popop_status' => $request->input('app_popop_status'),
            'fcm_server_key' => $request->input('fcm_server_key'),
            'updated_date' => now()->format('Y-m-d H:i:s'),
        ];

        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $this->storeSettingsUpload($request->file('qr_image'), 'upi_qr');
        }

        if ($request->hasFile('usdt_qr_image')) {
            $data['usdt_qr_image'] = $this->storeSettingsUpload($request->file('usdt_qr_image'), 'usdt_qr');
        }

        DB::table('tbl_setting')->update($data);

        return redirect()->route('admin.settings.app')->with('status', 'App configuration updated successfully.');
    }

    protected function storeSettingsUpload($file, string $prefix): string
    {
        $dir = public_path('data/Settings');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'png';
        $filename = $prefix.'_'.time().'_'.mt_rand(1000, 9999).'.'.$extension;
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
