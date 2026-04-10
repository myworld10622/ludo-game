<?php $__env->startSection('title', 'Users'); ?>
<?php $__env->startSection('heading', 'Users'); ?>
<?php $__env->startSection('subheading', 'Registered users — wallet balance, match history, and account detail'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .user-code { font-family: monospace; font-size: 13px; letter-spacing: 1px;
                 background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    .wallet-bal { font-weight: 700; color: #065f46; }
    .match-count-btn { background: none; border: none; cursor: pointer; color: #2563eb;
                       font-weight: 700; font-size: 14px; text-decoration: underline; padding: 0; }
    .match-count-btn:hover { color: #1d4ed8; }
    /* Modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
                     z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: #fff; border-radius: 16px; width: 92%; max-width: 720px;
                 max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #e5e7eb;
                    display: flex; justify-content: space-between; align-items: center; }
    .modal-title { font-size: 16px; font-weight: 700; }
    .modal-close { background: none; border: none; cursor: pointer; font-size: 20px;
                   color: #6b7280; line-height: 1; }
    .modal-body { overflow-y: auto; padding: 16px 20px; }
    .modal-body table { width: 100%; border-collapse: collapse; }
    .modal-body th, .modal-body td { text-align: left; padding: 10px 8px;
                                     border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .modal-body th { color: #6b7280; font-size: 12px; font-weight: 600; }
    .modal-loading { text-align: center; padding: 40px; color: #9ca3af; }
    .search-bar { display: flex; gap: 10px; margin-bottom: 16px; }
    .search-bar input { flex: 1; border: 1px solid #d1d5db; border-radius: 10px;
                        padding: 9px 14px; font-size: 14px; }
    .search-bar button { padding: 9px 18px; background: #0f766e; color: #fff;
                         border: 0; border-radius: 10px; cursor: pointer; font-size: 14px; }
    .btn-detail { display: inline-block; padding: 4px 10px; background: #e0f2fe; color: #0369a1;
                  border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; }
    .btn-detail:hover { background: #bae6fd; }
</style>


<form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="search-bar">
    <input type="text" name="q" value="<?php echo e(request('q')); ?>"
           placeholder="Search by username, email or 8-digit user code…">
    <button type="submit">Search</button>
    <?php if(request('q')): ?>
        <a href="<?php echo e(route('admin.users.index')); ?>" style="padding:9px 14px;color:#6b7280;">Clear</a>
    <?php endif; ?>
</form>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User Code</th>
                    <th>Username</th>
                    <th>Email / Mobile</th>
                    <th>Wallet</th>
                    <th>Matches</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="user-code"><?php echo e($user->user_code); ?></span></td>
                        <td style="font-weight:600;"><?php echo e($user->username); ?></td>
                        <td class="muted" style="font-size:13px;">
                            <?php echo e($user->email ?: ''); ?>

                            <?php if($user->email && $user->mobile): ?><br><?php endif; ?>
                            <?php echo e($user->mobile ?: ''); ?>

                            <?php if(!$user->email && !$user->mobile): ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if($user->primaryWallet): ?>
                                <span class="wallet-bal">₹<?php echo e(number_format($user->primaryWallet->balance, 2)); ?></span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($user->matches_played > 0): ?>
                                <button class="match-count-btn"
                                        onclick="loadMatches(<?php echo e($user->id); ?>, '<?php echo e($user->username); ?>')">
                                    <?php echo e($user->matches_played); ?>

                                </button>
                            <?php else: ?>
                                <span class="muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo e($user->is_active && !$user->is_banned ? '' : 'off'); ?>">
                                <?php echo e($user->is_banned ? 'Banned' : ($user->is_active ? 'Active' : 'Inactive')); ?>

                            </span>
                        </td>
                        <td class="muted" style="font-size:12px;">
                            <?php echo e($user->last_login_at?->format('M d, Y H:i') ?? '—'); ?>

                        </td>
                        <td>
                            <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn-detail">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="muted">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:16px 18px;"><?php echo e($users->links()); ?></div>
</div>


<div class="modal-overlay" id="matchModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">Match History</span>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="modal-loading">Loading…</div>
        </div>
    </div>
</div>

<script>
function loadMatches(userId, username) {
    document.getElementById('modalTitle').textContent = username + ' — Match History';
    document.getElementById('modalBody').innerHTML = '<div class="modal-loading">Loading…</div>';
    document.getElementById('matchModal').classList.add('open');

    fetch('/admin/users/' + userId + '/matches', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => { document.getElementById('modalBody').innerHTML = html; })
    .catch(() => { document.getElementById('modalBody').innerHTML = '<p style="color:red;padding:20px;">Failed to load.</p>'; });
}

function closeModal() {
    document.getElementById('matchModal').classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/users/index.blade.php ENDPATH**/ ?>