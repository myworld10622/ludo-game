<?php $__env->startSection('title', 'Manual Gateways'); ?>
<?php $__env->startSection('heading', 'Manual Gateways'); ?>
<?php $__env->startSection('subheading', 'Manage base gateway definitions'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel stack">
    <?php if(! $exists): ?>
        <div class="error-list">Legacy gateway table not found.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Gateway List</div>
            <a class="btn" href="<?php echo e(route('admin.gateways.manual.create')); ?>">Add Manual Gateway</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $gateways; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gateway): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="ID"><?php echo e($gateway->id); ?></td>
                        <td data-label="Name"><?php echo e($gateway->name); ?></td>
                        <td data-label="Roles"><?php echo e($gateway->role); ?></td>
                        <td data-label="Status">
                            <span class="badge <?php echo e((int) $gateway->status === 1 ? '' : 'off'); ?>">
                                <?php echo e((int) $gateway->status === 1 ? 'Enabled' : 'Disabled'); ?>

                            </span>
                        </td>
                        <td data-label="Created"><?php echo e($gateway->created_date); ?></td>
                        <td data-label="Action">
                            <div class="mobile-actions">
                                <a class="btn btn-secondary" href="<?php echo e(route('admin.gateways.manual.edit', $gateway->id)); ?>">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.gateways.manual.toggle', $gateway->id)); ?>" onsubmit="return confirm('Toggle this gateway status?');">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn"><?php echo e((int) $gateway->status === 1 ? 'Disable' : 'Enable'); ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="muted">No manual gateways found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/gateways/manual-index.blade.php ENDPATH**/ ?>