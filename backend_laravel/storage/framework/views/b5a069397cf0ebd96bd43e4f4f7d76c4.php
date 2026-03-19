<?php $__env->startSection('title', 'Tournaments'); ?>
<?php $__env->startSection('heading', 'Tournaments'); ?>
<?php $__env->startSection('subheading', 'Create, publish, cancel, and maintain tournament inventory'); ?>

<?php
    $isEdit = (bool) $editingTournament;
    $formAction = $isEdit ? route('admin.tournaments.update', $editingTournament) : route('admin.tournaments.store');
    $statusOptions = ['draft', 'published', 'entry_open', 'entry_locked', 'seeding', 'running', 'cancelled', 'completed'];
    $visibilityOptions = ['public', 'private'];
    $typeOptions = ['knockout'];
    $seedingStrategyOptions = ['random', 'ranked', 'segmented'];
    $botFillPolicyOptions = ['fill_after_timeout', 'real_only', 'never_fill'];
    $editingMeta = $editingTournament?->meta ?? [];
    $editingRules = $editingTournament?->rules ?? [];
?>

<?php $__env->startSection('content'); ?>
    <div class="stack">
        <div class="panel">
            <div class="header-row">
                <strong><?php echo e($isEdit ? 'Edit Tournament' : 'Create Tournament'); ?></strong>
                <?php if($isEdit): ?>
                    <a class="btn btn-secondary" href="<?php echo e(route('admin.tournaments.index')); ?>">New Tournament</a>
                <?php endif; ?>
            </div>
            <div class="muted" style="margin-bottom:12px;">
                For Unity-visible joinable tournaments, use `published` or `entry_open` and keep the registration window open.
            </div>

            <form method="POST" action="<?php echo e($formAction); ?>" class="stack">
                <?php echo csrf_field(); ?>
                <?php if($isEdit): ?>
                    <?php echo method_field('PUT'); ?>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                    <div>
                        <label>Game</label>
                        <select name="game_id" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $games; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($game->id); ?>" <?php if(old('game_id', $editingTournament?->game_id) == $game->id): echo 'selected'; endif; ?>><?php echo e($game->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Code</label>
                        <input name="code" value="<?php echo e(old('code', $editingTournament?->code)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Name</label>
                        <input name="name" value="<?php echo e(old('name', $editingTournament?->name)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Slug</label>
                        <input name="slug" value="<?php echo e(old('slug', $editingTournament?->slug)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status); ?>" <?php if(old('status', $editingTournament?->status ?? 'draft') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $status))); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Visibility</label>
                        <select name="visibility" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $visibilityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visibility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($visibility); ?>" <?php if(old('visibility', $editingTournament?->visibility ?? 'public') === $visibility): echo 'selected'; endif; ?>><?php echo e(ucfirst($visibility)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Tournament Type</label>
                        <select name="tournament_type" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $typeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type); ?>" <?php if(old('tournament_type', $editingTournament?->type ?? 'knockout') === $type): echo 'selected'; endif; ?>><?php echo e(ucfirst($type)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Currency</label>
                        <input name="currency" value="<?php echo e(old('currency', $editingTournament?->currency ?? 'INR')); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Entry Fee</label>
                        <input type="number" step="0.0001" name="entry_fee" value="<?php echo e(old('entry_fee', $editingTournament?->entry_fee ?? 0)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Max Entries Per User</label>
                        <input type="number" name="max_entries_per_user" value="<?php echo e(old('max_entries_per_user', $editingTournament?->max_entries_per_user ?? 1)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Max Total Entries</label>
                        <input type="number" name="max_total_entries" value="<?php echo e(old('max_total_entries', $editingTournament?->max_total_entries)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Min Entries Required</label>
                        <input type="number" name="min_players" value="<?php echo e(old('min_players', $editingTournament?->min_total_entries ?? 2)); ?>" min="2" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Match Size</label>
                        <select name="match_size" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = [2, 4]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $matchSize): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($matchSize); ?>" <?php if((int) old('match_size', $editingTournament?->match_size ?? 4) === $matchSize): echo 'selected'; endif; ?>><?php echo e($matchSize); ?> Players</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Advance Count</label>
                        <input type="number" name="advance_count" value="<?php echo e(old('advance_count', $editingTournament?->advance_count ?? 1)); ?>" min="1" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Bracket Size</label>
                        <input type="number" name="bracket_size" value="<?php echo e(old('bracket_size', $editingTournament?->bracket_size)); ?>" min="1" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Bye Count</label>
                        <input type="number" name="bye_count" value="<?php echo e(old('bye_count', $editingTournament?->bye_count ?? 0)); ?>" min="0" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Seeding Strategy</label>
                        <select name="seeding_strategy" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $seedingStrategyOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $strategy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($strategy); ?>" <?php if(old('seeding_strategy', $editingTournament?->seeding_strategy ?? 'random') === $strategy): echo 'selected'; endif; ?>><?php echo e(ucfirst($strategy)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Bot Fill Policy</label>
                        <select name="bot_fill_policy" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <?php $__currentLoopData = $botFillPolicyOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $botFillPolicy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($botFillPolicy); ?>" <?php if(old('bot_fill_policy', $editingTournament?->bot_fill_policy ?? 'fill_after_timeout') === $botFillPolicy): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $botFillPolicy))); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label>Prize Pool</label>
                        <input type="number" step="0.0001" name="prize_pool" value="<?php echo e(old('prize_pool', $editingTournament?->prize_pool ?? 0)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Registration Start</label>
                        <input type="datetime-local" name="registration_starts_at" value="<?php echo e(old('registration_starts_at', optional($editingTournament?->entry_open_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Registration End</label>
                        <input type="datetime-local" name="registration_ends_at" value="<?php echo e(old('registration_ends_at', optional($editingTournament?->entry_close_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Tournament Start</label>
                        <input type="datetime-local" name="starts_at" value="<?php echo e(old('starts_at', optional($editingTournament?->start_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Tournament End</label>
                        <input type="datetime-local" name="ends_at" value="<?php echo e(old('ends_at', optional($editingTournament?->end_at)->format('Y-m-d\\TH:i'))); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                </div>

                <div>
                    <label>Description / Notes</label>
                    <textarea name="metadata[notes]" rows="3" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;"><?php echo e(old('metadata.notes', $editingMeta['notes'] ?? '')); ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                    <div>
                        <label>Ticket Prefix</label>
                        <input name="ticket_prefix" value="<?php echo e(old('ticket_prefix', $editingTournament?->ticket_prefix ?? '')); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Platform Fee</label>
                        <input type="number" step="0.0001" name="platform_fee" value="<?php echo e(old('platform_fee', $editingTournament?->platform_fee ?? 0)); ?>" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Joined Players Snapshot</label>
                        <input type="number" name="metadata[joined_players]" value="<?php echo e(old('metadata.joined_players', $editingMeta['joined_players'] ?? 0)); ?>" min="0" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Max Players Snapshot</label>
                        <input type="number" name="metadata[max_players]" value="<?php echo e(old('metadata.max_players', $editingMeta['max_players'] ?? ($editingTournament?->match_size ?? 4))); ?>" min="2" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Rule Players Per Match</label>
                        <input type="number" name="settings[players_per_match]" value="<?php echo e(old('settings.players_per_match', $editingRules['players_per_match'] ?? $editingTournament?->match_size ?? 4)); ?>" min="2" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Rule Advance Count</label>
                        <input type="number" name="settings[advance_count]" value="<?php echo e(old('settings.advance_count', $editingRules['advance_count'] ?? $editingTournament?->advance_count ?? 1)); ?>" min="1" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                </div>

                <div>
                    <strong>Prize Slabs</strong>
                    <div class="muted" style="margin:6px 0 12px;">First 3 rows are editable in this first-pass admin screen.</div>
                    <?php
                        $prizes = old('prize_slabs', $editingTournament?->prizes?->map(fn($prize) => [
                            'rank_from' => $prize->rank_from,
                            'rank_to' => $prize->rank_to,
                            'prize_type' => $prize->prize_type,
                            'prize_amount' => $prize->prize_amount,
                            'currency' => $prize->currency,
                        ])->values()->all() ?? [['rank_from'=>1,'rank_to'=>1,'prize_type'=>'cash','prize_amount'=>0,'currency'=>'INR']]);
                    ?>
                    <?php for($i = 0; $i < 3; $i++): ?>
                        <?php $prize = $prizes[$i] ?? ['rank_from'=>'','rank_to'=>'','prize_type'=>'cash','prize_amount'=>'','currency'=>'INR']; ?>
                        <div style="display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:12px;margin-bottom:12px;">
                            <input type="number" name="prize_slabs[<?php echo e($i); ?>][rank_from]" placeholder="Rank From" value="<?php echo e($prize['rank_from']); ?>" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input type="number" name="prize_slabs[<?php echo e($i); ?>][rank_to]" placeholder="Rank To" value="<?php echo e($prize['rank_to']); ?>" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input name="prize_slabs[<?php echo e($i); ?>][prize_type]" placeholder="Prize Type" value="<?php echo e($prize['prize_type']); ?>" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input type="number" step="0.0001" name="prize_slabs[<?php echo e($i); ?>][prize_amount]" placeholder="Prize Amount" value="<?php echo e($prize['prize_amount']); ?>" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input name="prize_slabs[<?php echo e($i); ?>][currency]" placeholder="Currency" value="<?php echo e($prize['currency']); ?>" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                        </div>
                    <?php endfor; ?>
                </div>

                <div>
                    <button class="btn" type="submit"><?php echo e($isEdit ? 'Update Tournament' : 'Create Tournament'); ?></button>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Tournament List</strong>
                <span class="muted">Operational view with quick edit access</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Game</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Entry Fee</th>
                            <th>Entries</th>
                            <th>Starts At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $tournaments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tournament): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($tournament->name); ?></td>
                                <td><?php echo e($tournament->game?->name ?: '-'); ?></td>
                                <td><?php echo e($tournament->status); ?></td>
                                <td><?php echo e($tournament->type); ?></td>
                                <td><?php echo e($tournament->entry_fee); ?> <?php echo e($tournament->currency); ?></td>
                                <td><?php echo e($tournament->entries()->count()); ?> / <?php echo e($tournament->max_total_entries ?: 'Open'); ?></td>
                                <td><?php echo e(optional($tournament->start_at)->toDateTimeString() ?: '-'); ?></td>
                                <td><a class="btn btn-secondary" href="<?php echo e(route('admin.tournaments.edit', $tournament)); ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="muted">No tournaments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;"><?php echo e($tournaments->links()); ?></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/tournaments/index.blade.php ENDPATH**/ ?>