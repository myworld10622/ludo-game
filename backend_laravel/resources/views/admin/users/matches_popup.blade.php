@if ($players->isEmpty())
    <p style="text-align:center;color:#9ca3af;padding:30px 0;">No matches found for {{ $user->username }}.</p>
@else
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Tournament</th>
            <th>Round</th>
            <th>Match</th>
            <th>Result</th>
            <th>Score</th>
            <th>Finished</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($players as $i => $mp)
        @php
            $match   = $mp->match;
            $isWinner = $match?->winner_registration_id == $mp->registration_id;
            $result  = $mp->result ?? ($isWinner ? 'win' : 'loss');
            $resultColor = match($result) {
                'win'    => '#065f46',
                'loss'   => '#b42318',
                'forfeit', 'disconnected' => '#92400e',
                default  => '#374151',
            };
            $resultLabel = match($result) {
                'win'         => '🏆 Win',
                'loss'        => '💀 Loss',
                'forfeit'     => '⚠️ Forfeit',
                'disconnected'=> '🔌 DC',
                default       => ucfirst($result),
            };
        @endphp
        <tr>
            <td class="muted">{{ $i + 1 }}</td>
            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;">
                {{ $match?->tournament?->name ?? "T#{$match?->tournament_id}" }}
            </td>
            <td>R{{ $match?->round_number }}</td>
            <td>#{{ $match?->match_number }}</td>
            <td>
                <span style="font-weight:700;color:{{ $resultColor }};">{{ $resultLabel }}</span>
            </td>
            <td>{{ $mp->score ?? '—' }}</td>
            <td class="muted" style="font-size:12px;">
                {{ $mp->finished_at?->format('M d H:i') ?? '—' }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@if ($players->count() >= 50)
    <p style="text-align:center;color:#9ca3af;font-size:12px;margin-top:12px;">
        Showing last 50 matches.
        <a href="{{ route('admin.users.show', $user) }}" style="color:#2563eb;">View full history →</a>
    </p>
@endif
@endif
