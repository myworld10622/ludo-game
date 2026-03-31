<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentPrize;
use App\Models\User;
use App\Support\TournamentReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class UserPanelTournamentController extends Controller
{
    public function index(): View
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');

        return view('user.tournaments.index', $this->viewData($user));
    }

    public function edit(Tournament $tournament): View
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $this->ensureOwnership($tournament, $user);

        return view('user.tournaments.index', array_merge(
            $this->viewData($user),
            ['editingTournament' => $tournament->load('prizes')]
        ));
    }

    public function report(Tournament $tournament): View
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $this->ensureOwnership($tournament, $user);

        return view('user.tournaments.report', TournamentReportBuilder::build($tournament));
    }

    public function export(Tournament $tournament): StreamedResponse
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $this->ensureOwnership($tournament, $user);

        $filename = 'tournament-report-' . $tournament->id . '.csv';

        return response()->streamDownload(function () use ($tournament) {
            $report = TournamentReportBuilder::build($tournament);
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Tournament Report']);
            fputcsv($handle, ['Tournament ID', $report['tournament']->id]);
            fputcsv($handle, ['Tournament Name', $report['tournament']->name]);
            fputcsv($handle, ['Status', $report['tournament']->status]);
            fputcsv($handle, ['Created At', optional($report['tournament']->created_at)?->format('Y-m-d H:i:s')]);
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
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $this->ensureOwnership($tournament, $user);

        return view('reports.tournament_print', array_merge(
            TournamentReportBuilder::build($tournament),
            [
                'panelType' => 'user',
                'printIntent' => $request->query('mode') === 'pdf' ? 'pdf' : 'print',
                'backUrl' => route('panel.tournaments.report', $tournament),
            ]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated, $user) {
            $isPrivate = ($validated['type'] === 'private');
            $playSlots = $this->buildPlaySlots($validated);

            $tournament = Tournament::create([
                'name'                  => $validated['name'],
                'description'           => $validated['description'] ?? null,
                'creator_type'          => 'user',
                'creator_user_id'       => $user->id,
                'type'                  => $validated['type'],
                'format'                => $validated['format'],
                'bracket_mode'          => $validated['bracket_mode'] ?? 'auto',
                'status'                => Tournament::STATUS_DRAFT,
                'entry_fee'             => $validated['entry_fee'],
                'max_players'           => $validated['max_players'],
                'players_per_match'     => $validated['players_per_match'] ?? 4,
                'platform_fee_pct'      => $validated['platform_fee_pct'] ?? 20,
                'bot_allowed'           => false,
                'max_bot_pct'           => 0,
                'invite_password'       => $isPrivate ? ($validated['invite_password'] ?? null) : null,
                'invite_code'           => $isPrivate ? Tournament::generateInviteCode() : null,
                'registration_start_at' => $validated['registration_start_at'] ?? null,
                'registration_end_at'   => $validated['registration_end_at'] ?? null,
                'tournament_start_at'   => $validated['tournament_start_at'],
                'terms_conditions'      => $validated['terms_conditions'] ?? null,
                'play_slots'            => $playSlots,
                'is_approved'           => false,
                'requires_approval'     => true,
            ]);

            $this->syncPrizes($tournament, $validated);
        });

        return redirect()->route('panel.tournaments.index')->with('status', 'Tournament created in your panel.');
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $user = $this->user();
        $this->ensurePermission('manage_tournaments');
        $this->ensureOwnership($tournament, $user);
        $validated = $request->validate($this->rules($tournament->id));

        DB::transaction(function () use ($validated, $tournament) {
            $isPrivate = ($validated['type'] === 'private');
            $playSlots = $this->buildPlaySlots($validated);

            $tournament->update([
                'name'                  => $validated['name'],
                'description'           => $validated['description'] ?? null,
                'type'                  => $validated['type'],
                'format'                => $validated['format'],
                'bracket_mode'          => $validated['bracket_mode'] ?? 'auto',
                'status'                => $tournament->status,
                'entry_fee'             => $validated['entry_fee'],
                'max_players'           => $validated['max_players'],
                'players_per_match'     => $validated['players_per_match'] ?? 4,
                'platform_fee_pct'      => $validated['platform_fee_pct'] ?? 20,
                'bot_allowed'           => false,
                'max_bot_pct'           => 0,
                'invite_password'       => $isPrivate ? ($validated['invite_password'] ?? $tournament->invite_password) : null,
                'invite_code'           => $isPrivate ? ($tournament->invite_code ?? Tournament::generateInviteCode()) : null,
                'registration_start_at' => $validated['registration_start_at'] ?? null,
                'registration_end_at'   => $validated['registration_end_at'] ?? null,
                'tournament_start_at'   => $validated['tournament_start_at'],
                'terms_conditions'      => $validated['terms_conditions'] ?? null,
                'play_slots'            => $playSlots,
            ]);

            $tournament->prizes()->delete();
            $this->syncPrizes($tournament, $validated);
        });

        return redirect()->route('panel.tournaments.index')->with('status', 'Tournament updated successfully.');
    }

    public function approve(Tournament $tournament): RedirectResponse
    {
        abort(403, 'Only admin can approve tournaments.');
    }

    public function forceLive(Tournament $tournament): RedirectResponse
    {
        abort(403, 'Only admin can force tournaments live.');
    }

    public function adjustFakeRegistrations(Request $request, Tournament $tournament): RedirectResponse
    {
        abort(403, 'Only admin can manage fake registrations.');
    }

    private function viewData(User $user): array
    {
        $tournaments = Tournament::query()
            ->where('creator_type', 'user')
            ->where('creator_user_id', $user->id)
            ->with('prizes')
            ->withCount([
                'matches as running_matches_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'waiting', 'in_progress']),
                'matches as completed_matches_count' => fn ($q) => $q->where('status', 'completed'),
                'registrations as real_registrations_count' => fn ($q) => $q->where('is_bot', false),
                'registrations as winner_registrations_count' => fn ($q) => $q->where('status', 'winner'),
            ])
            ->latest()
            ->get();

        return [
            'tournaments' => $tournaments,
            'editingTournament' => null,
            'panelPermissions' => $user->panelPermissions(),
        ];
    }

    private function syncPrizes(Tournament $tournament, array $data): void
    {
        $entryFee = (float) ($data['entry_fee'] ?? 0);
        $maxPlayers = (int) ($data['max_players'] ?? 0);
        $feePct = (float) ($data['platform_fee_pct'] ?? 20);
        $prizePool = $entryFee * $maxPlayers * (1 - $feePct / 100);

        for ($pos = 1; $pos <= 5; $pos++) {
            $pct = (float) ($data["prize_pct_{$pos}"] ?? 0);
            if ($pct <= 0) {
                continue;
            }

            TournamentPrize::create([
                'tournament_id' => $tournament->id,
                'position' => $pos,
                'prize_pct' => $pct,
                'prize_amount' => round($prizePool * $pct / 100, 2),
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
            'entry_fee'             => ['required', 'numeric', 'min:0'],
            'max_players'           => ['required', 'integer', 'in:4,8,16,32,64,112'],
            'players_per_match'     => ['nullable', 'integer', 'in:2,4'],
            'platform_fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
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

    private function ensureOwnership(Tournament $tournament, User $user): void
    {
        abort_unless(
            $tournament->creator_type === 'user' && (int) $tournament->creator_user_id === (int) $user->id,
            403,
            'You cannot access this tournament.'
        );
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless($this->user()->hasPanelPermission($permission), 403, 'This panel option is disabled by admin.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();
        return $user;
    }
}
