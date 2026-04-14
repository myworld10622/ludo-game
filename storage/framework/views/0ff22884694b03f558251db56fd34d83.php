<?php $__env->startSection('title', 'Match Monitor'); ?>
<?php $__env->startSection('heading', 'Match Monitor'); ?>
<?php $__env->startSection('subheading', 'Live game tables — running, completed, and winner overrides'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .monitor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .match-card { background: var(--card2); border: 1px solid var(--line-dim); border-radius: 12px; padding: 14px 16px; transition: border-color .15s; }
    .match-card:hover { border-color: rgba(255,215,0,0.2); }
    .match-card.running { border-left: 3px solid var(--blue); }
    .match-card.completed { border-left: 3px solid var(--green); }
    .match-card.override { border-left: 3px solid #FF9500; }
    .mc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .mc-title { font-weight: 700; font-size: 14px; color: var(--text); }
    .mc-sub { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
    .mc-players { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
    .player-chip { padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
                   background: rgba(26,107,255,0.12); color: #66AAFF; border: 1px solid rgba(26,107,255,0.2); }
    .player-chip.bot { background: rgba(255,255,255,0.05); color: var(--muted); border-color: var(--line-dim); }
    .player-chip.winner { background: rgba(6,214,160,0.12); color: var(--green); border: 1px solid rgba(6,214,160,0.25); }
    .force-form { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 8px;
                  padding-top: 8px; border-top: 1px solid var(--line-dim); }
    .force-form select { flex: 1; min-width: 140px; }
    .force-form input[type=text] { flex: 1; min-width: 120px; }
    .force-form button { padding: 7px 16px; font-size: 13px; border-radius: 8px; border: 0; cursor: pointer;
                         background: linear-gradient(135deg, #FF9500, #d97706); color: #000; font-weight: 700; white-space: nowrap;
                         box-shadow: 0 4px 12px rgba(255,149,0,0.25); }
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
    .dot-run  { background: var(--blue); }
    .dot-wait { background: #f59e0b; }
    .dot-sched{ background: var(--muted); }
    .dot-done { background: var(--green); }
    .section-head { font-size: 16px; font-weight: 800; margin: 20px 0 10px; color: var(--text); }
    .override-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px;
                      font-weight: 700; background: rgba(255,149,0,0.12); color: #FF9500; border: 1px solid rgba(255,149,0,0.25); margin-left: 6px; }
    @media(max-width:900px){ .monitor-grid { grid-template-columns: 1fr; } }
</style>

<?php if(session('status')): ?>
    <div class="flash"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div style="background:#fee4e2;border:1px solid #fecdca;color:#b42318;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>


<div class="stats" style="margin-bottom:16px;">
    <div class="stat-card">
        <div class="stat-label">🟢 Running Matches</div>
        <div class="stat-value"><?php echo e($stats['running']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">✅ Completed (all time)</div>
        <div class="stat-value"><?php echo e(number_format($stats['completed_total'])); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">⚡ Admin Overrides</div>
        <div class="stat-value"><?php echo e($stats['overridden']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">🏆 Live Tournaments</div>
        <div class="stat-value"><?php echo e($stats['tournaments_live']); ?></div>
    </div>
</div>


<div class="section-head">
    ⚡ Active Game Tables
    <span class="muted" style="font-size:13px;font-weight:400;">(<?php echo e($runningMatches->count()); ?> total)</span>
</div>

<?php if($runningMatches->isEmpty()): ?>
    <div class="panel muted" style="padding:20px;text-align:center;">No active game tables right now.</div>
<?php else: ?>
    <div class="monitor-grid">
    <?php $__currentLoopData = $runningMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $dotClass = match($match->status) {
                'in_progress' => 'dot-run',
                'waiting'     => 'dot-wait',
                default       => 'dot-sched',
            };
            $duration = $match->started_at
                ? now()->diffForHumans($match->started_at, true)
                : '—';
            $hasForced = (bool) $match->forced_winner_registration_id;
        ?>
        <div class="match-card running <?php echo e($hasForced ? 'override' : ''); ?>">
            <div class="mc-header">
                <span class="mc-title">
                    <span class="status-dot <?php echo e($dotClass); ?>"></span>
                    R<?php echo e($match->round_number); ?> · Match #<?php echo e($match->match_number); ?>

                    <?php if($hasForced): ?><span class="override-badge">WINNER SET</span><?php endif; ?>
                </span>
                <span class="muted" style="font-size:12px;"><?php echo e(ucfirst($match->status)); ?></span>
            </div>
            <div class="mc-sub">
                🏆 <?php echo e($match->tournament->name ?? "T#{$match->tournament_id}"); ?>

                &nbsp;·&nbsp; ⏱ <?php echo e($duration); ?>

            </div>
            <div class="mc-players">
                <?php $__currentLoopData = $match->players; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $reg = $mp->registration;
                        $isBot = $reg?->is_bot;
                        $label = $isBot
                            ? ($reg->bot_name ?? "Bot#{$reg->id}")
                            : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                        $isForced = $match->forced_winner_registration_id == $reg?->id;
                    ?>
                    <span class="player-chip <?php echo e($isBot ? 'bot' : ''); ?> <?php echo e($isForced ? 'winner' : ''); ?>">
                        <?php echo e($label); ?><?php echo e($isForced ? ' 🎯' : ''); ?>

                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            
            <form method="POST" action="<?php echo e(route('admin.matches.force-winner', $match)); ?>" class="force-form">
                <?php echo csrf_field(); ?>
                <select name="registration_id" required>
                    <option value="">— Set winner —</option>
                    <?php $__currentLoopData = $match->players; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $reg = $mp->registration;
                            $lbl = $reg?->is_bot
                                ? ($reg->bot_name ?? "Bot#{$reg->id}")
                                : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                        ?>
                        <option value="<?php echo e($reg?->id); ?>"
                            <?php echo e($match->forced_winner_registration_id == $reg?->id ? 'selected' : ''); ?>>
                            <?php echo e($lbl); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <input type="text" name="note" placeholder="Reason (optional)" maxlength="255">
                <button type="submit">Set Winner</button>
            </form>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>


<div class="section-head" style="margin-top:28px;">
    ✅ Recently Completed
    <span class="muted" style="font-size:13px;font-weight:400;">(last 100)</span>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tournament</th>
                    <th>Round</th>
                    <th>Match</th>
                    <th>Players</th>
                    <th>Winner</th>
                    <th>Override?</th>
                    <th>Ended</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $completedMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $winnerReg = $match->winner;
                    $winnerName = $winnerReg
                        ? ($winnerReg->is_bot
                            ? ($winnerReg->bot_name ?? "Bot#{$winnerReg->id}")
                            : ($winnerReg->user?->username ?? "User#{$winnerReg->user_id}"))
                        : '—';
                    $playerList = $match->players->map(function($mp) {
                        $reg = $mp->registration;
                        return $reg?->is_bot
                            ? ($reg->bot_name ?? "Bot#{$reg->id}")
                            : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                    })->join(' vs ');
                    $dur = ($match->started_at && $match->ended_at)
                        ? $match->started_at->diff($match->ended_at)->format('%im %ss')
                        : '—';
                ?>
                <tr>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?php echo e($match->tournament->name ?? "T#{$match->tournament_id}"); ?>

                    </td>
                    <td>R<?php echo e($match->round_number); ?></td>
                    <td>#<?php echo e($match->match_number); ?></td>
                    <td style="font-size:13px;"><?php echo e($playerList ?: '—'); ?></td>
                    <td>
                        <?php if($winnerReg && !$winnerReg->is_bot): ?>
                            <strong style="color:#065f46;"><?php echo e($winnerName); ?></strong>
                        <?php else: ?>
                            <span class="muted"><?php echo e($winnerName); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($match->is_admin_override): ?>
                            <span class="badge" style="background:#fef3c7;color:#92400e;" title="<?php echo e($match->admin_override_note); ?>">Override</span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;" class="muted">
                        <?php echo e($match->ended_at?->format('M d H:i') ?? '—'); ?>

                    </td>
                    <td style="font-size:12px;" class="muted"><?php echo e($dur); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="muted">No completed matches yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/tournaments/matches.blade.php ENDPATH**/ ?>