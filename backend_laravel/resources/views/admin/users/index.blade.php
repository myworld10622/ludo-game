@extends('admin.layouts.app')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Registered users — wallet balance, match history, and account detail')

@section('content')
<style>
    .user-code { font-family: monospace; font-size: 13px; letter-spacing: 1px;
                 background: rgba(255,215,0,0.1); color: var(--gold); padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(255,215,0,0.2); }
    .wallet-bal { font-weight: 700; color: var(--green); }
    .match-count-btn { background: none; border: none; cursor: pointer; color: var(--gold);
                       font-weight: 700; font-size: 14px; text-decoration: underline; padding: 0; }
    .match-count-btn:hover { opacity: .75; }
    /* Modal */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8);
                     backdrop-filter: blur(6px); z-index: 1000; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: var(--card); border: 1px solid var(--line); border-radius: 16px; width: 92%; max-width: 720px;
                 max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;
                 box-shadow: 0 0 40px rgba(255,215,0,0.07), 0 24px 60px rgba(0,0,0,0.7); }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid var(--line-dim);
                    display: flex; justify-content: space-between; align-items: center; background: var(--card2); }
    .modal-title { font-size: 16px; font-weight: 700; color: var(--text); }
    .modal-close { background: var(--card); border: 1px solid var(--line-dim); cursor: pointer; font-size: 18px;
                   color: var(--muted); line-height: 1; border-radius: 8px; width: 32px; height: 32px;
                   display: flex; align-items: center; justify-content: center; transition: all .15s; }
    .modal-close:hover { color: var(--gold); border-color: rgba(255,215,0,0.3); }
    .modal-body { overflow-y: auto; padding: 16px 20px; }
    .modal-body table { width: 100%; border-collapse: collapse; }
    .modal-body th, .modal-body td { text-align: left; padding: 10px 8px;
                                     border-bottom: 1px solid var(--line-dim); font-size: 13px; color: var(--text); }
    .modal-body th { color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
    .modal-loading { text-align: center; padding: 40px; color: var(--muted); }
    .search-bar { display: flex; gap: 10px; margin-bottom: 16px; }
    .search-bar input { flex: 1; border: 1px solid #d1d5db; border-radius: 10px;
                        padding: 9px 14px; font-size: 14px; }
    .search-bar button { padding: 9px 18px; background: #0f766e; color: #fff;
                         border: 0; border-radius: 10px; cursor: pointer; font-size: 14px; }
    .btn-detail { display: inline-block; padding: 4px 10px; background: #e0f2fe; color: #0369a1;
                  border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; }
    .btn-detail:hover { background: #bae6fd; }
</style>

{{-- Search --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="search-bar">
    <input type="text" name="q" value="{{ request('q') }}"
           placeholder="Search by username, email or 8-digit user code…">
    <button type="submit">Search</button>
    @if(request('q'))
        <a href="{{ route('admin.users.index') }}" style="padding:9px 14px;color:#6b7280;">Clear</a>
    @endif
</form>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User Code</th>
                    <th>Username</th>
                    <th>Email / Mobile</th>
                    <th>Wallet</th>
                    <th>Matches</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><span class="user-code">{{ $user->user_code }}</span></td>
                        <td style="font-weight:600;">{{ $user->username }}</td>
                        <td class="muted" style="font-size:13px;">
                            {{ $user->email ?: '' }}
                            @if($user->email && $user->mobile)<br>@endif
                            {{ $user->mobile ?: '' }}
                            @if(!$user->email && !$user->mobile)—@endif
                        </td>
                        <td>
                            @if($user->primaryWallet)
                                <span class="wallet-bal">₹{{ number_format($user->primaryWallet->balance, 2) }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($user->matches_played > 0)
                                <button class="match-count-btn"
                                        onclick="loadMatches({{ $user->id }}, '{{ $user->username }}')">
                                    {{ $user->matches_played }}
                                </button>
                            @else
                                <span class="muted">0</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active && !$user->is_banned ? '' : 'off' }}">
                                {{ $user->is_banned ? 'Banned' : ($user->is_active ? 'Active' : 'Inactive') }}
                            </span>
                        </td>
                        <td class="muted" style="font-size:12px;">
                            {{ $user->last_login_at?->format('M d, Y H:i') ?? '—' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn-detail">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px 18px;">{{ $users->links() }}</div>
</div>

{{-- Match History Modal --}}
<div class="modal-overlay" id="matchModal" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">Match History</span>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="modal-loading">Loading…</div>
        </div>
    </div>
</div>

<script>
function loadMatches(userId, username) {
    document.getElementById('modalTitle').textContent = username + ' — Match History';
    document.getElementById('modalBody').innerHTML = '<div class="modal-loading">Loading…</div>';
    document.getElementById('matchModal').classList.add('open');

    fetch('/admin/users/' + userId + '/matches', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => { document.getElementById('modalBody').innerHTML = html; })
    .catch(() => { document.getElementById('modalBody').innerHTML = '<p style="color:red;padding:20px;">Failed to load.</p>'; });
}

function closeModal() {
    document.getElementById('matchModal').classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

@endsection
