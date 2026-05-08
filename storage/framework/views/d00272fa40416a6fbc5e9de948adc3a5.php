<?php $__env->startSection('title', $tournament->name . ' Report'); ?>
<?php $__env->startSection('heading', 'Tournament Report'); ?>
<?php $__env->startSection('subheading', $tournament->name . ' · Full report, winners, registrations, and financials'); ?>

<?php $__env->startSection('content'); ?>
<div class="panel page-hero" style="margin-bottom:24px;">
    <div>
        <div class="eyebrow">Report Overview</div>
        <h2 style="margin:8px 0 10px;font-size:30px;"><?php echo e($tournament->name); ?></h2>
        <p class="muted" style="line-height:1.7;margin:0;max-width:860px;">
            Created <span data-utc-time="<?php echo e($tournament->created_at?->toIso8601String()); ?>"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></span> ·
            Status <?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?> ·
            <?php echo e(ucfirst($tournament->type)); ?> ·
            <?php echo e(ucwords(str_replace('_', ' ', $tournament->format))); ?>

        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="<?php echo e(route('panel.tournaments.export', $tournament)); ?>" class="btn btn-secondary">Download Excel</a>
        <a href="<?php echo e(route('panel.tournaments.print', ['tournament' => $tournament, 'mode' => 'pdf'])); ?>" class="btn btn-secondary" target="_blank">Download PDF</a>
        <a href="<?php echo e(route('panel.tournaments.print', $tournament)); ?>" class="btn btn-secondary" target="_blank">Print Report</a>
        <a href="<?php echo e(route('panel.tournaments.index')); ?>" class="btn btn-secondary">Back To Tournaments</a>
        <a href="<?php echo e(route('panel.matches.index')); ?>" class="btn">Open Match Monitor</a>
    </div>
</div>

<div class="stats">
    <div class="stat-card"><div class="stat-label">Registrations</div><div class="stat-value"><?php echo e($stats['total_players']); ?></div></div>
    <div class="stat-card"><div class="stat-label">Completed Matches</div><div class="stat-value"><?php echo e($stats['completed_matches']); ?></div></div>
    <div class="stat-card"><div class="stat-label">Pending Matches</div><div class="stat-value"><?php echo e($stats['pending_matches']); ?></div></div>
    <div class="stat-card"><div class="stat-label">Prize Pool</div><div class="stat-value">₹<?php echo e(number_format((float) $tournament->total_prize_pool, 0)); ?></div></div>
    <div class="stat-card"><div class="stat-label">Platform Fee</div><div class="stat-value">₹<?php echo e(number_format((float) $tournament->platform_fee_amount, 0)); ?></div></div>
    <div class="stat-card"><div class="stat-label">Override Matches</div><div class="stat-value"><?php echo e($stats['override_matches']); ?></div></div>
</div>

<div class="tabs-bar">
    <a href="#overview" class="tab-chip">Overview</a>
    <a href="#winners" class="tab-chip">Winners</a>
    <a href="#registrations" class="tab-chip">Registrations</a>
    <a href="#matches" class="tab-chip">Round-Wise Matches</a>
    <a href="#financials" class="tab-chip">Financials</a>
</div>

<section id="overview" class="panel report-section">
    <div class="section-title">Overview</div>
    <div class="details-grid">
        <div><span>Created On</span><strong><span data-utc-time="<?php echo e($tournament->created_at?->toIso8601String()); ?>"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></span></strong></div>
        <div><span>Tournament Start</span><strong><span data-utc-time="<?php echo e($tournament->tournament_start_at?->toIso8601String()); ?>"><?php echo e($tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—'); ?></span></strong></div>
        <div><span>Completed On</span><strong><span data-utc-time="<?php echo e($tournament->completed_at?->toIso8601String()); ?>"><?php echo e($tournament->completed_at?->format('d M Y, h:i A') ?? '—'); ?></span></strong></div>
        <div><span>Entry Fee</span><strong>₹<?php echo e(number_format((float) $tournament->entry_fee, 2)); ?></strong></div>
        <div><span>Players Per Match</span><strong><?php echo e($tournament->players_per_match); ?></strong></div>
        <div><span>Approved</span><strong><?php echo e($tournament->is_approved ? 'Yes' : 'No'); ?></strong></div>
        <div><span>Real Players</span><strong><?php echo e($stats['real_players']); ?></strong></div>
        <div><span>Bot Players</span><strong><?php echo e($stats['bot_players']); ?></strong></div>
        <div><span>Cancelled Matches</span><strong><?php echo e($stats['cancelled_matches']); ?></strong></div>
    </div>
    <?php if(!empty($tournament->play_slots)): ?>
        <div class="note-box">
            <div class="note-title">Playing Slots</div>
            <div class="stack-compact">
                <?php $__currentLoopData = $tournament->normalizedPlaySlots(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($slot['label'] ?? 'Slot'); ?>: <?php echo e($slot['start_time'] ?? '—'); ?> to <?php echo e($slot['end_time'] ?? '—'); ?><?php echo e(!empty($slot['timezone']) ? ' · '.$slot['timezone'] : ''); ?><?php echo e(($slot['recurrence'] ?? '') === 'daily' ? ' · Daily' : ''); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if($tournament->description): ?>
        <div class="note-box">
            <div class="note-title">Description</div>
            <div><?php echo e($tournament->description); ?></div>
        </div>
    <?php endif; ?>
</section>

<section id="winners" class="panel report-section">
    <div class="section-title">Winners & Payouts</div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Position</th>
                <th>Winner</th>
                <th>User ID</th>
                <th>Prize</th>
                <th>Payout</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $prizes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prize): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>#<?php echo e($prize->position); ?></td>
                    <td><?php echo e($prize->winner?->username ?? 'Pending'); ?></td>
                    <td><?php echo e($prize->winner?->user_code ?? '—'); ?></td>
                    <td>₹<?php echo e(number_format((float) $prize->prize_amount, 2)); ?></td>
                    <td><?php echo e(ucfirst($prize->payout_status)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="muted">No prize rows found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section id="registrations" class="panel report-section">
    <div class="section-title">Registrations</div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Player</th>
                <th>User ID</th>
                <th>Status</th>
                <th>Position</th>
                <th>Prize Won</th>
                <th>Registered</th>
                <th>Eliminated</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $registrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($registration->displayName()); ?></td>
                    <td><?php echo e($registration->user?->user_code ?? 'Bot'); ?></td>
                    <td><?php echo e(ucwords(str_replace('_', ' ', $registration->status))); ?></td>
                    <td><?php echo e($registration->final_position ? '#' . $registration->final_position : '—'); ?></td>
                    <td><?php echo e((float) $registration->prize_won > 0 ? '₹' . number_format((float) $registration->prize_won, 2) : '—'); ?></td>
                    <td><span data-utc-time="<?php echo e($registration->registered_at?->toIso8601String()); ?>"><?php echo e($registration->registered_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                    <td><span data-utc-time="<?php echo e($registration->eliminated_at?->toIso8601String()); ?>"><?php echo e($registration->eliminated_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="muted">No registrations found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section id="matches" class="panel report-section">
    <div class="section-title">Round-Wise Match Report</div>
    <div class="round-stack">
        <?php $__empty_1 = true; $__currentLoopData = $rounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $round): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="round-card">
                <div class="round-head">
                    <div>
                        <strong>Round <?php echo e($round['round_number']); ?></strong>
                        <div class="muted" style="font-size:13px;">
                            <?php echo e($round['completed_matches']); ?> completed · <?php echo e($round['pending_matches']); ?> pending · <?php echo e($round['cancelled_matches']); ?> cancelled
                        </div>
                    </div>
                    <span class="badge"><?php echo e($round['total_matches']); ?> matches</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Match</th>
                            <th>Status</th>
                            <th>Players</th>
                            <th>Winner</th>
                            <th>Scheduled</th>
                            <th>Ended</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $__currentLoopData = $round['matches']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    #<?php echo e($match->match_number); ?>

                                    <?php if($match->is_admin_override): ?>
                                        <div class="muted" style="font-size:12px;">Winner forced manually</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(ucwords(str_replace('_', ' ', $match->status))); ?></td>
                                <td>
                                    <?php if($match->players->isNotEmpty()): ?>
                                        <?php echo e($match->players->map(fn ($player) => $player->registration?->displayName() ?? 'Unknown')->join(', ')); ?>

                                    <?php else: ?>
                                        <span class="muted">No players</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($match->winner?->displayName() ?? $match->forcedWinner?->displayName() ?? 'Pending'); ?></td>
                                <td><span data-utc-time="<?php echo e($match->scheduled_at?->toIso8601String()); ?>"><?php echo e($match->scheduled_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                                <td><span data-utc-time="<?php echo e($match->ended_at?->toIso8601String()); ?>"><?php echo e($match->ended_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="muted">No match rounds found.</div>
        <?php endif; ?>
    </div>
</section>

<section id="financials" class="panel report-section">
    <div class="section-title">Financials</div>
    <div class="details-grid" style="margin-bottom:18px;">
        <div><span>Gross Entry</span><strong>₹<?php echo e(number_format((float) $stats['gross_entry'], 2)); ?></strong></div>
        <div><span>Prize Pool</span><strong>₹<?php echo e(number_format((float) $tournament->total_prize_pool, 2)); ?></strong></div>
        <div><span>Platform Fee</span><strong>₹<?php echo e(number_format((float) $tournament->platform_fee_amount, 2)); ?></strong></div>
        <div><span>Paid / Planned Payout</span><strong>₹<?php echo e(number_format((float) $stats['prize_paid'], 2)); ?></strong></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $financialRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><span data-utc-time="<?php echo e($row->created_at?->toIso8601String()); ?>"><?php echo e($row->created_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                    <td><?php echo e($row->user?->username ?? 'System'); ?></td>
                    <td><?php echo e(ucwords(str_replace('_', ' ', $row->type ?? 'transaction'))); ?></td>
                    <td>₹<?php echo e(number_format((float) ($row->amount ?? 0), 2)); ?></td>
                    <td><?php echo e($row->description ?? '—'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="muted">No tournament wallet transactions found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const userReportTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
document.querySelectorAll('[data-utc-time]').forEach((node) => {
    const iso = node.getAttribute('data-utc-time');
    if (!iso) return;
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return;
    node.textContent = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: userReportTimezone,
        timeZoneName: 'short',
    }).format(date);
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/user/tournaments/report.blade.php ENDPATH**/ ?>