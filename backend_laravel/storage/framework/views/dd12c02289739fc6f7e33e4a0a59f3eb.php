<?php $__env->startSection('title', 'Distributor Withdraw Gateways'); ?>
<?php $__env->startSection('heading', 'Distributor Withdraw Gateways'); ?>
<?php $__env->startSection('subheading', 'Assign gateway numbers for distributor withdrawals'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <?php if(! $exists): ?>
        <div class="error-list">Legacy distributor withdraw gateway tables not found.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Withdraw Gateway Numbers</div>
            <a class="btn" href="<?php echo e(route('admin.gateways.distributor-withdraw.create')); ?>">Add Distributor Withdraw Gateway</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Distributor</th>
                    <th>Gateway</th>
                    <th>Number</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $ownerLabel = trim(($row->owner_first_name ?? '').' '.($row->owner_last_name ?? ''));
                        if ($ownerLabel === '') {
                            $ownerLabel = $row->owner_email ?? ('#'.$row->distributor_id);
                        }
                    ?>
                    <tr>
                        <td data-label="ID"><?php echo e($row->id); ?></td>
                        <td data-label="Distributor"><?php echo e($ownerLabel); ?></td>
                        <td data-label="Gateway"><?php echo e($row->gateway_name); ?></td>
                        <td data-label="Number"><?php echo e($row->number); ?></td>
                        <td data-label="Created"><?php echo e($row->created_date); ?></td>
                        <td data-label="Action">
                            <a class="btn btn-secondary" href="<?php echo e(route('admin.gateways.distributor-withdraw.edit', $row->id)); ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="muted">No distributor withdraw gateways found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/gateways/distributor-withdraw-index.blade.php ENDPATH**/ ?>