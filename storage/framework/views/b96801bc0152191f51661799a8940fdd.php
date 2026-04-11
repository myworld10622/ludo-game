<?php $__env->startSection('title', 'Redeem Presets'); ?>
<?php $__env->startSection('heading', 'Redeem Presets'); ?>
<?php $__env->startSection('subheading', 'Manage legacy redeem cards'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <div class="header-row">
        <div>
            <div style="font-weight: 800; font-size: 18px;">Redeem Cards</div>
            <div class="muted">These are used in the app withdraw screen.</div>
        </div>
        <a class="btn" href="<?php echo e(route('admin.withdrawals.redeem.create')); ?>">Add Redeem</a>
    </div>

    <?php if(! $exists): ?>
        <div class="error-list">Legacy table tbl_redeem not found.</div>
    <?php endif; ?>

    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Coin</th>
                <th>Amount</th>
                <th>Image</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="ID"><?php echo e($row->id); ?></td>
                    <td data-label="Title"><?php echo e($row->title); ?></td>
                    <td data-label="Coin"><?php echo e($row->coin); ?></td>
                    <td data-label="Amount"><?php echo e($row->amount); ?></td>
                    <td data-label="Image">
                        <?php if(!empty($row->img)): ?>
                            <img src="<?php echo e(url('data/Redeem/'.$row->img)); ?>" style="width: 80px; border-radius: 8px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td data-label="Created"><?php echo e($row->created_date); ?></td>
                    <td data-label="Action">
                        <a class="btn btn-secondary" href="<?php echo e(route('admin.withdrawals.redeem.edit', $row->id)); ?>">Edit</a>
                        <form method="POST" action="<?php echo e(route('admin.withdrawals.redeem.delete', $row->id)); ?>" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-secondary" type="submit" onclick="return confirm('Remove this redeem?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="muted">No redeem presets found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/withdrawals/redeem-index.blade.php ENDPATH**/ ?>