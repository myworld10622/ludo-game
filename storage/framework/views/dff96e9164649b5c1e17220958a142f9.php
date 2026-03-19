<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('heading', 'Dashboard'); ?>
<?php $__env->startSection('subheading', 'Operational overview for the gaming backend'); ?>

<?php $__env->startSection('content'); ?>
    <div class="stats">
        <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="stat-card">
                <div class="stat-label"><?php echo e(str($label)->replace('_', ' ')->title()); ?></div>
                <div class="stat-value"><?php echo e($value); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Audit Logs</strong>
            <a class="muted" href="<?php echo e(route('admin.audit-logs.index')); ?>">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Source</th>
                        <th>Target</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recent_audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($log->event); ?></td>
                            <td><?php echo e($log->source); ?></td>
                            <td><?php echo e($log->auditable_type); ?>#<?php echo e($log->auditable_id); ?></td>
                            <td><?php echo e(optional($log->created_at)->toDateTimeString()); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="muted">No audit activity available yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/dashboard/index.blade.php ENDPATH**/ ?>