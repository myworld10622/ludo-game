<?php $__env->startSection('title', 'Audit Logs'); ?>
<?php $__env->startSection('heading', 'Audit Logs'); ?>
<?php $__env->startSection('subheading', 'Cross-domain operational and system event history'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Actor</th>
                        <th>Target</th>
                        <th>Source</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($log->event); ?></td>
                            <td><?php echo e($log->actor_type ? $log->actor_type.'#'.$log->actor_id : '-'); ?></td>
                            <td><?php echo e($log->auditable_type); ?>#<?php echo e($log->auditable_id); ?></td>
                            <td><?php echo e($log->source); ?></td>
                            <td><?php echo e(optional($log->created_at)->toDateTimeString()); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="muted">No audit logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;"><?php echo e($logs->links()); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/audit-logs/index.blade.php ENDPATH**/ ?>