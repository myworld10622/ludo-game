<?php $__env->startSection('title', 'Deposit Requests'); ?>
<?php $__env->startSection('heading', 'Deposit Requests'); ?>
<?php $__env->startSection('subheading', 'Distributor to admin requests'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <?php if(! $exists): ?>
        <div class="error-list">Legacy tables for deposits not found.</div>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="Distributor"><?php echo e($row->distributor ?? '-'); ?></td>
                        <td data-label="Distributor ID"><?php echo e($row->distributor_id); ?></td>
                        <td data-label="Amount"><?php echo e($row->amount); ?></td>
                        <td data-label="Txn ID"><?php echo e($row->txn_id); ?></td>
                        <td data-label="Gateway"><?php echo e($row->gateway_name); ?></td>
                        <td data-label="Status">
                            <select data-deposit-status="<?php echo e($row->id); ?>">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="muted">No pending deposits.</td></tr>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $approved; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="Distributor"><?php echo e($row->distributor ?? '-'); ?></td>
                        <td data-label="Distributor ID"><?php echo e($row->distributor_id); ?></td>
                        <td data-label="Amount"><?php echo e($row->amount); ?></td>
                        <td data-label="Txn ID"><?php echo e($row->txn_id); ?></td>
                        <td data-label="Gateway"><?php echo e($row->gateway_name); ?></td>
                        <td data-label="Status">
                            <select data-deposit-status="<?php echo e($row->id); ?>">
                                <option value="1" selected>Approved</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="muted">No approved deposits.</td></tr>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rejected; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="Distributor"><?php echo e($row->distributor ?? '-'); ?></td>
                        <td data-label="Distributor ID"><?php echo e($row->distributor_id); ?></td>
                        <td data-label="Amount"><?php echo e($row->amount); ?></td>
                        <td data-label="Txn ID"><?php echo e($row->txn_id); ?></td>
                        <td data-label="Gateway"><?php echo e($row->gateway_name); ?></td>
                        <td data-label="Status">
                            <select data-deposit-status="<?php echo e($row->id); ?>">
                                <option value="2" selected>Rejected</option>
                                <option value="1">Approve</option>
                            </select>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="muted">No rejected deposits.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('[data-deposit-status]').forEach(function (select) {
    select.addEventListener('change', function () {
        const id = select.getAttribute('data-deposit-status');
        const status = select.value;
        fetch("<?php echo e(route('admin.deposits.status')); ?>", {
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
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/deposits/index.blade.php ENDPATH**/ ?>