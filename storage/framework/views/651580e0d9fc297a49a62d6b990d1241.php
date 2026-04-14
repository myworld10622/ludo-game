<?php $__env->startSection('title', 'Support Chat'); ?>
<?php $__env->startSection('heading', 'Support Chat'); ?>
<?php $__env->startSection('subheading', 'Raise a ticket, discuss tournament approval, and reply to admin'); ?>

<?php $__env->startSection('content'); ?>
<?php ($activeTicket = $ticket ?? $tickets->first()); ?>
<div class="split-wide-narrow">
    <div class="panel">
        <div class="section-title">Create Ticket</div>
        <form method="POST" action="<?php echo e(route('panel.support.store')); ?>" class="stack-compact">
            <?php echo csrf_field(); ?>
            <div><label>Subject</label><input name="subject" value="<?php echo e(old('subject')); ?>" required></div>
            <div><label>Category</label><select name="category"><option value="general">General</option><option value="tournament_approval">Tournament Approval</option><option value="payment">Payment</option><option value="technical">Technical</option></select></div>
            <div><label>Tournament</label><select name="tournament_id"><option value="">Select tournament (optional)</option><?php $__currentLoopData = $tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($tournament->id); ?>"><?php echo e($tournament->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div><label>Message</label><textarea name="message" required><?php echo e(old('message')); ?></textarea></div>
            <button type="submit" class="btn">Create Ticket</button>
        </form>

        <div class="section-title" style="margin-top:18px;">My Tickets</div>
        <div class="stack-compact">
            <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route('panel.support.show', $item)); ?>" style="display:block;padding:14px;border:1px solid var(--line);border-radius:14px;background:<?php echo e($activeTicket && $activeTicket->id === $item->id ? 'linear-gradient(180deg,#fff5ea,#fff)' : '#fff'); ?>;">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-weight:700;"><?php echo e($item->subject); ?></div>
                            <div class="muted" style="font-size:12px;"><?php echo e(ucfirst(str_replace('_', ' ', $item->status))); ?> · <?php echo e($item->last_message_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                        </div>
                        <span class="badge"><?php echo e(ucfirst($item->priority)); ?></span>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">No support tickets yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <?php if($activeTicket): ?>
            <div class="card-head">
                <div>
                    <div class="card-title"><?php echo e($activeTicket->subject); ?></div>
                    <div class="muted" style="font-size:13px;">
                        <?php echo e(ucfirst(str_replace('_', ' ', $activeTicket->status))); ?>

                        <?php if($activeTicket->tournament): ?>
                            · <?php echo e($activeTicket->tournament->name); ?>

                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge"><?php echo e(ucfirst($activeTicket->category)); ?></span>
            </div>

            <div class="stack-compact" style="margin:16px 0;">
                <?php $__currentLoopData = $activeTicket->messages->sortBy('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($isUser = $message->sender_type === 'user'); ?>
                    <div style="padding:14px;border-radius:16px;border:1px solid var(--line);background:<?php echo e($isUser ? 'linear-gradient(180deg,#fff8f1,#fff)' : 'linear-gradient(180deg,#eefaf7,#fff)'); ?>;">
                        <div style="font-size:12px;font-weight:700;margin-bottom:6px;color:<?php echo e($isUser ? 'var(--brand-dark)' : 'var(--accent)'); ?>;">
                            <?php echo e($isUser ? 'You' : ($message->senderAdmin?->name ?? 'Admin')); ?>

                            · <?php echo e($message->created_at?->format('d M Y, h:i A')); ?>

                        </div>
                        <div style="white-space:pre-wrap;line-height:1.7;"><?php echo e($message->message); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <form method="POST" action="<?php echo e(route('panel.support.reply', $activeTicket)); ?>" class="stack-compact">
                <?php echo csrf_field(); ?>
                <div><label>Reply</label><textarea name="message" required></textarea></div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        <?php else: ?>
            <div class="muted">Select or create a support ticket to start chatting with admin.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/user/support/index.blade.php ENDPATH**/ ?>