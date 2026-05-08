<?php $__env->startSection('title', 'User Panel'); ?>
<?php $__env->startSection('heading', 'Tournament User Panel'); ?>
<?php $__env->startSection('subheading', 'Manage your own tournaments, reports, live matches, and support'); ?>

<?php $__env->startSection('content'); ?>
    <?php ($permissions = $user->panelPermissions()); ?>

    <div class="panel" style="margin-bottom:24px;background:linear-gradient(135deg,#4a2210,#a6461f 48%, #17806d 100%);color:#fff;border:none;">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.16);color:#fff;">Web Panel Active</div>
                <h2 style="margin:14px 0 8px;font-size:32px;">Create and manage your tournaments from the browser</h2>
                <p style="color:rgba(255,255,255,0.84);max-width:760px;line-height:1.7;">Track your live tournaments, running matches, approval updates, and support chat from a single dashboard.</p>
            </div>
            <div class="live-callout">
                <span class="live-pill">LIVE</span>
                <div style="font-size:28px;font-weight:800;"><?php echo e($panelStats['live_tournaments'] ?? 0); ?></div>
                <div style="font-size:13px;opacity:0.85;">Active tournaments now</div>
            </div>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="stat-label">User ID</div><div class="stat-value"><?php echo e($user->user_code); ?></div></div>
        <div class="stat-card"><div class="stat-label">Username</div><div class="stat-value" style="font-size:22px;"><?php echo e($user->username); ?></div></div>
        <div class="stat-card"><div class="stat-label">Email</div><div class="stat-value" style="font-size:18px;"><?php echo e($user->email ?: 'Not set'); ?></div></div>
        <div class="stat-card"><div class="stat-label">Wallet</div><div class="stat-value"><?php echo e(number_format((float) ($user->primaryWallet?->balance ?? 0), 2)); ?></div></div>
        <div class="stat-card"><div class="stat-label">Pending Approval</div><div class="stat-value"><?php echo e($panelStats['pending_tournaments'] ?? 0); ?></div></div>
        <div class="stat-card"><div class="stat-label">Support Tickets</div><div class="stat-value"><?php echo e($panelStats['support_tickets'] ?? 0); ?></div></div>
    </div>

    <div class="highlight-grid" style="margin-bottom:24px;">
        <div class="highlight-card live-card">
            <div class="highlight-top">
                <span class="live-pill">LIVE</span>
                <a href="<?php echo e(route('panel.tournaments.index')); ?>" class="text-link">Open Tournaments</a>
            </div>
            <div class="highlight-value"><?php echo e($panelStats['live_tournaments'] ?? 0); ?></div>
            <div class="highlight-label">Live Or Registration Open Tournaments</div>
            <div class="highlight-sub"><?php echo e($panelStats['running_matches'] ?? 0); ?> running matches are active under your tournaments.</div>
        </div>
        <div class="highlight-card running-card">
            <div class="highlight-top">
                <span class="running-pill">RUNNING</span>
                <a href="<?php echo e(route('panel.matches.index')); ?>" class="text-link">Open Match Monitor</a>
            </div>
            <div class="highlight-value"><?php echo e($panelStats['running_matches'] ?? 0); ?></div>
            <div class="highlight-label">Running / Waiting Matches</div>
            <div class="highlight-sub"><?php echo e($panelStats['completed_tournaments'] ?? 0); ?> tournaments already completed in your panel.</div>
        </div>
    </div>

    <div class="split-2" style="margin-bottom:24px;">
        <div class="panel">
            <div class="header-row">
                <strong>Full User Details</strong>
                <a href="<?php echo e(route('panel.support.index')); ?>" class="text-link">Open Support Chat</a>
            </div>
            <div class="details-grid">
                <div><span>User ID</span><strong><?php echo e($user->user_code); ?></strong></div>
                <div><span>Username</span><strong><?php echo e($user->username); ?></strong></div>
                <div><span>Email</span><strong><?php echo e($user->email ?: 'Not set'); ?></strong></div>
                <div><span>Mobile</span><strong><?php echo e($user->mobile ?: 'Not set'); ?></strong></div>
                <div><span>Wallet Balance</span><strong>₹<?php echo e(number_format((float) ($user->primaryWallet?->balance ?? 0), 2)); ?></strong></div>
                <div><span>Last Login</span><strong><?php echo e($user->last_login_at?->format('d M Y, h:i A') ?? 'First session'); ?></strong></div>
                <div><span>Pending Tournaments</span><strong><?php echo e($panelStats['pending_tournaments'] ?? 0); ?></strong></div>
                <div><span>Rejected Tournaments</span><strong><?php echo e($panelStats['rejected_tournaments'] ?? 0); ?></strong></div>
                <div><span>Support Tickets</span><strong><?php echo e($panelStats['support_tickets'] ?? 0); ?></strong></div>
                <div><span>Modules Enabled</span><strong><?php echo e(collect($permissions)->filter()->count()); ?></strong></div>
            </div>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Tournament Approval Alerts</strong>
                <a href="<?php echo e(route('panel.support.index')); ?>" class="text-link">Support Chat</a>
            </div>
            <div class="stack-compact">
                <?php $__empty_1 = true; $__currentLoopData = $recentTournamentAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div style="padding:14px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(180deg,#fffaf4 0%, #fff 100%);">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:700;"><?php echo e($alert->name); ?></div>
                                <div class="muted" style="font-size:12px;">Created <?php echo e($alert->created_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                                <?php if($alert->rejection_reason): ?>
                                    <div style="margin-top:8px;color:#b42318;font-size:13px;"><strong>Rejected:</strong> <?php echo e($alert->rejection_reason); ?></div>
                                <?php else: ?>
                                    <div style="margin-top:8px;color:#b54708;font-size:13px;"><strong>Pending:</strong> Waiting for admin review and approval.</div>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo e(route('panel.tournaments.report', $alert)); ?>" class="btn btn-secondary">View</a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="muted">No approval alerts right now.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:24px;">
        <div class="header-row">
            <strong>Live And Running Tournaments</strong>
            <a href="<?php echo e(route('panel.tournaments.index')); ?>" class="text-link">Manage All</a>
        </div>
        <div class="cards-grid">
            <?php $__empty_1 = true; $__currentLoopData = $liveTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="panel tournament-card" style="background:linear-gradient(180deg,#fff8ef 0%, #fff 100%);">
                    <div class="card-head">
                        <div>
                            <div class="card-title"><?php echo e($tournament->name); ?></div>
                            <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_', ' ', $tournament->format))); ?></div>
                        </div>
                        <span class="live-pill"><?php echo e($tournament->status === 'in_progress' ? 'RUNNING' : 'LIVE'); ?></span>
                    </div>
                    <div class="metrics-grid">
                        <div><span>Players</span><strong><?php echo e($tournament->registrations_count); ?>/<?php echo e($tournament->max_players); ?></strong></div>
                        <div><span>Running Matches</span><strong><?php echo e($tournament->running_matches_count); ?></strong></div>
                        <div><span>Completed Matches</span><strong><?php echo e($tournament->completed_matches_count); ?></strong></div>
                    </div>
                    <div class="card-actions">
                        <a href="<?php echo e(route('panel.tournaments.report', $tournament)); ?>" class="btn">Open Report</a>
                        <a href="<?php echo e(route('panel.matches.index')); ?>" class="btn btn-secondary">Match Monitor</a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">No live or running tournaments right now.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="split-main-aside">
        <div class="panel">
            <div class="header-row">
                <strong>Recent Tournament Details</strong>
                <a href="<?php echo e(route('panel.tournaments.index')); ?>" class="text-link">Open Tournament List</a>
            </div>
            <div class="table-wrap responsive-table">
                <table>
                    <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $recentOwnedTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Tournament">
                                <strong><?php echo e($tournament->name); ?></strong>
                                <div class="muted" style="font-size:12px;"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                            </td>
                            <td data-label="Status">
                                <?php if(in_array($tournament->status, ['registration_open', 'in_progress'])): ?>
                                    <span class="live-pill"><?php echo e($tournament->status === 'in_progress' ? 'RUNNING' : 'LIVE'); ?></span>
                                <?php else: ?>
                                    <span class="badge"><?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Players"><?php echo e($tournament->registrations_count); ?>/<?php echo e($tournament->max_players); ?></td>
                            <td data-label="Matches"><?php echo e($tournament->completed_matches_count); ?> done · <?php echo e($tournament->running_matches_count); ?> running</td>
                            <td data-label="Action"><a href="<?php echo e(route('panel.tournaments.report', $tournament)); ?>" class="text-link">View Report</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="muted">No tournaments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="stat-label">Enabled Modules</div>
            <div style="display:grid;gap:10px;">
                <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $enabled): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid var(--line-dim);border-radius:12px;background:var(--card2);">
                        <span style="font-weight:600;color:var(--text);"><?php echo e(ucwords(str_replace('_', ' ', $key))); ?></span>
                        <span class="badge <?php echo e($enabled ? '' : 'badge-warn'); ?>"><?php echo e($enabled ? 'Enabled' : 'Hidden'); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/user/panel/index.blade.php ENDPATH**/ ?>