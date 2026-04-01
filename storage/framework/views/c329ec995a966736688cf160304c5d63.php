<?php $__env->startSection('title', 'Classic Ludo Tables'); ?>
<?php $__env->startSection('heading', 'Classic Ludo Tables'); ?>
<?php $__env->startSection('subheading', 'Manage 2-player and 4-player classic fee tables, and monitor live public rooms'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $groupedTableSummaries = collect($tableSummaries)->groupBy(fn ($summary) => (int) $summary['table']->player_count);
        $tableSections = [
            2 => '2 Player Tables',
            4 => '4 Player Tables',
        ];
    ?>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Configured Tables</div>
            <div class="stat-value"><?php echo e($stats['total_tables']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Tables</div>
            <div class="stat-value"><?php echo e($stats['active_tables']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">2 Player Active</div>
            <div class="stat-value"><?php echo e($stats['two_player_tables']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">4 Player Active</div>
            <div class="stat-value"><?php echo e($stats['four_player_tables']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Waiting Rooms</div>
            <div class="stat-value"><?php echo e($stats['waiting_rooms']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Live Rooms</div>
            <div class="stat-value"><?php echo e($stats['live_rooms']); ?></div>
        </div>
    </div>

    <div class="panel" style="margin-bottom:16px;">
        <div class="header-row">
            <div>
                <strong>Add Classic Ludo Fee Table</strong>
                <div class="muted" style="margin-top:4px;">Ye cards Unity classic page ke 2 Player aur 4 Player fee selection ko control karenge.</div>
            </div>
        </div>
        <form method="POST" action="<?php echo e(route('admin.games.ludo-tables.store')); ?>" class="split-2">
            <?php echo csrf_field(); ?>
            <div>
                <label>Player Count</label>
                <select name="player_count" required>
                    <option value="2">2 Player</option>
                    <option value="4">4 Player</option>
                </select>
            </div>
            <div>
                <label>Entry Fee</label>
                <input type="number" name="entry_fee" min="0" step="0.01" placeholder="10 / 50 / 100 / 500" required>
            </div>
            <div>
                <label>Sort Order</label>
                <input type="number" name="sort_order" min="0" step="1" value="0">
            </div>
            <div>
                <label>Notes</label>
                <input type="text" name="notes" maxlength="255" placeholder="Optional internal note for admin">
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="new_is_active" name="is_active" value="1" checked style="width:auto;">
                <label for="new_is_active" style="margin:0;">Keep table active in lobby</label>
            </div>
            <div style="display:flex;align-items:end;">
                <button type="submit" class="btn">Add Fee Table</button>
            </div>
        </form>
    </div>

    <div class="panel" style="margin-bottom:16px;">
        <div class="header-row">
            <strong>Configured Classic Fee Tables</strong>
            <span class="muted">Delete sirf tab allowed hai jab matching active room na chal raha ho.</span>
        </div>
        <div class="mobile-actions" style="margin:14px 0 16px;">
            <button type="button" class="btn classic-filter-btn" data-classic-filter="all">All Tables</button>
            <button type="button" class="btn btn-secondary classic-filter-btn" data-classic-filter="2">2 Player</button>
            <button type="button" class="btn btn-secondary classic-filter-btn" data-classic-filter="4">4 Player</button>
        </div>
        <?php $__empty_1 = true; $__currentLoopData = $tableSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $playerCount => $sectionTitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="classic-table-section" data-classic-section="<?php echo e($playerCount); ?>" style="margin-top:18px;">
                <div class="header-row" style="margin-bottom:10px;">
                    <div>
                        <strong><?php echo e($sectionTitle); ?></strong>
                        <div class="muted" style="margin-top:4px;">
                            <?php echo e($groupedTableSummaries->get($playerCount, collect())->count()); ?> configured fee tables
                        </div>
                    </div>
                    <span class="badge"><?php echo e($playerCount); ?>P Lobby</span>
                </div>
                <div class="table-wrap responsive-table">
                    <table>
                        <thead>
                        <tr>
                            <th>Table</th>
                            <th>Visibility</th>
                            <th>Room Snapshot</th>
                            <th>Players Snapshot</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $__empty_2 = true; $__currentLoopData = $groupedTableSummaries->get($playerCount, collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $summary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                            <?php ($table = $summary['table']); ?>
                            <tr>
                                <td data-label="Table">
                                    <strong><?php echo e($table->player_count); ?> Player · ₹<?php echo e(number_format((float) $table->entry_fee, 2)); ?></strong><br>
                                    <span class="muted">Prize pool approx ₹<?php echo e(number_format($summary['estimated_prize_pool'], 2)); ?></span><br>
                                    <span class="muted">Sort <?php echo e($table->sort_order); ?><?php echo e($table->notes ? ' · '.$table->notes : ''); ?></span>
                                </td>
                                <td data-label="Visibility">
                                    <span class="badge <?php echo e($table->is_active ? '' : 'off'); ?>"><?php echo e($table->is_active ? 'Active In Lobby' : 'Hidden / Disabled'); ?></span>
                                </td>
                                <td data-label="Room Snapshot">
                                    <div><strong>Total:</strong> <?php echo e($summary['total_rooms']); ?></div>
                                    <div><strong>Waiting:</strong> <?php echo e($summary['waiting_rooms']); ?></div>
                                    <div><strong>Live:</strong> <?php echo e($summary['live_rooms']); ?></div>
                                </td>
                                <td data-label="Players Snapshot">
                                    <div><strong>Total Seats Used:</strong> <?php echo e($summary['current_players']); ?></div>
                                    <div><strong>Real:</strong> <?php echo e($summary['current_real_players']); ?></div>
                                    <div><strong>Bots:</strong> <?php echo e($summary['current_bot_players']); ?></div>
                                </td>
                                <td data-label="Actions">
                                    <form method="POST" action="<?php echo e(route('admin.games.ludo-tables.update', $table)); ?>" class="stack" style="gap:10px;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <div class="mobile-actions">
                                            <div style="flex:1;min-width:120px;">
                                                <label>Players</label>
                                                <select name="player_count" required>
                                                    <option value="2" <?php echo e($table->player_count === 2 ? 'selected' : ''); ?>>2 Player</option>
                                                    <option value="4" <?php echo e($table->player_count === 4 ? 'selected' : ''); ?>>4 Player</option>
                                                </select>
                                            </div>
                                            <div style="flex:1;min-width:120px;">
                                                <label>Entry Fee</label>
                                                <input type="number" name="entry_fee" min="0" step="0.01" value="<?php echo e(number_format((float) $table->entry_fee, 2, '.', '')); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mobile-actions">
                                            <div style="flex:1;min-width:100px;">
                                                <label>Sort</label>
                                                <input type="number" name="sort_order" min="0" step="1" value="<?php echo e($table->sort_order); ?>">
                                            </div>
                                            <div style="flex:2;min-width:160px;">
                                                <label>Notes</label>
                                                <input type="text" name="notes" maxlength="255" value="<?php echo e($table->notes); ?>">
                                            </div>
                                            <div style="display:flex;align-items:end;min-width:140px;">
                                                <div>
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" id="is_active_<?php echo e($table->id); ?>" name="is_active" value="1" <?php echo e($table->is_active ? 'checked' : ''); ?> style="width:auto;">
                                                    <label for="is_active_<?php echo e($table->id); ?>" style="display:inline;margin-left:8px;">Active</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mobile-actions">
                                            <button type="submit" class="btn">Update Table</button>
                                        </div>
                                    </form>
                                    <div class="mobile-actions" style="margin-top:10px;">
                                        <form method="POST" action="<?php echo e(route('admin.games.ludo-tables.destroy', $table)); ?>" onsubmit="return confirm('Remove this classic fee table?');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-secondary">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                            <tr>
                                <td colspan="5" class="muted">No <?php echo e(strtolower($sectionTitle)); ?> configured yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="muted">No classic Ludo fee tables configured yet.</div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="header-row">
            <div>
                <strong>Recent Public Ludo Rooms</strong>
                <div class="muted" style="margin-top:4px;">Admin yahan se dekh sakta hai kis fee table par waiting ya live room chal raha hai, aur kaun players us room me baithe hain.</div>
            </div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>Room</th>
                    <th>Fee Table</th>
                    <th>Status</th>
                    <th>Players</th>
                    <th>Runtime</th>
                </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $recentRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="Room">
                            <strong>#<?php echo e($room->id); ?></strong><br>
                            <span class="muted"><?php echo e($room->room_uuid); ?></span>
                        </td>
                        <td data-label="Fee Table">
                            <strong><?php echo e($room->max_players); ?> Player · ₹<?php echo e(number_format((float) $room->entry_fee, 2)); ?></strong><br>
                            <span class="muted"><?php echo e(ucfirst($room->room_type)); ?> / <?php echo e(ucfirst($room->play_mode)); ?></span>
                        </td>
                        <td data-label="Status">
                            <span class="badge <?php echo e(in_array($room->status, ['waiting', 'starting', 'live', 'active', 'in_progress', 'playing'], true) ? '' : 'off'); ?>">
                                <?php echo e(str_replace('_', ' ', ucfirst($room->status))); ?>

                            </span><br>
                            <span class="muted">Real <?php echo e($room->current_real_players); ?> · Bot <?php echo e($room->current_bot_players); ?></span>
                        </td>
                        <td data-label="Players">
                            <?php if($room->players->isEmpty()): ?>
                                <span class="muted">No players joined yet.</span>
                            <?php else: ?>
                                <?php $__currentLoopData = $room->players->sortBy('seat_no'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $player): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div>
                                        <strong>S<?php echo e($player->seat_no); ?></strong>
                                        <?php echo e($player->player_type === 'bot' ? 'Bot' : ($player->user?->username ?? 'User')); ?>

                                        <span class="muted">· <?php echo e($player->status); ?></span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </td>
                        <td data-label="Runtime">
                            <div><strong>Created:</strong> <?php echo e(optional($room->created_at)->format('d M Y, h:i A')); ?></div>
                            <div><strong>Started:</strong> <?php echo e(optional($room->started_at)->format('d M Y, h:i A') ?: '—'); ?></div>
                            <div><strong>Last Match:</strong> <?php echo e(optional($room->matches->first()?->completed_at)->format('d M Y, h:i A') ?: '—'); ?></div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="muted">Abhi tak koi public Ludo room generate nahi hua.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterButtons = document.querySelectorAll('.classic-filter-btn');
            const sections = document.querySelectorAll('.classic-table-section');

            function applyClassicFilter(filter) {
                sections.forEach(section => {
                    section.style.display = (filter === 'all' || section.dataset.classicSection === filter) ? '' : 'none';
                });

                filterButtons.forEach(button => {
                    const isActive = button.dataset.classicFilter === filter;
                    button.classList.toggle('btn-secondary', !isActive);
                });
            }

            filterButtons.forEach(button => {
                button.addEventListener('click', function () {
                    applyClassicFilter(button.dataset.classicFilter);
                });
            });

            applyClassicFilter('all');
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/admin/games/ludo-tables/index.blade.php ENDPATH**/ ?>