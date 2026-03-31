<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Runs every minute via scheduler.
 * Handles two time-based transitions:
 *
 *  1. draft → registration_open
 *     Condition: is_approved=true AND registration_start_at <= now
 *
 *  2. registration_open → registration_closed
 *     Condition: registration_end_at <= now
 *     Side-effect: recalculatePrizePool()
 *
 * NOTE: registration_closed → in_progress is triggered manually by admin
 *       via POST /api/v1/tournaments/{t}/generate-bracket.
 */
class TournamentStatusAutomationService
{
    public function advanceStatuses(): array
    {
        $now = CarbonImmutable::now();
        $openedCount  = 0;
        $closedCount  = 0;
        $disqualifiedCount = 0;

        // ── 1. draft → registration_open ─────────────────────────────────────
        // Approved tournaments whose registration window has arrived.
        Tournament::query()
            ->where('status', Tournament::STATUS_DRAFT)
            ->where('is_approved', true)
            ->whereNotNull('registration_start_at')
            ->where('registration_start_at', '<=', $now)
            ->chunkById(100, function ($tournaments) use (&$openedCount) {
                foreach ($tournaments as $tournament) {
                    $tournament->status = Tournament::STATUS_REGISTRATION_OPEN;
                    $tournament->save();
                    $openedCount++;
                    Log::info("[TournamentScheduler] #{$tournament->id} \"{$tournament->name}\" → registration_open");
                }
            });

        // ── 2. registration_open → registration_closed ───────────────────────
        // Registration window has passed (and not already full-closed earlier).
        Tournament::query()
            ->where('status', Tournament::STATUS_REGISTRATION_OPEN)
            ->whereNotNull('registration_end_at')
            ->where('registration_end_at', '<=', $now)
            ->chunkById(100, function ($tournaments) use (&$closedCount) {
                foreach ($tournaments as $tournament) {
                    $tournament->status            = Tournament::STATUS_REGISTRATION_CLOSED;
                    $tournament->registration_end_at = $tournament->registration_end_at; // keep original
                    $tournament->save();

                    // Recalculate actual prize pool from real registrations
                    $tournament->recalculatePrizePool();

                    $closedCount++;
                    Log::info("[TournamentScheduler] #{$tournament->id} \"{$tournament->name}\" → registration_closed (players: {$tournament->current_players})");
                }
            });

        Tournament::query()
            ->whereIn('status', [
                Tournament::STATUS_REGISTRATION_OPEN,
                Tournament::STATUS_REGISTRATION_CLOSED,
                Tournament::STATUS_IN_PROGRESS,
            ])
            ->whereNotNull('play_slots')
            ->chunkById(100, function ($tournaments) use ($now, &$disqualifiedCount) {
                foreach ($tournaments as $tournament) {
                    foreach ($tournament->normalizedPlaySlots() as $slot) {
                        if (! $slot['end_at'] || $slot['end_at']->gt($now)) {
                            continue;
                        }

                        $affected = $tournament->registrations()
                            ->whereIn('status', [
                                TournamentRegistration::STATUS_REGISTERED,
                                TournamentRegistration::STATUS_CHECKED_IN,
                            ])
                            ->where(function ($query) use ($slot) {
                                $query->whereNull('last_checked_in_slot_index')
                                    ->orWhere('last_checked_in_slot_index', '<', $slot['index']);
                            })
                            ->update([
                                'status' => TournamentRegistration::STATUS_DISQUALIFIED,
                                'eliminated_at' => now(),
                            ]);

                        if ($affected > 0) {
                            $disqualifiedCount += $affected;
                            Log::info("[TournamentScheduler] #{$tournament->id} disqualified {$affected} registrations for missed {$slot['label']}");
                        }
                    }
                }
            });

        return [
            'opened_registration' => $openedCount,
            'closed_registration' => $closedCount,
            'disqualified_no_show' => $disqualifiedCount,
        ];
    }
}
