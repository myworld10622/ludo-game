<?php $__env->startSection('title', 'Tournaments'); ?>
<?php $__env->startSection('heading', 'Tournaments'); ?>
<?php $__env->startSection('subheading', 'Create, monitor, and open full reports for admin and user tournaments'); ?>

<?php
    $isEdit = (bool) $editingTournament;
    $formAction = $isEdit ? route('admin.tournaments.update', $editingTournament) : route('admin.tournaments.store');
    $t = $editingTournament;
    $existingPrizes = collect($t?->prizes ?? []);
    $prizePct = fn (int $pos) => old("prize_pct_{$pos}", $existingPrizes->firstWhere('position', $pos)?->prize_pct ?? match($pos) {1 => 50, 2 => 25, 3 => 15, 4 => 7, 5 => 3});
?>

<?php $__env->startSection('content'); ?>
<div class="stack">
    <div class="panel" style="background:linear-gradient(135deg,#0f172a,#153e75);color:#fff;border:none;">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Tournament Control Center</div>
                <h2 style="margin:12px 0 8px;font-size:30px;">Admin tournament reports and controls in one screen</h2>
                <div style="color:rgba(255,255,255,0.84);max-width:860px;line-height:1.7;">
                    Open any tournament to see full report like the user panel: created date, status, registrations, round-wise matches, winners, and financials.
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.tournaments.run-scheduler')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn" style="background:#2563eb;"
                    onclick="return confirm('Run status scheduler now?\n\nThis will move tournaments based on current time.')">
                    Run Scheduler Now
                </button>
            </form>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="stat-label">Total Tournaments</div><div class="stat-value"><?php echo e($tournamentStats['total']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Admin Tournaments</div><div class="stat-value"><?php echo e($tournamentStats['admin_total']); ?></div></div>
        <div class="stat-card"><div class="stat-label">User Tournaments</div><div class="stat-value"><?php echo e($tournamentStats['user_total']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Live</div><div class="stat-value"><?php echo e($tournamentStats['live']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value"><?php echo e($tournamentStats['completed']); ?></div></div>
        <div class="stat-card"><div class="stat-label">Pending Approval</div><div class="stat-value"><?php echo e($tournamentStats['pending_approval']); ?></div></div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Pending Approval Queue</strong>
            <span class="muted"><?php echo e($pendingApprovalTournaments->count()); ?> waiting</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Review</th>
                        <th>Edit</th>
                        <th>Approve</th>
                        <th>Reject With Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pendingApprovalTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td data-label="Tournament">
                                <strong><?php echo e($tournament->name); ?></strong>
                                <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_',' ', $tournament->format))); ?></div>
                            </td>
                            <td data-label="User"><?php echo e($tournament->creator?->username ?? 'User'); ?><div class="muted" style="font-size:12px;"><?php echo e($tournament->creator?->user_code ?? '—'); ?></div></td>
                            <td data-label="Created"><?php echo e($tournament->created_at?->format('d M Y, h:i A') ?? '—'); ?></td>
                            <td data-label="Review"><a href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Review Details</a></td>
                            <td data-label="Edit"><a href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Edit Form</a></td>
                            <td data-label="Approve"><form method="POST" action="<?php echo e(route('admin.tournaments.approve', $tournament)); ?>"><?php echo csrf_field(); ?><button type="submit" class="btn" style="font-size:12px;padding:6px 10px;">Approve</button></form></td>
                            <td data-label="Reject" style="min-width:280px;">
                                <form method="POST" action="<?php echo e(route('admin.tournaments.reject', $tournament)); ?>" class="stack" style="gap:8px;">
                                    <?php echo csrf_field(); ?>
                                    <textarea name="reason" rows="2" placeholder="Write rejection reason for user..." required></textarea>
                                    <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Reject And Notify</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:20px;">No tournaments pending approval.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Tournament Reports</strong>
            <span class="muted">Click any card to open full report</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;">
            <?php $__empty_1 = true; $__currentLoopData = $recentTournamentReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>" style="display:block;border:1px solid #d9e1e7;border-radius:14px;padding:16px;background:#f8fafc;">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-size:18px;font-weight:700;"><?php echo e($tournament->name); ?></div>
                            <div class="muted" style="font-size:12px;margin-top:4px;">
                                <?php echo e(ucfirst($tournament->creator_type)); ?>

                                <?php if($tournament->creator_type === 'user' && $tournament->creator): ?>
                                    · <?php echo e($tournament->creator->username); ?> (<?php echo e($tournament->creator->user_code); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge"><?php echo e(ucwords(str_replace('_', ' ', $tournament->status))); ?></span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:14px;">
                        <div><div class="stat-label">Players</div><div style="font-weight:700;"><?php echo e($tournament->registrations_count); ?>/<?php echo e($tournament->max_players); ?></div></div>
                        <div><div class="stat-label">Prize Pool</div><div style="font-weight:700;">₹<?php echo e(number_format((float) $tournament->total_prize_pool, 0)); ?></div></div>
                        <div><div class="stat-label">Completed</div><div style="font-weight:700;"><?php echo e($tournament->completed_matches_count); ?></div></div>
                        <div><div class="stat-label">Pending</div><div style="font-weight:700;"><?php echo e($tournament->pending_matches_count); ?></div></div>
                    </div>
                    <div style="margin-top:12px;color:#2563eb;font-weight:700;">Open Full Report</div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">No tournaments yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div>
                <strong>Tournament Form</strong>
                <div class="muted" style="margin-top:4px;">Open popup, fill tournament details, and submit.</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn" data-modal-open="adminTournamentModal"><?php echo e($isEdit ? 'Edit Tournament' : 'Create Tournament'); ?></button>
                <?php if($isEdit): ?>
                    <a class="btn btn-secondary" href="<?php echo e(route('admin.tournaments.index')); ?>">New Tournament</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="adminTournamentModal" class="modal-shell <?php echo e(($isEdit || $errors->any()) ? 'is-open' : ''); ?>">
        <div class="modal-backdrop" data-modal-close="adminTournamentModal"></div>
        <div class="modal-card">
            <div class="modal-head">
                <div>
                    <div style="font-size:20px;font-weight:700;"><?php echo e($isEdit ? 'Edit Tournament' : 'Create Tournament'); ?></div>
                    <div class="muted">Fill tournament details and submit.</div>
                </div>
                <button type="button" class="modal-close" data-modal-close="adminTournamentModal">×</button>
            </div>
            <form method="POST" action="<?php echo e($formAction); ?>" class="stack">
                <?php echo csrf_field(); ?>
                <?php if($isEdit): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                    <div><label>Name</label><input name="name" value="<?php echo e(old('name', $t?->name)); ?>" required style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Type</label><select name="type" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><?php $__currentLoopData = ['public','private']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($v); ?>" <?php if(old('type', $t?->type ?? 'public') === $v): echo 'selected'; endif; ?>><?php echo e(ucfirst($v)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div><label>Format</label><select name="format" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><option value="knockout" <?php if(old('format', $t?->format ?? 'knockout') === 'knockout'): echo 'selected'; endif; ?>>Knockout</option><option value="round_robin" <?php if(old('format', $t?->format) === 'round_robin'): echo 'selected'; endif; ?>>Round Robin</option><option value="double_elim" <?php if(old('format', $t?->format) === 'double_elim'): echo 'selected'; endif; ?>>Double Elimination</option><option value="group_knockout" <?php if(old('format', $t?->format) === 'group_knockout'): echo 'selected'; endif; ?>>Group + Knockout</option></select></div>
                    <div><label>Status</label><select name="status" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><?php $__currentLoopData = ['draft','registration_open','registration_closed','in_progress','completed','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($s); ?>" <?php if(old('status', $t?->status ?? 'registration_open') === $s): echo 'selected'; endif; ?>><?php echo e(ucwords(str_replace('_',' ',$s))); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div><label>Entry Fee</label><input type="number" step="0.01" name="entry_fee" value="<?php echo e(old('entry_fee', $t?->entry_fee ?? 10)); ?>" required style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Max Players</label><select name="max_players" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><?php $__currentLoopData = [4, 8, 16, 32, 64]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($n); ?>" <?php if((int) old('max_players', $t?->max_players ?? 8) === $n): echo 'selected'; endif; ?>><?php echo e($n); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div><label>Players Per Match</label><select name="players_per_match" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><option value="4" <?php if((int) old('players_per_match', $t?->players_per_match ?? 4) === 4): echo 'selected'; endif; ?>>4 Players</option><option value="2" <?php if((int) old('players_per_match', $t?->players_per_match) === 2): echo 'selected'; endif; ?>>2 Players</option></select></div>
                    <div><label>Platform Fee %</label><input type="number" step="0.1" name="platform_fee_pct" value="<?php echo e(old('platform_fee_pct', $t?->platform_fee_pct ?? 20)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Bracket Mode</label><select name="bracket_mode" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><option value="auto" <?php if(old('bracket_mode', $t?->bracket_mode ?? 'auto') === 'auto'): echo 'selected'; endif; ?>>Auto</option><option value="manual" <?php if(old('bracket_mode', $t?->bracket_mode) === 'manual'): echo 'selected'; endif; ?>>Manual</option></select></div>
                    <div><label>Allow Bots</label><select name="bot_allowed" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><option value="0" <?php if(! old('bot_allowed', $t?->bot_allowed ?? true)): echo 'selected'; endif; ?>>No</option><option value="1" <?php if((bool) old('bot_allowed', $t?->bot_allowed ?? true)): echo 'selected'; endif; ?>>Yes</option></select></div>
                    <div><label>Max Bot %</label><input type="number" step="1" name="max_bot_pct" value="<?php echo e(old('max_bot_pct', $t?->max_bot_pct ?? 5)); ?>" min="0" max="100" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Bot Start Policy</label><select name="bot_start_policy" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><option value="disabled" <?php if(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'disabled'): echo 'selected'; endif; ?>>Disabled</option><option value="fill_missing" <?php if(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'fill_missing'): echo 'selected'; endif; ?>>Fill Missing Seats</option><option value="replace_offline" <?php if(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'replace_offline'): echo 'selected'; endif; ?>>Replace Offline Players</option><option value="hybrid" <?php if(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'hybrid'): echo 'selected'; endif; ?>>Hybrid</option></select></div>
                    <div><label>Min Real Players To Start</label><input type="number" name="min_real_players_to_start" value="<?php echo e(old('min_real_players_to_start', $t?->min_real_players_to_start ?? 1)); ?>" min="1" max="4" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Bot Fill Delay (sec)</label><input type="number" name="bot_fill_after_seconds" value="<?php echo e(old('bot_fill_after_seconds', $t?->bot_fill_after_seconds ?? 8)); ?>" min="0" max="300" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Registration Opens</label><input type="datetime-local" name="registration_start_at" value="<?php echo e(old('registration_start_at', optional($t?->registration_start_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Registration Closes</label><input type="datetime-local" name="registration_end_at" value="<?php echo e(old('registration_end_at', optional($t?->registration_end_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Tournament Start</label><input type="datetime-local" name="tournament_start_at" required value="<?php echo e(old('tournament_start_at', optional($t?->tournament_start_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                    <div><label>Private Password</label><input name="invite_password" value="<?php echo e(old('invite_password', $t?->invite_password)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"></div>
                </div>

                <div>
                    <strong>Playing Slots</strong>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:10px;">
                        <?php for($slot = 1; $slot <= 5; $slot++): ?>
                            <?php
                                $slotStart = data_get($t?->play_slots, ($slot - 1).'.start_at');
                                $slotEnd = data_get($t?->play_slots, ($slot - 1).'.end_at');
                            ?>
                            <div class="panel" style="padding:12px;">
                                <div style="font-size:14px;font-weight:700;margin-bottom:8px;">Slot <?php echo e($slot); ?></div>
                                <label>Start</label>
                                <input type="datetime-local" name="play_slot_start_<?php echo e($slot); ?>" value="<?php echo e(old('play_slot_start_'.$slot, $slotStart ? \Illuminate\Support\Carbon::parse($slotStart)->format('Y-m-d\\TH:i') : '')); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;">
                                <label style="margin-top:8px;">End</label>
                                <input type="datetime-local" name="play_slot_end_<?php echo e($slot); ?>" value="<?php echo e(old('play_slot_end_'.$slot, $slotEnd ? \Illuminate\Support\Carbon::parse($slotEnd)->format('Y-m-d\\TH:i') : '')); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div><label>Terms & Conditions</label><textarea name="terms_conditions" rows="2" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;"><?php echo e(old('terms_conditions', $t?->terms_conditions)); ?></textarea></div>

                <div>
                    <strong>Prize Distribution</strong>
                    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:10px;">
                        <?php for($pos = 1; $pos <= 5; $pos++): ?>
                            <div>
                                <label><?php echo e(['1st','2nd','3rd','4th','5th'][$pos-1]); ?></label>
                                <input type="number" step="0.1" name="prize_pct_<?php echo e($pos); ?>" value="<?php echo e(old("prize_pct_{$pos}", $prizePct($pos))); ?>" min="0" max="100" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:8px;">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn" type="submit"><?php echo e($isEdit ? 'Update Tournament' : 'Create Tournament'); ?></button>
                    <button type="button" class="btn btn-secondary" data-modal-close="adminTournamentModal">Close</button>
                    <?php if($isEdit): ?>
                        <a href="<?php echo e(route('admin.tournaments.index')); ?>" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php
        $renderRow = function (App\Models\Tournament $tournament) {
            $isDraft = $tournament->status === 'draft';
            $isOpen = $tournament->status === 'registration_open';
            $fake = (int) ($tournament->fake_registrations_count ?? 0);
            return compact('isDraft', 'isOpen', 'fake');
        };
    ?>

    <div class="panel">
        <div class="header-row">
            <strong>User-Created Tournaments</strong>
            <span class="muted"><?php echo e($userTournaments->count()); ?> total</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tournament</th>
                        <th>Creator</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Prize Pool</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $userTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php extract($renderRow($tournament)); ?>
                        <tr>
                            <td data-label="ID"><?php echo e($tournament->id); ?></td>
                            <td data-label="Tournament">
                                <strong><?php echo e($tournament->name); ?></strong>
                                <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_',' ',$tournament->format))); ?> · <?php echo e($tournament->created_at?->format('d M Y, h:i A')); ?></div>
                            </td>
                            <td data-label="Creator"><?php echo e($tournament->creator?->username ?? 'User'); ?><div class="muted" style="font-size:12px;"><?php echo e($tournament->creator?->user_code ?? '—'); ?></div></td>
                            <td data-label="Status"><?php echo e(ucwords(str_replace('_',' ',$tournament->status))); ?></td>
                            <td data-label="Players"><?php echo e($tournament->registrations_count + $fake); ?>/<?php echo e($tournament->max_players); ?></td>
                            <td data-label="Matches"><?php echo e($tournament->completed_matches_count); ?> complete · <?php echo e($tournament->pending_matches_count); ?> pending</td>
                            <td data-label="Prize Pool">₹<?php echo e(number_format((float) $tournament->total_prize_pool, 2)); ?></td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn" style="font-size:12px;padding:4px 10px;margin-right:4px;" href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>">Open Report</a>
                                <?php if(!$tournament->is_approved): ?>
                                    <form method="POST" action="<?php echo e(route('admin.tournaments.approve', $tournament)); ?>" style="display:inline;"><?php echo csrf_field(); ?><button type="submit" class="btn" style="background:#10b981;font-size:12px;padding:4px 10px;margin-right:4px;">Approve</button></form>
                                <?php endif; ?>
                                <?php if($isDraft): ?>
                                    <form method="POST" action="<?php echo e(route('admin.tournaments.force-live', $tournament)); ?>" style="display:inline;"><?php echo csrf_field(); ?><button type="submit" class="btn" style="background:#f59e0b;font-size:12px;padding:4px 10px;margin-right:4px;">Force Live</button></form>
                                <?php endif; ?>
                                <a class="btn btn-secondary" style="font-size:12px;padding:4px 10px;" href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="muted" style="text-align:center;padding:20px;">No user-created tournaments yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Admin-Created Tournaments</strong>
            <span class="muted"><?php echo e($adminTournaments->count()); ?> total</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tournament</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Prize Pool</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $adminTournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php extract($renderRow($tournament)); ?>
                        <tr>
                            <td data-label="ID"><?php echo e($tournament->id); ?></td>
                            <td data-label="Tournament">
                                <strong><?php echo e($tournament->name); ?></strong>
                                <div class="muted" style="font-size:12px;"><?php echo e(ucfirst($tournament->type)); ?> · <?php echo e(ucwords(str_replace('_',' ',$tournament->format))); ?> · <?php echo e($tournament->created_at?->format('d M Y, h:i A')); ?></div>
                            </td>
                            <td data-label="Status"><?php echo e(ucwords(str_replace('_',' ',$tournament->status))); ?></td>
                            <td data-label="Players"><?php echo e($tournament->registrations_count + $fake); ?>/<?php echo e($tournament->max_players); ?></td>
                            <td data-label="Matches"><?php echo e($tournament->completed_matches_count); ?> complete · <?php echo e($tournament->pending_matches_count); ?> pending</td>
                            <td data-label="Prize Pool">₹<?php echo e(number_format((float) $tournament->total_prize_pool, 2)); ?></td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn" style="font-size:12px;padding:4px 10px;margin-right:4px;" href="<?php echo e(route('admin.tournaments.report', $tournament)); ?>">Open Report</a>
                                <?php if($isDraft): ?>
                                    <form method="POST" action="<?php echo e(route('admin.tournaments.force-live', $tournament)); ?>" style="display:inline;"><?php echo csrf_field(); ?><button type="submit" class="btn" style="background:#f59e0b;font-size:12px;padding:4px 10px;margin-right:4px;">Force Live</button></form>
                                <?php endif; ?>
                                <a class="btn btn-secondary" style="font-size:12px;padding:4px 10px;" href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:20px;">No admin-created tournaments yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
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

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/tournaments/index.blade.php ENDPATH**/ ?>