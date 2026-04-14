<?php $__env->startSection('title', 'My Tournaments'); ?>
<?php $__env->startSection('heading', 'My Tournaments'); ?>
<?php $__env->startSection('subheading', 'Create, review, and manage only your own tournaments'); ?>

<?php $__env->startSection('content'); ?>
<?php ($editing = $editingTournament ?? null); ?>

<div class="panel page-hero" style="margin-bottom:24px;">
    <div>
        <div class="eyebrow">Tournament Workspace</div>
        <h2 style="margin:8px 0 10px;font-size:30px;">Your tournaments, reports, winners, and financials in one place</h2>
        <p class="muted" style="max-width:780px;line-height:1.7;margin:0;">
            Create tournament, monitor progress, open detailed reports, and drill into registrations, payouts, and match outcomes.
        </p>
    </div>
    <div class="hero-stats">
        <div class="hero-chip">
            <strong><?php echo e($tournaments->count()); ?></strong>
            <span>Total Tournaments</span>
        </div>
        <div class="hero-chip">
            <strong><?php echo e($tournaments->sum('running_matches_count')); ?></strong>
            <span>Running Matches</span>
        </div>
        <div class="hero-chip">
            <strong><?php echo e($tournaments->sum('completed_matches_count')); ?></strong>
            <span>Completed Matches</span>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
    <div>
        <div style="font-size:18px;font-weight:700;">Tournament Form</div>
        <div class="muted">Open popup, fill tournament information, and submit.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <a href="<?php echo e(route('tournament.guide')); ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:12px;border:1px solid var(--line);background:linear-gradient(135deg,#fff5ea 0%,#f8e3cf 100%);color:var(--brand-dark);font-weight:700;font-size:14px;">
            📖 Learn &amp; Guide
        </a>
        <button type="button" class="btn" data-modal-open="userTournamentModal"><?php echo e($editing ? 'Edit Tournament' : 'Create Tournament'); ?></button>
    </div>
</div>

<div id="userTournamentModal" class="modal-shell <?php echo e(($editing || $errors->any()) ? 'is-open' : ''); ?>">
    <div class="modal-backdrop" data-modal-close="userTournamentModal"></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <div style="font-size:20px;font-weight:700;"><?php echo e($editing ? 'Edit Tournament' : 'Create Tournament'); ?></div>
                <div class="muted">Fill tournament information and submit.</div>
                <div class="muted" style="margin-top:4px;">Timezone: <span data-user-timezone>UTC</span></div>
            </div>
            <button type="button" class="modal-close" data-modal-close="userTournamentModal">×</button>
        </div>
        <form method="POST" action="<?php echo e($editing ? route('panel.tournaments.update', $editing) : route('panel.tournaments.store')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($editing): ?>
                <?php echo method_field('PUT'); ?>
            <?php endif; ?>
            <input type="hidden" name="timezone" id="user_tournament_timezone" value="">
            <div class="form-grid">
                <div><label>Name</label><input name="name" value="<?php echo e(old('name', $editing?->name)); ?>" required></div>
                <div><label>Type</label><select name="type"><?php $__currentLoopData = ['public','private']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($type); ?>" <?php echo e(old('type', $editing?->type ?? 'public') === $type ? 'selected' : ''); ?>><?php echo e(ucfirst($type)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label>Format</label><select name="format"><?php $__currentLoopData = ['knockout','round_robin','double_elim','group_knockout']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $format): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($format); ?>" <?php echo e(old('format', $editing?->format ?? 'knockout') === $format ? 'selected' : ''); ?>><?php echo e(ucwords(str_replace('_', ' ', $format))); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label>Entry Fee</label><input type="number" step="0.01" name="entry_fee" value="<?php echo e(old('entry_fee', $editing?->entry_fee ?? 0)); ?>" required></div>
                <div><label>Max Players</label><select name="max_players"><?php $__currentLoopData = [4,8,16,32,64,112]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $max): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($max); ?>" <?php echo e((int) old('max_players', $editing?->max_players ?? 4) === $max ? 'selected' : ''); ?>><?php echo e($max); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label>Players Per Match</label><select name="players_per_match"><?php $__currentLoopData = [2,4]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ppm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($ppm); ?>" <?php echo e((int) old('players_per_match', $editing?->players_per_match ?? 4) === $ppm ? 'selected' : ''); ?>><?php echo e($ppm); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div><label>Platform Fee %</label><input type="number" step="0.01" name="platform_fee_pct" value="<?php echo e(old('platform_fee_pct', $editing?->platform_fee_pct ?? 20)); ?>"></div>
                <div><label>Registration Start</label><input type="datetime-local" name="registration_start_at" value="<?php echo e(old('registration_start_at')); ?>" data-utc="<?php echo e($editing?->registration_start_at?->toIso8601String()); ?>"></div>
                <div><label>Registration End</label><input type="datetime-local" name="registration_end_at" value="<?php echo e(old('registration_end_at')); ?>" data-utc="<?php echo e($editing?->registration_end_at?->toIso8601String()); ?>"></div>
                <div><label>Tournament Start</label><input type="datetime-local" name="tournament_start_at" value="<?php echo e(old('tournament_start_at')); ?>" data-utc="<?php echo e($editing?->tournament_start_at?->toIso8601String()); ?>" required></div>
                <div><label>Bracket Mode</label><select name="bracket_mode"><?php $__currentLoopData = ['auto','manual']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($mode); ?>" <?php echo e(old('bracket_mode', $editing?->bracket_mode ?? 'auto') === $mode ? 'selected' : ''); ?>><?php echo e(ucfirst($mode)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            </div>
            <div class="stack-compact" style="margin-top:14px;">
                <div style="font-size:15px;font-weight:700;">Playing Slots</div>
                <div class="form-grid">
                    <?php for($slot = 1; $slot <= 5; $slot++): ?>
                        <div class="panel" style="padding:12px;">
                            <div style="font-size:14px;font-weight:700;margin-bottom:10px;">Slot <?php echo e($slot); ?></div>
                            <div><label>Start</label><input type="datetime-local" name="play_slot_start_<?php echo e($slot); ?>" value="<?php echo e(old('play_slot_start_'.$slot)); ?>" data-utc="<?php echo e($editing && data_get($editing->play_slots, ($slot - 1).'.start_at') ? \Illuminate\Support\Carbon::parse(data_get($editing->play_slots, ($slot - 1).'.start_at'))->toIso8601String() : ''); ?>"></div>
                            <div style="margin-top:10px;"><label>End</label><input type="datetime-local" name="play_slot_end_<?php echo e($slot); ?>" value="<?php echo e(old('play_slot_end_'.$slot)); ?>" data-utc="<?php echo e($editing && data_get($editing->play_slots, ($slot - 1).'.end_at') ? \Illuminate\Support\Carbon::parse(data_get($editing->play_slots, ($slot - 1).'.end_at'))->toIso8601String() : ''); ?>"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="stack-compact">
                <div><label>Description</label><textarea name="description"><?php echo e(old('description', $editing?->description)); ?></textarea></div>
                <div><label>Terms & Conditions</label><textarea name="terms_conditions"><?php echo e(old('terms_conditions', $editing?->terms_conditions)); ?></textarea></div>
                <div class="prize-grid">
                    <?php for($pos = 1; $pos <= 5; $pos++): ?>
                        <div>
                            <label>Prize % <?php echo e($pos); ?></label>
                            <input type="number" step="0.01" name="prize_pct_<?php echo e($pos); ?>" value="<?php echo e(old('prize_pct_'.$pos, $editing?->prizes->firstWhere('position', $pos)?->prize_pct)); ?>">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="mobile-actions" style="margin-top:16px;">
                <button type="submit" class="btn"><?php echo e($editing ? 'Update Tournament' : 'Create Tournament'); ?></button>
                <button type="button" class="btn btn-secondary" data-modal-close="userTournamentModal">Close</button>
                <?php if($editing): ?>
                    <a href="<?php echo e(route('panel.tournaments.index')); ?>" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="cards-grid" style="margin-bottom:22px;">
    <?php $__empty_1 = true; $__currentLoopData = $tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="panel tournament-card">
            <div class="card-head">
                <div>
                    <div class="card-title"><?php echo e($tournament->name); ?></div>
                    <div class="muted" style="font-size:13px;">
                        Created <span data-utc-time="<?php echo e($tournament->created_at?->toIso8601String()); ?>"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></span>
                    </div>
                </div>
                <div class="stack-compact">
                    <span class="badge"><?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?></span>
                    <span class="badge <?php echo e($tournament->is_approved ? '' : 'badge-warn'); ?>"><?php echo e($tournament->is_approved ? 'Approved' : 'Pending Approval'); ?></span>
                </div>
            </div>

            <div class="metrics-grid">
                <div><span>Format</span><strong><?php echo e(ucwords(str_replace('_', ' ', $tournament->format))); ?></strong></div>
                <div><span>Players</span><strong><?php echo e($tournament->current_players); ?>/<?php echo e($tournament->max_players); ?></strong></div>
                <div><span>Real Registrations</span><strong><?php echo e($tournament->real_registrations_count); ?></strong></div>
                <div><span>Winners Marked</span><strong><?php echo e($tournament->winner_registrations_count); ?></strong></div>
                <div><span>Running Matches</span><strong><?php echo e($tournament->running_matches_count); ?></strong></div>
                <div><span>Completed Matches</span><strong><?php echo e($tournament->completed_matches_count); ?></strong></div>
                <div><span>Start Time</span><strong><span data-utc-time="<?php echo e($tournament->tournament_start_at?->toIso8601String()); ?>"><?php echo e($tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—'); ?></span></strong></div>
            </div>

            <div class="card-actions">
                <a href="<?php echo e(route('panel.tournaments.report', $tournament)); ?>" class="btn">Open Report</a>
                <a href="<?php echo e(route('panel.tournaments.edit', $tournament)); ?>" class="btn btn-secondary">Edit</a>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="panel">
            <div style="font-size:18px;font-weight:700;margin-bottom:6px;">No tournaments created yet</div>
            <div class="muted">Use the form above to create your first tournament and open detailed reporting from here.</div>
        </div>
    <?php endif; ?>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>Tournament</th>
                <th>Created</th>
                <th>Status</th>
                <th>Players</th>
                <th>Matches</th>
                <th>Financials</th>
                <th>Reports</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td data-label="Tournament">
                        <strong><?php echo e($tournament->name); ?></strong>
                        <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_', ' ', $tournament->format))); ?></div>
                    </td>
                    <td data-label="Created"><span data-utc-time="<?php echo e($tournament->created_at?->toIso8601String()); ?>"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></span></td>
                    <td data-label="Status"><?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?></td>
                    <td data-label="Players"><?php echo e($tournament->current_players); ?>/<?php echo e($tournament->max_players); ?></td>
                    <td data-label="Matches"><?php echo e($tournament->completed_matches_count); ?> done · <?php echo e($tournament->running_matches_count); ?> running</td>
                    <td data-label="Financials">₹<?php echo e(number_format((float) $tournament->total_prize_pool, 2)); ?> pool</td>
                    <td data-label="Reports"><a href="<?php echo e(route('panel.tournaments.report', $tournament)); ?>" class="text-link">View Full Report</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="muted">No tournament rows yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const userTournamentTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
const userTimezoneLabel = document.querySelector('[data-user-timezone]');
const userTimezoneInput = document.getElementById('user_tournament_timezone');
if (userTimezoneLabel) userTimezoneLabel.textContent = userTournamentTimezone;
if (userTimezoneInput) userTimezoneInput.value = userTournamentTimezone;

const toLocalInputValue = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

document.querySelectorAll('input[type="datetime-local"][data-utc]').forEach((input) => {
    if (!input.value && input.dataset.utc) {
        input.value = toLocalInputValue(input.dataset.utc);
    }
});

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
        timeZone: userTournamentTimezone,
        timeZoneName: 'short',
    }).format(date);
});

document.querySelectorAll('[data-modal-open]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-modal-open'))?.classList.add('is-open');
    });
});
document.querySelectorAll('[data-modal-close]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-modal-close'))?.classList.remove('is-open');
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/user/tournaments/index.blade.php ENDPATH**/ ?>