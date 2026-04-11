<?php $__env->startSection('title', 'Wallet Transactions'); ?>
<?php $__env->startSection('heading', 'Wallet Transactions'); ?>
<?php $__env->startSection('subheading', 'Operational ledger view for wallet activity'); ?>

<?php $__env->startSection('content'); ?>
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Txn</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($transaction->transaction_uuid); ?></td>
                            <td><?php echo e($transaction->user?->username ?: '-'); ?></td>
                            <td><?php echo e($transaction->type); ?></td>
                            <td><?php echo e($transaction->amount); ?> <?php echo e($transaction->currency); ?></td>
                            <td><?php echo e($transaction->status); ?></td>
                            <td><?php echo e(optional($transaction->created_at)->toDateTimeString()); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="muted">No wallet transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;"><?php echo e($transactions->links()); ?></div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/wallet-transactions/index.blade.php ENDPATH**/ ?>