<?php $__env->startSection('title', 'Withdrawal Requests'); ?>
<?php $__env->startSection('heading', 'Withdrawal Requests'); ?>
<?php $__env->startSection('subheading', 'Approve or reject legacy withdrawal logs'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>Start Date</label>
            <input type="text" name="start_date" value="<?php echo e($filters['start_date'] ?? ''); ?>" placeholder="YYYY-MM-DD">
        </div>
        <div>
            <label>End Date</label>
            <input type="text" name="end_date" value="<?php echo e($filters['end_date'] ?? ''); ?>" placeholder="YYYY-MM-DD">
        </div>
        <div class="mobile-actions" style="grid-column: 1 / -1;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.withdrawals.index')); ?>">Reset</a>
        </div>
    </form>

    <?php if(! $exists): ?>
        <div class="error-list">Legacy table tbl_withdrawal_log not found.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Pending</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Type</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Transfer</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="User"><?php echo e($row->user_name ?? '-'); ?></td>
                        <td data-label="User ID"><?php echo e($row->user_id); ?></td>
                        <td data-label="Type"><?php echo e((int) $row->type === 0 ? 'Bank' : 'Crypto'); ?></td>
                        <td data-label="Bank"><?php echo e($row->bank_name); ?></td>
                        <td data-label="IFSC"><?php echo e($row->ifsc_code); ?></td>
                        <td data-label="Account"><?php echo e($row->acc_no); ?></td>
                        <td data-label="Crypto"><?php echo e($row->crypto_address); ?></td>
                        <td data-label="Wallet"><?php echo e($row->crypto_wallet_type); ?></td>
                        <td data-label="Coin"><?php echo e($row->coin); ?></td>
                        <td data-label="Status">
                            <select data-withdraw-status="<?php echo e($row->id); ?>">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                        <td data-label="Transfer">
                            <button class="btn btn-secondary" type="button" data-transfer-betzono="<?php echo e($row->id); ?>">
                                Send to Betzono
                            </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="13" class="muted">No pending withdrawals.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Approved</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $approved; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="User"><?php echo e($row->user_name ?? '-'); ?></td>
                        <td data-label="User ID"><?php echo e($row->user_id); ?></td>
                        <td data-label="Bank"><?php echo e($row->bank_name); ?></td>
                        <td data-label="IFSC"><?php echo e($row->ifsc_code); ?></td>
                        <td data-label="Account"><?php echo e($row->acc_no); ?></td>
                        <td data-label="Crypto"><?php echo e($row->crypto_address); ?></td>
                        <td data-label="Wallet"><?php echo e($row->crypto_wallet_type); ?></td>
                        <td data-label="Coin"><?php echo e($row->coin); ?></td>
                        <td data-label="Status">
                            <select data-withdraw-status="<?php echo e($row->id); ?>">
                                <option value="1" selected>Approved</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="11" class="muted">No approved withdrawals.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Rejected</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rejected; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="User"><?php echo e($row->user_name ?? '-'); ?></td>
                        <td data-label="User ID"><?php echo e($row->user_id); ?></td>
                        <td data-label="Bank"><?php echo e($row->bank_name); ?></td>
                        <td data-label="IFSC"><?php echo e($row->ifsc_code); ?></td>
                        <td data-label="Account"><?php echo e($row->acc_no); ?></td>
                        <td data-label="Crypto"><?php echo e($row->crypto_address); ?></td>
                        <td data-label="Wallet"><?php echo e($row->crypto_wallet_type); ?></td>
                        <td data-label="Coin"><?php echo e($row->coin); ?></td>
                        <td data-label="Status">
                            <select data-withdraw-status="<?php echo e($row->id); ?>">
                                <option value="2" selected>Rejected</option>
                                <option value="1">Approve</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="11" class="muted">No rejected withdrawals.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('[data-withdraw-status]').forEach(function (select) {
    select.addEventListener('change', function () {
        const id = select.getAttribute('data-withdraw-status');
        const status = select.value;
        fetch("<?php echo e(route('admin.withdrawals.status')); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "<?php echo e(csrf_token()); ?>"
            },
            body: JSON.stringify({ id, status })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.class === 'success') {
                alert(data.msg);
                window.location.reload();
            } else {
                alert(data.msg || 'Something went to wrong');
            }
        })
        .catch(function () {
            alert('Status update failed');
        });
    });
});

document.querySelectorAll('[data-transfer-betzono]').forEach(function (button) {
    button.addEventListener('click', function () {
        const id = button.getAttribute('data-transfer-betzono');
        if (! id) {
            return;
        }
        if (! confirm('Send this withdrawal to Betzono?')) {
            return;
        }

        fetch("<?php echo e(route('admin.withdrawals.transfer')); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "<?php echo e(csrf_token()); ?>"
            },
            body: JSON.stringify({ id })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.class === 'success') {
                alert(data.msg);
                window.location.reload();
            } else {
                alert(data.msg || 'Transfer failed');
            }
        })
        .catch(function () {
            alert('Transfer request failed');
        });
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/withdrawals/index.blade.php ENDPATH**/ ?>