<?php $__env->startSection('title', 'Deposit Bonus'); ?>
<?php $__env->startSection('heading', 'Deposit Bonus'); ?>
<?php $__env->startSection('subheading', 'Legacy tbl_purcharse_ref records'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="<?php echo e($filters['user_id'] ?? ''); ?>" placeholder="Legacy user_id">
        </div>
        <div>
            <label>Type</label>
            <input type="text" name="type" value="<?php echo e($filters['type'] ?? ''); ?>" placeholder="Type">
        </div>
        <div>
            <label>Purchase User ID</label>
            <input type="text" name="purchase_user_id" value="<?php echo e($filters['purchase_user_id'] ?? ''); ?>" placeholder="purchase_user_id">
        </div>
        <div>
            <label>Date</label>
            <input type="text" name="date" value="<?php echo e($filters['date'] ?? ''); ?>" placeholder="YYYY-MM-DD">
        </div>
        <div class="mobile-actions" style="grid-column: 1 / -1;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.legacy-reports.deposit-bonus')); ?>">Reset</a>
        </div>
    </form>

    <?php if(! $exists): ?>
        <div class="error-list">Legacy table tbl_purcharse_ref not found.</div>
    <?php endif; ?>

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Purchase User</th>
                    <th>Coin</th>
                    <th>Purchase Amount</th>
                    <th>Type</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="User ID"><?php echo e($row->user_id); ?></td>
                        <td data-label="Purchase User"><?php echo e($row->purchase_user_id ?? '-'); ?></td>
                        <td data-label="Coin"><?php echo e($row->coin ?? '-'); ?></td>
                        <td data-label="Amount"><?php echo e($row->purchase_amount ?? '-'); ?></td>
                        <td data-label="Type"><?php echo e($row->type ?? '-'); ?></td>
                        <td data-label="Added"><?php echo e($row->added_date ?? '-'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="muted">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/legacy-reports/deposit-bonus.blade.php ENDPATH**/ ?>