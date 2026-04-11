<?php $__env->startSection('title', 'Deposit बोनस'); ?>
<?php $__env->startSection('heading', 'Deposit Bonus'); ?>
<?php $__env->startSection('subheading', 'Bonus slabs for deposits'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Deposit Bonus Slabs</div>
            <div class="muted">Min/Max amount based bonus rules.</div>
        </div>
        <a class="btn" href="<?php echo e(route('admin.deposits.bonus.create')); ?>">Add Bonus</a>
    </div>

    <?php if(! $exists): ?>
        <div class="error-list">Legacy table tbl_deposit_bonus_master not found.</div>
    <?php endif; ?>

    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Min</th>
                <th>Max</th>
                <th>Self Bonus</th>
                <th>Upline Bonus</th>
                <th>Deposit Count</th>
                <th>Added Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="ID"><?php echo e($row->id); ?></td>
                    <td data-label="Min"><?php echo e($row->min); ?></td>
                    <td data-label="Max"><?php echo e($row->max); ?></td>
                    <td data-label="Self Bonus"><?php echo e($row->self_bonus); ?></td>
                    <td data-label="Upline Bonus"><?php echo e($row->upline_bonus); ?></td>
                    <td data-label="Deposit Count"><?php echo e($row->deposit_count); ?></td>
                    <td data-label="Added"><?php echo e($row->added_date); ?></td>
                    <td data-label="Action">
                        <a class="btn btn-secondary" href="<?php echo e(route('admin.deposits.bonus.edit', $row->id)); ?>">Edit</a>
                        <form method="POST" action="<?php echo e(route('admin.deposits.bonus.delete', $row->id)); ?>" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-secondary" type="submit" onclick="return confirm('Delete bonus slab?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="muted">No bonus slabs found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/deposits/bonus-index.blade.php ENDPATH**/ ?>