@extends('admin.layouts.app')

@section('title', 'App Configuration')
@section('heading', 'App Configuration')
@section('subheading', 'Control referral, bonus, and payment settings')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Settings table not found in legacy database.</div>
    @endif

    <div class="panel">
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.app.update') }}">
            @csrf

            <div class="split-2">
                <div>
                    <label>Referral Coins</label>
                    <input type="number" name="referral_amount" value="{{ old('referral_amount', $setting->referral_amount ?? '') }}" required>
                </div>
                <div>
                    <label>Referral ID</label>
                    <input type="text" name="referral_id" value="{{ old('referral_id', $setting->referral_id ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Link</label>
                    <input type="text" name="referral_link" value="{{ old('referral_link', $setting->referral_link ?? '') }}" required>
                </div>
                <div>
                    <label>Share Text</label>
                    <input type="text" name="share_text" value="{{ old('share_text', $setting->share_text ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 1 (%)</label>
                    <input type="number" name="level_1" min="0" max="100" step="0.01" value="{{ old('level_1', $setting->level_1 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 2 (%)</label>
                    <input type="number" name="level_2" min="0" max="100" step="0.01" value="{{ old('level_2', $setting->level_2 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 3 (%)</label>
                    <input type="number" name="level_3" min="0" max="100" step="0.01" value="{{ old('level_3', $setting->level_3 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 4 (%)</label>
                    <input type="number" name="level_4" min="0" max="100" step="0.01" value="{{ old('level_4', $setting->level_4 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 5 (%)</label>
                    <input type="number" name="level_5" min="0" max="100" step="0.01" value="{{ old('level_5', $setting->level_5 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 6 (%)</label>
                    <input type="number" name="level_6" min="0" max="100" step="0.01" value="{{ old('level_6', $setting->level_6 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 7 (%)</label>
                    <input type="number" name="level_7" min="0" max="100" step="0.01" value="{{ old('level_7', $setting->level_7 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 8 (%)</label>
                    <input type="number" name="level_8" min="0" max="100" step="0.01" value="{{ old('level_8', $setting->level_8 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 9 (%)</label>
                    <input type="number" name="level_9" min="0" max="100" step="0.01" value="{{ old('level_9', $setting->level_9 ?? '') }}" required>
                </div>
                <div>
                    <label>Referral Level 10 (%)</label>
                    <input type="number" name="level_10" min="0" max="100" step="0.01" value="{{ old('level_10', $setting->level_10 ?? '') }}" required>
                </div>
            </div>

            <div class="split-2" style="margin-top:16px;">
                <div>
                    <label>Minimum Withdrawal</label>
                    <input type="number" name="min_withdrawal" min="0" value="{{ old('min_withdrawal', $setting->min_withdrawal ?? '') }}" required>
                </div>
                <div>
                    <label>Admin Commission</label>
                    <input type="number" name="admin_commission" min="0" value="{{ old('admin_commission', $setting->admin_commission ?? '') }}" required>
                </div>
                <div>
                    <label>Distribute Percentage</label>
                    <input type="number" name="distribute_precent" min="0" value="{{ old('distribute_precent', $setting->distribute_precent ?? '') }}" required>
                </div>
                <div>
                    <label>Registration Bonus Enabled</label>
                    <select name="bonus" required>
                        <option value="0" {{ old('bonus', $setting->bonus ?? '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('bonus', $setting->bonus ?? '0') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div>
                    <label>Registration Bonus Amount</label>
                    <input type="number" name="bonus_amount" min="0" value="{{ old('bonus_amount', $setting->bonus_amount ?? '') }}" required>
                </div>
                <div>
                    <label>INR to Dollar</label>
                    <input type="number" name="dollar" min="0" step="0.01" value="{{ old('dollar', $setting->dollar ?? '') }}" required>
                </div>
                <div>
                    <label>Daily Bonus Status</label>
                    <input type="text" name="daily_bonus_status" value="{{ old('daily_bonus_status', $setting->daily_bonus_status ?? '') }}" required>
                </div>
                <div>
                    <label>App Popup Status</label>
                    <input type="text" name="app_popop_status" value="{{ old('app_popop_status', $setting->app_popop_status ?? '') }}" required>
                </div>
                <div>
                    <label>FCM Server Key</label>
                    <input type="text" name="fcm_server_key" value="{{ old('fcm_server_key', $setting->fcm_server_key ?? '') }}" required>
                </div>
            </div>

            <div class="split-2" style="margin-top:16px;">
                <div>
                    <label>UPI ID</label>
                    <input type="text" name="upi_id" value="{{ old('upi_id', $setting->upi_id ?? '') }}">
                </div>
                <div>
                    <label>UPI Gateway API Key</label>
                    <input type="text" name="upi_gateway_key" value="{{ old('upi_gateway_key', $setting->upi_gateway_api_key ?? '') }}">
                </div>
                <div>
                    <label>USDT Address</label>
                    <input type="text" name="usdt_address" value="{{ old('usdt_address', $setting->usdt_address ?? '') }}">
                </div>
                <div>
                    <label>UPI QR Image</label>
                    <input type="file" name="qr_image" accept="image/*">
                    @if (!empty($setting->qr_image))
                        <div class="muted" style="margin-top:8px;">Current: {{ $setting->qr_image }}</div>
                        <img src="{{ url('data/Settings/'.$setting->qr_image) }}" style="width: 120px; border-radius: 12px; margin-top:8px;">
                    @endif
                </div>
                <div>
                    <label>USDT QR Image</label>
                    <input type="file" name="usdt_qr_image" accept="image/*">
                    @if (!empty($setting->usdt_qr_image))
                        <div class="muted" style="margin-top:8px;">Current: {{ $setting->usdt_qr_image }}</div>
                        <img src="{{ url('data/Settings/'.$setting->usdt_qr_image) }}" style="width: 120px; border-radius: 12px; margin-top:8px;">
                    @endif
                </div>
            </div>

            <div class="mobile-actions" style="margin-top: 16px;">
                <button class="btn" type="submit">Update</button>
                <a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
