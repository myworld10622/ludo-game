<?php $__env->startSection('title', 'Games'); ?>
<?php $__env->startSection('heading', 'Games'); ?>
<?php $__env->startSection('subheading', 'Manage game availability, routing, and tournament support'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel">
        <div class="header-row">
            <strong>Game Catalog</strong>
            <span class="muted">Quick control visibility, activity, and tournament availability from here.</span>
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
                        <th>Actions</th>
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
                            <td style="min-width:280px;">
                                <form method="POST" action="<?php echo e(route('admin.games.update', $game)); ?>" class="stack" style="gap:10px;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="is_visible" value="<?php echo e($game->is_visible ? 0 : 1); ?>">
                                    <input type="hidden" name="is_active" value="<?php echo e($game->is_active ? 1 : 0); ?>">
                                    <input type="hidden" name="tournaments_enabled" value="<?php echo e($game->tournaments_enabled ? 1 : 0); ?>">
                                    <button type="submit" class="btn <?php echo e($game->is_visible ? 'btn-secondary' : ''); ?>">
                                        <?php echo e($game->is_visible ? 'Hide In Lobby' : 'Show In Lobby'); ?>

                                    </button>
                                </form>
                                <div class="mobile-actions" style="margin-top:8px;">
                                    <form method="POST" action="<?php echo e(route('admin.games.update', $game)); ?>" style="flex:1;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="is_visible" value="<?php echo e($game->is_visible ? 1 : 0); ?>">
                                        <input type="hidden" name="is_active" value="<?php echo e($game->is_active ? 0 : 1); ?>">
                                        <input type="hidden" name="tournaments_enabled" value="<?php echo e($game->tournaments_enabled ? 1 : 0); ?>">
                                        <button type="submit" class="btn <?php echo e($game->is_active ? 'btn-secondary' : ''); ?>" style="width:100%;">
                                            <?php echo e($game->is_active ? 'Disable Game' : 'Enable Game'); ?>

                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.games.update', $game)); ?>" style="flex:1;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="is_visible" value="<?php echo e($game->is_visible ? 1 : 0); ?>">
                                        <input type="hidden" name="is_active" value="<?php echo e($game->is_active ? 1 : 0); ?>">
                                        <input type="hidden" name="tournaments_enabled" value="<?php echo e($game->tournaments_enabled ? 0 : 1); ?>">
                                        <button type="submit" class="btn <?php echo e($game->tournaments_enabled ? 'btn-secondary' : ''); ?>" style="width:100%;">
                                            <?php echo e($game->tournaments_enabled ? 'Tournament Off' : 'Tournament On'); ?>

                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="muted">No games found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;"><?php echo e($games->links()); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/games/index.blade.php ENDPATH**/ ?>