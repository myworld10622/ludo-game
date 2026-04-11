<?php $__env->startSection('title', 'Support Tickets'); ?>
<?php $__env->startSection('heading', 'Support Tickets'); ?>
<?php $__env->startSection('subheading', 'Review user tickets, respond, and manage approval discussions'); ?>

<?php $__env->startSection('content'); ?>
<?php ($activeTicket = $ticket ?? $tickets->first()); ?>
<div class="split-wide-narrow">
    <div class="panel">
        <div class="section-title" style="font-size:18px;font-weight:700;margin-bottom:14px;">Ticket Inbox</div>
        <div class="stack">
            <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route('admin.support.show', $item)); ?>" style="display:block;padding:14px;border:1px solid var(--line);border-radius:16px;background:<?php echo e($activeTicket && $activeTicket->id === $item->id ? 'linear-gradient(180deg,#eef4ff,#fff)' : '#fff'); ?>;">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-weight:800;"><?php echo e($item->subject); ?></div>
                            <div class="muted" style="font-size:12px;"><?php echo e($item->user?->username); ?> · <?php echo e($item->user?->user_code); ?> · <?php echo e(ucfirst(str_replace('_', ' ', $item->status))); ?></div>
                            <?php if($item->tournament): ?>
                                <div class="muted" style="font-size:12px;">Tournament: <?php echo e($item->tournament->name); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge"><?php echo e(ucfirst($item->priority)); ?></span>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">No support tickets available.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <?php if($activeTicket): ?>
            <div class="header-row">
                <div>
                    <div style="font-size:22px;font-weight:800;"><?php echo e($activeTicket->subject); ?></div>
                    <div class="muted" style="font-size:13px;">
                        <?php echo e($activeTicket->user?->username); ?> · <?php echo e($activeTicket->user?->user_code); ?>

                        <?php if($activeTicket->tournament): ?>
                            · <?php echo e($activeTicket->tournament->name); ?>

                        <?php endif; ?>
                    </div>
                </div>
                <form method="POST" action="<?php echo e(route('admin.support.status', $activeTicket)); ?>" style="display:flex;gap:8px;align-items:center;">
                    <?php echo csrf_field(); ?>
                    <select name="status" style="min-width:160px;">
                        <?php $__currentLoopData = ['open' => 'Open', 'pending_user' => 'Pending User', 'closed' => 'Closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php echo e($activeTicket->status === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit" class="btn btn-secondary">Update</button>
                </form>
            </div>

            <div class="stack" style="margin:16px 0;">
                <?php $__currentLoopData = $activeTicket->messages->sortBy('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($isAdmin = $message->sender_type === 'admin'); ?>
                    <div style="padding:14px;border-radius:16px;border:1px solid var(--line);background:<?php echo e($isAdmin ? 'linear-gradient(180deg,#eef4ff,#fff)' : 'linear-gradient(180deg,#f8fbff,#fff)'); ?>;">
                        <div style="font-size:12px;font-weight:800;margin-bottom:6px;color:<?php echo e($isAdmin ? 'var(--brand-dark)' : 'var(--accent)'); ?>;">
                            <?php echo e($isAdmin ? ($message->senderAdmin?->name ?? 'Admin') : ($message->senderUser?->username ?? 'User')); ?>

                            · <?php echo e($message->created_at?->format('d M Y, h:i A')); ?>

                        </div>
                        <div style="white-space:pre-wrap;line-height:1.7;"><?php echo e($message->message); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <form method="POST" action="<?php echo e(route('admin.support.reply', $activeTicket)); ?>" class="stack">
                <?php echo csrf_field(); ?>
                <div><label>Reply to user</label><textarea name="message" required></textarea></div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        <?php else: ?>
            <div class="muted">Select a ticket to start chat with the user.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/support/index.blade.php ENDPATH**/ ?>