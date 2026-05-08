<?php $__env->startSection('title', 'Gateway Transactions'); ?>
<?php $__env->startSection('heading', 'Gateway Transactions'); ?>
<?php $__env->startSection('subheading', 'Rox hosted deposit flow and manual settlement control'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <form method="GET" class="split-3">
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php $__currentLoopData = ['pending', 'success', 'rejected']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($status); ?>" <?php if(($filters['status'] ?? '') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label>TRX</label>
            <input type="text" name="trx" value="<?php echo e($filters['trx'] ?? ''); ?>" placeholder="ROX-...">
        </div>
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="<?php echo e($filters['user_id'] ?? ''); ?>" placeholder="App user ID">
        </div>
        <div>
            <label>TRA ID</label>
            <input type="text" name="tra_id" value="<?php echo e($filters['tra_id'] ?? ''); ?>" placeholder="Provider TRA ID">
        </div>
        <div>
            <label>UTR ID</label>
            <input type="text" name="utr_id" value="<?php echo e($filters['utr_id'] ?? ''); ?>" placeholder="Bank UTR ID">
        </div>
        <div style="align-self:end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.gateway-transactions.index')); ?>">Reset</a>
        </div>
    </form>

    <?php if(! $exists): ?>
        <div class="error-list">Table rox_gateway_transactions not found. Run the latest migrations first.</div>
    <?php endif; ?>

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>TRX</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Gateway</th>
                    <th>TRA / UTR</th>
                    <th>Hosted URL</th>
                    <th>Created</th>
                    <th>Manual Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="User">
                            <div><?php echo e($row->user_id ?: '-'); ?></div>
                            <div class="muted"><?php echo e($row->app_username ?: '-'); ?></div>
                        </td>
                        <td data-label="TRX"><?php echo e($row->trx); ?></td>
                        <td data-label="Amount"><?php echo e($row->amount); ?> <?php echo e($row->currency); ?></td>
                        <td data-label="Status">
                            <div><?php echo e(ucfirst($row->status)); ?></div>
                            <div class="muted"><?php echo e($row->gateway_status ?: '-'); ?></div>
                        </td>
                        <td data-label="Gateway"><?php echo e($row->gateway_transaction_id ?: '-'); ?></td>
                        <td data-label="TRA / UTR">
                            <div>TRA: <?php echo e($row->tra_id ?: '-'); ?></div>
                            <div>UTR: <?php echo e($row->utr_id ?: '-'); ?></div>
                        </td>
                        <td data-label="Hosted URL">
                            <?php if(! empty($row->payment_url)): ?>
                                <a href="<?php echo e($row->payment_url); ?>" target="_blank" rel="noopener">Open</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td data-label="Created"><?php echo e($row->created_at ?: '-'); ?></td>
                        <td data-label="Manual Action" style="min-width: 280px;">
                            <form method="POST" action="<?php echo e(route('admin.gateway-transactions.status')); ?>" class="stack" style="gap:8px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo e($row->id); ?>">
                                <select name="status">
                                    <option value="pending" <?php if($row->status === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                                    <option value="success" <?php if($row->status === 'success'): echo 'selected'; endif; ?>>Approve</option>
                                    <option value="rejected" <?php if($row->status === 'rejected'): echo 'selected'; endif; ?>>Reject</option>
                                </select>
                                <input type="text" name="tra_id" value="<?php echo e($row->tra_id ?: ''); ?>" placeholder="TRA ID">
                                <input type="text" name="utr_id" value="<?php echo e($row->utr_id ?: ''); ?>" placeholder="UTR ID">
                                <textarea name="note" rows="2" placeholder="Manual note"><?php echo e($row->manual_status_note ?? ''); ?></textarea>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="10" class="muted">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/gateway-transactions/index.blade.php ENDPATH**/ ?>