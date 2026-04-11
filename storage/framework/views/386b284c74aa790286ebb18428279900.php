<?php $__env->startSection('title', $mode === 'edit' ? 'Edit Agent' : 'Add Agent'); ?>
<?php $__env->startSection('heading', $mode === 'edit' ? 'Edit Agent' : 'Add Agent'); ?>
<?php $__env->startSection('subheading', 'Manage agent profile'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel">
    <form method="POST" action="<?php echo e($mode === 'edit' ? route('admin.agents.update', $agent->id ?? 0) : route('admin.agents.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if($mode === 'edit'): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <div class="split-2">
            <div>
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo e(old('first_name', $agent->first_name ?? '')); ?>" required>
            </div>
            <div>
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo e(old('last_name', $agent->last_name ?? '')); ?>">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email_id" value="<?php echo e(old('email_id', $agent->email_id ?? '')); ?>" required>
            </div>
            <div>
                <label>Mobile</label>
                <input type="text" name="mobile" value="<?php echo e(old('mobile', $agent->mobile ?? '')); ?>">
            </div>
            <div>
                <label>Password</label>
                <input type="text" name="password" value="<?php echo e(old('password', $agent->password ?? '')); ?>" <?php echo e($mode === 'create' ? 'required' : ''); ?>>
            </div>
            <div>
                <label>Added By (Distributor ID)</label>
                <input type="number" name="addedby" value="<?php echo e(old('addedby', $agent->addedby ?? '')); ?>">
            </div>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit"><?php echo e($mode === 'edit' ? 'Update' : 'Create'); ?></button>
            <a class="btn btn-secondary" href="<?php echo e(route('admin.agents.index')); ?>">Cancel</a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/agents/form.blade.php ENDPATH**/ ?>