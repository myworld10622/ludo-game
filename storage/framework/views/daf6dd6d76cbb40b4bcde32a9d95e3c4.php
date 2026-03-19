<?php $__env->startSection('title', 'Users'); ?>
<?php $__env->startSection('heading', 'Users'); ?>
<?php $__env->startSection('subheading', 'Monitor registered users and account state'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($user->username); ?></td>
                            <td><?php echo e($user->email ?: '-'); ?></td>
                            <td><?php echo e($user->mobile ?: '-'); ?></td>
                            <td><span class="badge <?php echo e($user->is_active && ! $user->is_banned ? '' : 'off'); ?>"><?php echo e($user->is_active && ! $user->is_banned ? 'Active' : 'Restricted'); ?></span></td>
                            <td><?php echo e(optional($user->last_login_at)->toDateTimeString() ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;"><?php echo e($users->links()); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/users/index.blade.php ENDPATH**/ ?>