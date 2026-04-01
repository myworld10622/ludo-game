<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tournament;
use App\Models\TournamentPrize;
use App\Support\TournamentReportBuilder;
use App\Services\Tournament\TournamentStatusAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        return view('admin.tournaments.index', $this->viewData());
    }

    public function create(): View
    {
        return view('admin.tournaments.index', $this->viewData());
    }

    public function edit(Tournament $tournament): View
    {
        return view('admin.tournaments.index', array_merge(
            $this->viewData(),
            ['editingTournament' => $tournament->load('prizes')]
        ));
    }

    public function report(Tournament $tournament): View
    {
        return view('admin.tournaments.report', TournamentReportBuilder::build($tournament));
    }

    public function export(Tournament $tournament): StreamedResponse
    {
        $filename = 'admin-tournament-report-' . $tournament->id . '.csv';

        return response()->streamDownload(function () use ($tournament) {
            $report = TournamentReportBuilder::build($tournament);
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Tournament Report']);
            fputcsv($handle, ['Tournament ID', $report['tournament']->id]);
            fputcsv($handle, ['Tournament Name', $report['tournament']->name]);
            fputcsv($handle, ['Owner Type', $report['tournament']->creator_type]);
            fputcsv($handle, ['Owner Username', $report['tournament']->creator?->username ?? 'Admin']);
            fputcsv($handle, ['Status', $report['tournament']->status]);
            fputcsv($handle, []);

            fputcsv($handle, ['Summary']);
            foreach ($report['stats'] as $label => $value) {
                fputcsv($handle, [str_replace('_', ' ', $label), $value]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Winners']);
            fputcsv($handle, ['Position', 'Winner', 'User ID', 'Prize Amount', 'Payout Status']);
            foreach ($report['prizes'] as $prize) {
                fputcsv($handle, [
                    $prize->position,
                    $prize->winner?->username ?? 'Pending',
                    $prize->winner?->user_code ?? '',
                    $prize->prize_amount,
                    $prize->payout_status,
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Registrations']);
            fputcsv($handle, ['Player', 'User ID', 'Status', 'Final Position', 'Prize Won', 'Registered At', 'Eliminated At']);
            foreach ($report['registrations'] as $registration) {
                fputcsv($handle, [
                    $registration->displayName(),
                    $registration->user?->user_code ?? 'Bot',
                    $registration->status,
                    $registration->final_position,
                    $registration->prize_won,
                    optional($registration->registered_at)?->format('Y-m-d H:i:s'),
                    optional($registration->eliminated_at)?->format('Y-m-d H:i:s'),
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Matches']);
            fputcsv($handle, ['Round', 'Match', 'Status', 'Players', 'Winner', 'Scheduled At', 'Ended At', 'Override']);
            foreach ($report['rounds'] as $round) {
                foreach ($round['matches'] as $match) {
                    fputcsv($handle, [
                        $round['round_number'],
                        $match->match_number,
                        $match->status,
                        $match->players->map(fn ($player) => $player->registration?->displayName() ?? 'Unknown')->join(' | '),
                        $match->winner?->displayName() ?? $match->forcedWinner?->displayName() ?? 'Pending',
                        optional($match->scheduled_at)?->format('Y-m-d H:i:s'),
                        optional($match->ended_at)?->format('Y-m-d H:i:s'),
                        $match->is_admin_override ? ($match->admin_override_note ?: 'Yes') : 'No',
                    ]);
                }
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Financials']);
            fputcsv($handle, ['Time', 'User', 'Type', 'Amount', 'Description']);
            foreach ($report['financialRows'] as $row) {
                fputcsv($handle, [
                    optional($row->created_at)?->format('Y-m-d H:i:s'),
                    $row->user?->username ?? 'System',
                    $row->type,
                    $row->amount,
                    $row->description,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Tournament $tournament, Request $request): View
    {
        return view('reports.tournament_print', array_merge(
            TournamentReportBuilder::build($tournament),
            [
                'panelType' => 'admin',
                'printIntent' => $request->query('mode') === 'pdf' ? 'pdf' : 'print',
                'backUrl' => route('admin.tournaments.report', $tournament),
            ]
        ));
    }

    private function viewData(): array
    {
        $all = Tournament::query()
            ->with('prizes', 'creator')
            ->withCount([
                'registrations',
                'matches as completed_matches_count' => fn ($q) => $q->where('status', 'completed'),
                'matches as pending_matches_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'disputed', 'forfeited']),
            ])
            ->latest()
            ->get();

        $adminTournaments = $all->where('creator_type', 'admin')->values();
        $userTournaments = $all->where('creator_type', 'user')->values();

        return [
            'adminTournaments'  => $adminTournaments,
            'userTournaments'   => $userTournaments,
            'editingTournament' => null,
            'tournamentStats' => [
                'total' => $all->count(),
                'admin_total' => $adminTournaments->count(),
                'user_total' => $userTournaments->count(),
                'live' => $all->whereIn('status', ['registration_open', 'in_progress'])->count(),
                'completed' => $all->where('status', 'completed')->count(),
                'pending_approval' => $all->where('is_approved', false)->count(),
            ],
            'recentTournamentReports' => $all->take(6)->values(),
            'pendingApprovalTournaments' => $userTournaments
                ->where('is_approved', false)
                ->take(8)
                ->values(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated) {
            $isPrivate = ($validated['type'] === 'private');
            $playSlots = $this->buildPlaySlots($validated);

            $tournament = Tournament::create([
                'name'                 => $validated['name'],
                'description'          => $validated['description'] ?? null,
                'creator_type'         => 'admin',
                'type'                 => $validated['type'],
                'format'               => $validated['format'],
                'bracket_mode'         => $validated['bracket_mode'] ?? 'auto',
                'status'               => $validated['status'],
                'entry_fee'            => $validated['entry_fee'],
                'max_players'          => $validated['max_players'],
                'players_per_match'    => $validated['players_per_match'] ?? 4,
                'platform_fee_pct'     => $validated['platform_fee_pct'] ?? 20,
                'bot_allowed'          => (bool) ($validated['bot_allowed'] ?? false),
                'max_bot_pct'          => $validated['max_bot_pct'] ?? 5,
                'bot_start_policy'     => $validated['bot_start_policy'] ?? 'hybrid',
                'min_real_players_to_start' => $validated['min_real_players_to_start'] ?? 1,
                'bot_fill_after_seconds' => $validated['bot_fill_after_seconds'] ?? 8,
                'invite_password'      => $isPrivate ? ($validated['invite_password'] ?? null) : null,
                'invite_code'          => $isPrivate ? Tournament::generateInviteCode() : null,
                'registration_start_at' => $validated['registration_start_at'] ?? null,
                'registration_end_at'  => $validated['registration_end_at'] ?? null,
                'tournament_start_at'  => $validated['tournament_start_at'],
                'terms_conditions'     => $validated['terms_conditions'] ?? null,
                'play_slots'           => $playSlots,
                'is_approved'          => true,
            ]);

            $this->syncPrizes($tournament, $validated);
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', 'Tournament created successfully.');
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate($this->rules($tournament->id));

        DB::transaction(function () use ($tournament, $validated) {
            $isPrivate = ($validated['type'] === 'private');
            $playSlots = $this->buildPlaySlots($validated);

            $tournament->update([
                'name'                 => $validated['name'],
                'description'          => $validated['description'] ?? null,
                'type'                 => $validated['type'],
                'format'               => $validated['format'],
                'bracket_mode'         => $validated['bracket_mode'] ?? 'auto',
                'status'               => $validated['status'],
                'entry_fee'            => $validated['entry_fee'],
                'max_players'          => $validated['max_players'],
                'players_per_match'    => $validated['players_per_match'] ?? 4,
                'platform_fee_pct'     => $validated['platform_fee_pct'] ?? 20,
                'bot_allowed'          => (bool) ($validated['bot_allowed'] ?? false),
                'max_bot_pct'          => $validated['max_bot_pct'] ?? 5,
                'bot_start_policy'     => $validated['bot_start_policy'] ?? 'hybrid',
                'min_real_players_to_start' => $validated['min_real_players_to_start'] ?? 1,
                'bot_fill_after_seconds' => $validated['bot_fill_after_seconds'] ?? 8,
                'invite_password'      => $isPrivate ? ($validated['invite_password'] ?? $tournament->invite_password) : null,
                'invite_code'          => $isPrivate ? ($tournament->invite_code ?? Tournament::generateInviteCode()) : null,
                'registration_start_at' => $validated['registration_start_at'] ?? null,
                'registration_end_at'  => $validated['registration_end_at'] ?? null,
                'tournament_start_at'  => $validated['tournament_start_at'],
                'terms_conditions'     => $validated['terms_conditions'] ?? null,
                'play_slots'           => $playSlots,
            ]);

            $tournament->prizes()->delete();
            $this->syncPrizes($tournament, $validated);
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', 'Tournament updated successfully.');
    }

    public function approve(Tournament $tournament): RedirectResponse
    {
        if ($tournament->is_approved) {
            return back()->with('status', 'Tournament is already approved.');
        }

        $tournament->update([
            'is_approved'       => true,
            'requires_approval' => false,
            'approved_at'       => now(),
            'rejected_at'       => null,
            'rejection_reason'  => null,
            'status'            => $tournament->status === Tournament::STATUS_DRAFT
                ? Tournament::STATUS_REGISTRATION_OPEN
                : $tournament->status,
        ]);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', "Tournament \"{$tournament->name}\" approved and published.");
    }

    public function reject(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($tournament, $validated) {
            $tournament->update([
                'is_approved' => false,
                'requires_approval' => true,
                'rejected_at' => now(),
                'rejection_reason' => $validated['reason'],
                'approved_at' => null,
            ]);

            if ($tournament->creator_type === 'user' && $tournament->creator_user_id) {
                $ticket = SupportTicket::create([
                    'user_id' => $tournament->creator_user_id,
                    'tournament_id' => $tournament->id,
                    'subject' => 'Tournament review: ' . $tournament->name,
                    'category' => 'tournament_approval',
                    'status' => 'open',
                    'priority' => 'high',
                    'last_message_at' => now(),
                ]);

                SupportTicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'sender_type' => 'admin',
                    'sender_admin_user_id' => Auth::guard('admin')->id(),
                    'message' => "Tournament rejected by admin.\n\nReason: {$validated['reason']}",
                ]);
            }
        });

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', "Tournament \"{$tournament->name}\" rejected and user notified.");
    }

    // ── POST /admin/tournaments/{tournament}/force-live ───────────────────────
    // Emergency: instantly publish any stuck draft/approved tournament.
    public function forceLive(Tournament $tournament): RedirectResponse
    {
        if (in_array($tournament->status, [Tournament::STATUS_COMPLETED, Tournament::STATUS_CANCELLED])) {
            return back()->with('status', "Cannot force-live a {$tournament->status} tournament.");
        }

        $tournament->update([
            'is_approved' => true,
            'status'      => Tournament::STATUS_REGISTRATION_OPEN,
        ]);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', "✅ \"{$tournament->name}\" is now LIVE (registration open).");
    }

    // ── POST /admin/tournaments/{tournament}/fake-registrations ──────────────
    // Inflate or deflate the displayed registration count (public interest only).
    public function adjustFakeRegistrations(Request $request, Tournament $tournament): RedirectResponse
    {
        $request->validate([
            'fake_count' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $max   = $tournament->max_players - $tournament->current_players;
        $count = min((int) $request->fake_count, max(0, $max));

        $tournament->update(['fake_registrations_count' => $count]);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', "Fake registration count set to {$count} for \"{$tournament->name}\".");
    }

    // ── POST /admin/tournaments/run-scheduler ─────────────────────────────────
    // Emergency: manually run the cron job that advances tournament statuses.
    public function runScheduler(TournamentStatusAutomationService $service): RedirectResponse
    {
        $result = $service->advanceStatuses();

        $msg = "🔄 Scheduler ran successfully. "
            . "draft→open: {$result['opened_registration']}, "
            . "open→closed: {$result['closed_registration']}, "
            . "no-show disqualified: " . ($result['disqualified_no_show'] ?? 0) . ".";

        return redirect()
            ->route('admin.tournaments.index')
            ->with('status', $msg);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function syncPrizes(Tournament $tournament, array $data): void
    {
        $entryFee   = (float) ($data['entry_fee'] ?? 0);
        $maxPlayers = (int)   ($data['max_players'] ?? 0);
        $feePct     = (float) ($data['platform_fee_pct'] ?? 20);
        $prizePool  = $entryFee * $maxPlayers * (1 - $feePct / 100);

        for ($pos = 1; $pos <= 5; $pos++) {
            $pct = (float) ($data["prize_pct_{$pos}"] ?? 0);
            if ($pct <= 0) continue;

            TournamentPrize::create([
                'tournament_id' => $tournament->id,
                'position'      => $pos,
                'prize_pct'     => $pct,
                'prize_amount'  => round($prizePool * $pct / 100, 2),
                'payout_status' => 'pending',
            ]);
        }
    }

    private function rules(?int $exceptId = null): array
    {
        return [
            'name'                  => ['required', 'string', 'max:150'],
            'description'           => ['nullable', 'string'],
            'type'                  => ['required', 'in:public,private'],
            'format'                => ['required', 'in:knockout,round_robin,double_elim,group_knockout'],
            'bracket_mode'          => ['nullable', 'in:auto,manual'],
            'status'                => ['required', 'in:draft,registration_open,registration_closed,in_progress,completed,cancelled'],
            'entry_fee'             => ['required', 'numeric', 'min:0'],
            'max_players'           => ['required', 'integer', 'in:4,8,16,32,64'],
            'players_per_match'     => ['nullable', 'integer', 'in:2,4'],
            'platform_fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bot_allowed'           => ['nullable', 'boolean'],
            'max_bot_pct'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bot_start_policy'      => ['nullable', 'in:disabled,fill_missing,replace_offline,hybrid'],
            'min_real_players_to_start' => ['nullable', 'integer', 'min:1', 'max:4'],
            'bot_fill_after_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
            'invite_password'       => ['nullable', 'string', 'max:50'],
            'registration_start_at' => ['nullable', 'date'],
            'registration_end_at'   => ['nullable', 'date', 'after_or_equal:registration_start_at'],
            'tournament_start_at'   => ['required', 'date'],
            'terms_conditions'      => ['nullable', 'string'],
            'play_slot_start_1'     => ['nullable', 'date'],
            'play_slot_end_1'       => ['nullable', 'date', 'after:play_slot_start_1'],
            'play_slot_start_2'     => ['nullable', 'date'],
            'play_slot_end_2'       => ['nullable', 'date', 'after:play_slot_start_2'],
            'play_slot_start_3'     => ['nullable', 'date'],
            'play_slot_end_3'       => ['nullable', 'date', 'after:play_slot_start_3'],
            'play_slot_start_4'     => ['nullable', 'date'],
            'play_slot_end_4'       => ['nullable', 'date', 'after:play_slot_start_4'],
            'play_slot_start_5'     => ['nullable', 'date'],
            'play_slot_end_5'       => ['nullable', 'date', 'after:play_slot_start_5'],
            'prize_pct_1'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prize_pct_2'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prize_pct_3'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prize_pct_4'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'prize_pct_5'           => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function buildPlaySlots(array $data): array
    {
        $slots = [];

        for ($i = 1; $i <= 5; $i++) {
            $startAt = $data["play_slot_start_{$i}"] ?? null;
            $endAt = $data["play_slot_end_{$i}"] ?? null;

            if (! $startAt || ! $endAt) {
                continue;
            }

            $slots[] = [
                'label' => "Slot {$i}",
                'start_at' => \Illuminate\Support\Carbon::parse($startAt)->toIso8601String(),
                'end_at' => \Illuminate\Support\Carbon::parse($endAt)->toIso8601String(),
            ];
        }

        return $slots;
    }
}
