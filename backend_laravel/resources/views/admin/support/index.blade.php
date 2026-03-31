@extends('admin.layouts.app')

@section('title', 'Support Tickets')
@section('heading', 'Support Tickets')
@section('subheading', 'Review user tickets, respond, and manage approval discussions')

@section('content')
@php($activeTicket = $ticket ?? $tickets->first())
<div class="split-wide-narrow">
    <div class="panel">
        <div class="section-title" style="font-size:18px;font-weight:700;margin-bottom:14px;">Ticket Inbox</div>
        <div class="stack">
            @forelse($tickets as $item)
                <a href="{{ route('admin.support.show', $item) }}" style="display:block;padding:14px;border:1px solid var(--line);border-radius:16px;background:{{ $activeTicket && $activeTicket->id === $item->id ? 'linear-gradient(180deg,#eef4ff,#fff)' : '#fff' }};">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-weight:800;">{{ $item->subject }}</div>
                            <div class="muted" style="font-size:12px;">{{ $item->user?->username }} · {{ $item->user?->user_code }} · {{ ucfirst(str_replace('_', ' ', $item->status)) }}</div>
                            @if($item->tournament)
                                <div class="muted" style="font-size:12px;">Tournament: {{ $item->tournament->name }}</div>
                            @endif
                        </div>
                        <span class="badge">{{ ucfirst($item->priority) }}</span>
                    </div>
                </a>
            @empty
                <div class="muted">No support tickets available.</div>
            @endforelse
        </div>
    </div>

    <div class="panel">
        @if($activeTicket)
            <div class="header-row">
                <div>
                    <div style="font-size:22px;font-weight:800;">{{ $activeTicket->subject }}</div>
                    <div class="muted" style="font-size:13px;">
                        {{ $activeTicket->user?->username }} · {{ $activeTicket->user?->user_code }}
                        @if($activeTicket->tournament)
                            · {{ $activeTicket->tournament->name }}
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.support.status', $activeTicket) }}" style="display:flex;gap:8px;align-items:center;">
                    @csrf
                    <select name="status" style="min-width:160px;">
                        @foreach(['open' => 'Open', 'pending_user' => 'Pending User', 'closed' => 'Closed'] as $key => $label)
                            <option value="{{ $key }}" {{ $activeTicket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">Update</button>
                </form>
            </div>

            <div class="stack" style="margin:16px 0;">
                @foreach($activeTicket->messages->sortBy('created_at') as $message)
                    @php($isAdmin = $message->sender_type === 'admin')
                    <div style="padding:14px;border-radius:16px;border:1px solid var(--line);background:{{ $isAdmin ? 'linear-gradient(180deg,#eef4ff,#fff)' : 'linear-gradient(180deg,#f8fbff,#fff)' }};">
                        <div style="font-size:12px;font-weight:800;margin-bottom:6px;color:{{ $isAdmin ? 'var(--brand-dark)' : 'var(--accent)' }};">
                            {{ $isAdmin ? ($message->senderAdmin?->name ?? 'Admin') : ($message->senderUser?->username ?? 'User') }}
                            · {{ $message->created_at?->format('d M Y, h:i A') }}
                        </div>
                        <div style="white-space:pre-wrap;line-height:1.7;">{{ $message->message }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.support.reply', $activeTicket) }}" class="stack">
                @csrf
                <div><label>Reply to user</label><textarea name="message" required></textarea></div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        @else
            <div class="muted">Select a ticket to start chat with the user.</div>
        @endif
    </div>
</div>
@endsection
