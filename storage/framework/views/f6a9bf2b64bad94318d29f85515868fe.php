<?php $__env->startSection('title', 'Games'); ?>
<?php $__env->startSection('heading', 'Games'); ?>
<?php $__env->startSection('subheading', 'Manage game availability, routing, and tournament support'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel">
        <div class="header-row">
            <strong>Game Catalog</strong>
            <span class="muted">Create and update via admin API endpoints.</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Visibility</th>
                        <th>Activity</th>
                        <th>Tournaments</th>
                        <th>Client Route</th>
                        <th>Socket Namespace</th>
                        <th>Sort</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $games; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <strong><?php echo e($game->name); ?></strong><br>
                                <span class="muted"><?php echo e($game->code); ?></span>
                            </td>
                            <td><span class="badge <?php echo e($game->is_visible ? '' : 'off'); ?>"><?php echo e($game->is_visible ? 'Visible' : 'Hidden'); ?></span></td>
                            <td><span class="badge <?php echo e($game->is_active ? '' : 'off'); ?>"><?php echo e($game->is_active ? 'Active' : 'Disabled'); ?></span></td>
                            <td><span class="badge <?php echo e($game->tournaments_enabled ? '' : 'off'); ?>"><?php echo e($game->tournaments_enabled ? 'Enabled' : 'Off'); ?></span></td>
                            <td><?php echo e($game->client_route ?: '-'); ?></td>
                            <td><?php echo e($game->socket_namespace ?: '-'); ?></td>
                            <td><?php echo e($game->sort_order); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="muted">No games found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;"><?php echo e($games->links()); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/games/index.blade.php ENDPATH**/ ?>