<?php $__env->startSection('title', 'App Configuration'); ?>
<?php $__env->startSection('heading', 'App Configuration'); ?>
<?php $__env->startSection('subheading', 'Control referral, bonus, and payment settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <?php if(! $exists): ?>
        <div class="error-list">Settings table not found in legacy database.</div>
    <?php endif; ?>

    <div class="panel">
        <form method="POST" enctype="multipart/form-data" action="<?php echo e(route('admin.settings.app.update')); ?>">
            <?php echo csrf_field(); ?>

            <div class="split-2">
                <div>
                    <label>Referral Coins</label>
                    <input type="number" name="referral_amount" value="<?php echo e(old('referral_amount', $setting->referral_amount ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral ID</label>
                    <input type="text" name="referral_id" value="<?php echo e(old('referral_id', $setting->referral_id ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Link</label>
                    <input type="text" name="referral_link" value="<?php echo e(old('referral_link', $setting->referral_link ?? '')); ?>" required>
                </div>
                <div>
                    <label>Share Text</label>
                    <input type="text" name="share_text" value="<?php echo e(old('share_text', $setting->share_text ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 1 (%)</label>
                    <input type="number" name="level_1" min="0" max="100" step="0.01" value="<?php echo e(old('level_1', $setting->level_1 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 2 (%)</label>
                    <input type="number" name="level_2" min="0" max="100" step="0.01" value="<?php echo e(old('level_2', $setting->level_2 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 3 (%)</label>
                    <input type="number" name="level_3" min="0" max="100" step="0.01" value="<?php echo e(old('level_3', $setting->level_3 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 4 (%)</label>
                    <input type="number" name="level_4" min="0" max="100" step="0.01" value="<?php echo e(old('level_4', $setting->level_4 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 5 (%)</label>
                    <input type="number" name="level_5" min="0" max="100" step="0.01" value="<?php echo e(old('level_5', $setting->level_5 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 6 (%)</label>
                    <input type="number" name="level_6" min="0" max="100" step="0.01" value="<?php echo e(old('level_6', $setting->level_6 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 7 (%)</label>
                    <input type="number" name="level_7" min="0" max="100" step="0.01" value="<?php echo e(old('level_7', $setting->level_7 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 8 (%)</label>
                    <input type="number" name="level_8" min="0" max="100" step="0.01" value="<?php echo e(old('level_8', $setting->level_8 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 9 (%)</label>
                    <input type="number" name="level_9" min="0" max="100" step="0.01" value="<?php echo e(old('level_9', $setting->level_9 ?? '')); ?>" required>
                </div>
                <div>
                    <label>Referral Level 10 (%)</label>
                    <input type="number" name="level_10" min="0" max="100" step="0.01" value="<?php echo e(old('level_10', $setting->level_10 ?? '')); ?>" required>
                </div>
            </div>

            <div class="split-2" style="margin-top:16px;">
                <div>
                    <label>Minimum Withdrawal</label>
                    <input type="number" name="min_withdrawal" min="0" value="<?php echo e(old('min_withdrawal', $setting->min_withdrawal ?? '')); ?>" required>
                </div>
                <div>
                    <label>Admin Commission</label>
                    <input type="number" name="admin_commission" min="0" value="<?php echo e(old('admin_commission', $setting->admin_commission ?? '')); ?>" required>
                </div>
                <div>
                    <label>Distribute Percentage</label>
                    <input type="number" name="distribute_precent" min="0" value="<?php echo e(old('distribute_precent', $setting->distribute_precent ?? '')); ?>" required>
                </div>
                <div>
                    <label>Registration Bonus Enabled</label>
                    <select name="bonus" required>
                        <option value="0" <?php echo e(old('bonus', $setting->bonus ?? '0') == '0' ? 'selected' : ''); ?>>No</option>
                        <option value="1" <?php echo e(old('bonus', $setting->bonus ?? '0') == '1' ? 'selected' : ''); ?>>Yes</option>
                    </select>
                </div>
                <div>
                    <label>Registration Bonus Amount</label>
                    <input type="number" name="bonus_amount" min="0" value="<?php echo e(old('bonus_amount', $setting->bonus_amount ?? '')); ?>" required>
                </div>
                <div>
                    <label>INR to Dollar</label>
                    <input type="number" name="dollar" min="0" step="0.01" value="<?php echo e(old('dollar', $setting->dollar ?? '')); ?>" required>
                </div>
                <div>
                    <label>Daily Bonus Status</label>
                    <input type="text" name="daily_bonus_status" value="<?php echo e(old('daily_bonus_status', $setting->daily_bonus_status ?? '')); ?>" required>
                </div>
                <div>
                    <label>App Popup Status</label>
                    <input type="text" name="app_popop_status" value="<?php echo e(old('app_popop_status', $setting->app_popop_status ?? '')); ?>" required>
                </div>
                <div>
                    <label>FCM Server Key</label>
                    <input type="text" name="fcm_server_key" value="<?php echo e(old('fcm_server_key', $setting->fcm_server_key ?? '')); ?>" required>
                </div>
            </div>

            <div class="split-2" style="margin-top:16px;">
                <div>
                    <label>UPI ID</label>
                    <input type="text" name="upi_id" value="<?php echo e(old('upi_id', $setting->upi_id ?? '')); ?>">
                </div>
                <div>
                    <label>UPI Gateway API Key</label>
                    <input type="text" name="upi_gateway_key" value="<?php echo e(old('upi_gateway_key', $setting->upi_gateway_api_key ?? '')); ?>">
                </div>
                <div>
                    <label>USDT Address</label>
                    <input type="text" name="usdt_address" value="<?php echo e(old('usdt_address', $setting->usdt_address ?? '')); ?>">
                </div>
                <div>
                    <label>UPI QR Image</label>
                    <input type="file" name="qr_image" accept="image/*">
                    <?php if(!empty($setting->qr_image)): ?>
                        <div class="muted" style="margin-top:8px;">Current: <?php echo e($setting->qr_image); ?></div>
                        <img src="<?php echo e(url('data/Settings/'.$setting->qr_image)); ?>" style="width: 120px; border-radius: 12px; margin-top:8px;">
                    <?php endif; ?>
                </div>
                <div>
                    <label>USDT QR Image</label>
                    <input type="file" name="usdt_qr_image" accept="image/*">
                    <?php if(!empty($setting->usdt_qr_image)): ?>
                        <div class="muted" style="margin-top:8px;">Current: <?php echo e($setting->usdt_qr_image); ?></div>
                        <img src="<?php echo e(url('data/Settings/'.$setting->usdt_qr_image)); ?>" style="width: 120px; border-radius: 12px; margin-top:8px;">
                    <?php endif; ?>
                </div>
            </div>

            <div class="mobile-actions" style="margin-top: 16px;">
                <button class="btn" type="submit">Update</button>
                <a class="btn btn-secondary" href="<?php echo e(route('admin.dashboard')); ?>">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/settings/app-configuration.blade.php ENDPATH**/ ?>