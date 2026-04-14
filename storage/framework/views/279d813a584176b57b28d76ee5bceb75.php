<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('heading', 'Dashboard'); ?>
<?php $__env->startSection('subheading', 'Advanced control room for tournaments, revenue, users, and reports'); ?>

<?php $__env->startSection('content'); ?>
<?php ($liveOrRunningTournaments = $recent_tournaments->filter(fn ($tournament) => in_array($tournament->status, ['registration_open', 'in_progress']))->values()); ?>
<?php ($runningTournamentCount = $recent_tournaments->where('status', 'in_progress')->count()); ?>
<div class="stack">
    <div class="panel" style="background:linear-gradient(135deg,rgba(255,215,0,0.14),rgba(255,149,0,0.12) 48%,rgba(6,214,160,0.12) 100%);border-color:rgba(255,215,0,0.2);">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Admin Command Center</div>
                <h2 style="margin:12px 0 8px;font-size:32px;">Live business snapshot with direct tournament reports</h2>
                <div style="max-width:900px;color:rgba(255,255,255,0.86);line-height:1.7;">
                    Track platform activity, tournament status, payouts, recent report openings, and high-value user accounts from one dashboard.
                </div>
            </div>
            <div class="live-callout">
                <span class="live-pill">LIVE</span>
                <div style="font-size:28px;font-weight:800;margin-top:10px;"><?php echo e($tournamentStats['live']); ?></div>
                <div style="font-size:13px;opacity:0.85;">Active tournaments now</div>
            </div>
        </div>
    </div>

    <div class="highlight-grid">
        <div class="highlight-card live-card">
            <div class="highlight-top">
                <span class="live-pill">LIVE</span>
                <a href="<?php echo e(route('admin.tournaments.index')); ?>" class="text-link">Open Tournaments</a>
            </div>
            <div class="highlight-value"><?php echo e($tournamentStats['live']); ?></div>
            <div class="highlight-label">Live Or Registration Open Tournaments</div>
            <div class="highlight-sub"><?php echo e($tournamentStats['pending_approval']); ?> tournaments are still waiting for admin approval.</div>
        </div>
        <div class="highlight-card running-card">
            <div class="highlight-top">
                <span class="running-pill">RUNNING</span>
                <a href="<?php echo e(route('admin.tournaments.matches')); ?>" class="text-link">Open Match Monitor</a>
            </div>
            <div class="highlight-value"><?php echo e($runningTournamentCount); ?></div>
            <div class="highlight-label">Tournaments Currently In Progress</div>
            <div class="highlight-sub"><?php echo e($tournamentStats['completed']); ?> tournaments are already completed across the platform.</div>
        </div>
    </div>

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
            <strong>Live And Running Tournaments</strong>
            <a class="text-link" href="<?php echo e(route('admin.tournaments.index')); ?>">Manage All</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;">
            <?php $__empty_1 = true; $__currentLoopData = $liveOrRunningTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="panel" style="background:var(--card2);border-color:rgba(255,215,0,0.15);">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                        <div>
                            <div style="font-size:18px;font-weight:800;color:var(--text);"><?php echo e($tournament->name); ?></div>
                            <div class="muted" style="font-size:12px;">
                                <?php echo e(ucfirst($tournament->creator_type)); ?>

                                <?php if($tournament->creator_type === 'user' && $tournament->creator): ?>
                                    · <?php echo e($tournament->creator->username); ?> (<?php echo e($tournament->creator->user_code); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="<?php echo e($tournament->status === 'in_progress' ? 'running-pill' : 'live-pill'); ?>">
                            <?php echo e($tournament->status === 'in_progress' ? 'RUNNING' : 'LIVE'); ?>

                        </span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px;">
                        <div><div class="stat-label">Players</div><div style="font-weight:800;color:var(--text);"><?php echo e($tournament->registrations_count); ?>/<?php echo e($tournament->max_players); ?></div></div>
                        <div><div class="stat-label">Running</div><div style="font-weight:800;color:var(--text);"><?php echo e($tournament->pending_matches_count); ?></div></div>
                        <div><div class="stat-label">Completed</div><div style="font-weight:800;color:var(--text);"><?php echo e($tournament->completed_matches_count); ?></div></div>
                    </div>
                    <div class="mobile-actions" style="margin-top:14px;">
                        <a class="btn" href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>">Open Report</a>
                        <a class="btn btn-secondary" href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>">Edit</a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">No live or running tournaments right now.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Pending Tournament Approval Alerts</strong>
            <a class="muted" href="<?php echo e(route('admin.tournaments.index')); ?>">Open tournament queue</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pending_approval_tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Tournament">
                                <strong><?php echo e($tournament->name); ?></strong>
                                <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_',' ', $tournament->format))); ?></div>
                            </td>
                            <td data-label="User"><?php echo e($tournament->creator?->username ?? 'User'); ?> · <?php echo e($tournament->creator?->user_code ?? '—'); ?></td>
                            <td data-label="Created"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></td>
                            <td data-label="Status"><span class="badge">Pending Approval</span></td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;margin-right:4px;" href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>">Review Details</a>
                                <a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;margin-right:4px;" href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.tournaments.approve', $tournament)); ?>" style="display:inline;"><?php echo csrf_field(); ?><button type="submit" class="btn" style="font-size:12px;padding:6px 10px;margin-right:4px;">Approve</button></form>
                                <form method="POST" action="<?php echo e(route('admin.tournaments.reject', $tournament)); ?>" style="display:inline;" onsubmit="return confirm('Reject this tournament? A support ticket will be sent to the user.')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="reason" value="Tournament needs admin review updates before approval. Please check support ticket for details.">
                                    <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="muted">No pending tournament approval alerts right now.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="split-main-aside">
        <div class="panel">
            <div class="header-row">
                <strong>Tournament Report Snapshot</strong>
                <a class="muted" href="<?php echo e(route('admin.tournaments.index')); ?>">Open tournaments</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Live</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);"><?php echo e($tournamentStats['live']); ?></div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Completed</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);"><?php echo e($tournamentStats['completed']); ?></div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Drafts</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);"><?php echo e($tournamentStats['drafts']); ?></div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">User Created</div>
                    <div style="font-size:24px;font-weight:800;color:var(--green);"><?php echo e($tournamentStats['user_created']); ?></div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Admin Created</div>
                    <div style="font-size:24px;font-weight:800;color:var(--green);"><?php echo e($tournamentStats['admin_created']); ?></div>
                </div>
                <div style="padding:14px;border:1px solid rgba(230,57,70,0.2);border-radius:14px;background:rgba(230,57,70,0.06);">
                    <div class="stat-label">Pending Approval</div>
                    <div style="font-size:24px;font-weight:800;color:var(--red);"><?php echo e($tournamentStats['pending_approval']); ?></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Revenue Snapshot</strong>
                <span class="muted">Platform financial view</span>
            </div>
            <div style="display:grid;gap:12px;">
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Wallet Volume</div>
                    <div style="font-size:26px;font-weight:800;color:var(--gold);">₹<?php echo e(number_format($revenue['wallet_volume'], 2)); ?></div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Active Wallet Balance</div>
                    <div style="font-size:26px;font-weight:800;color:var(--green);">₹<?php echo e(number_format($revenue['active_wallet_balance'], 2)); ?></div>
                </div>
                <div style="padding:14px;border:1px solid rgba(255,149,0,0.2);border-radius:14px;background:rgba(255,149,0,0.06);">
                    <div class="stat-label">Tournament Platform Fee</div>
                    <div style="font-size:26px;font-weight:800;color:#FF9500;">₹<?php echo e(number_format($revenue['tournament_platform_fee'], 2)); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="split-main-aside">
        <div class="panel">
            <div class="header-row">
                <strong>Recent Tournament Reports</strong>
                <a class="muted" href="<?php echo e(route('admin.tournaments.index')); ?>">See all tournaments</a>
            </div>
            <div class="table-wrap responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Players</th>
                            <th>Matches</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recent_tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td data-label="Tournament">
                                    <strong><?php echo e($tournament->name); ?></strong>
                                    <div class="muted" style="font-size:12px;"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></div>
                                </td>
                                <td data-label="Owner">
                                    <?php echo e(ucfirst($tournament->creator_type)); ?>

                                    <?php if($tournament->creator_type === 'user' && $tournament->creator): ?>
                                        <div class="muted" style="font-size:12px;"><?php echo e($tournament->creator->username); ?> · <?php echo e($tournament->creator->user_code); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status"><span class="badge"><?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?></span></td>
                                <td data-label="Players"><?php echo e($tournament->registrations_count); ?>/<?php echo e($tournament->max_players); ?></td>
                                <td data-label="Matches"><?php echo e($tournament->completed_matches_count); ?> complete · <?php echo e($tournament->pending_matches_count); ?> pending</td>
                                <td data-label="Report"><a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;" href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>">Open Report</a></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" class="muted">No tournament activity yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Top User Activity</strong>
                <a class="muted" href="<?php echo e(route('admin.users.index')); ?>">Open users</a>
            </div>
            <div style="display:grid;gap:10px;">
                <?php $__empty_1 = true; $__currentLoopData = $top_users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:800;color:var(--text);"><?php echo e($user->username); ?></div>
                                <div class="muted" style="font-size:12px;"><?php echo e($user->user_code); ?> · <?php echo e($user->email ?: ($user->mobile ?: 'No contact')); ?></div>
                            </div>
                            <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="badge">Open</a>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;">
                            <div><div class="stat-label">Registrations</div><div style="font-weight:800;color:var(--text);"><?php echo e($user->tournament_registrations_count); ?></div></div>
                            <div><div class="stat-label">Created</div><div style="font-weight:800;color:var(--text);"><?php echo e($user->created_tournaments_count); ?></div></div>
                            <div><div class="stat-label">Wallet</div><div style="font-weight:800;color:var(--green);">₹<?php echo e(number_format((float) ($user->primaryWallet?->balance ?? 0), 0)); ?></div></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="muted">No user activity available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Audit Logs</strong>
            <a class="muted" href="<?php echo e(route('admin.audit-logs.index')); ?>">View all</a>
        </div>
        <div class="table-wrap responsive-table">
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
                            <td data-label="Event"><?php echo e($log->event); ?></td>
                            <td data-label="Source"><?php echo e($log->source); ?></td>
                            <td data-label="Target"><?php echo e($log->auditable_type); ?>#<?php echo e($log->auditable_id); ?></td>
                            <td data-label="Time"><?php echo e(optional($log->created_at)->toDateTimeString()); ?></td>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/dashboard/index.blade.php ENDPATH**/ ?>