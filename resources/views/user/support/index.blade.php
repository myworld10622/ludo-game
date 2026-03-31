@extends('user.layouts.app')

@section('title', 'Support Chat')
@section('heading', 'Support Chat')
@section('subheading', 'Raise a ticket, discuss tournament approval, and reply to admin')

@section('content')
@php($activeTicket = $ticket ?? $tickets->first())
<div class="split-wide-narrow">
    <div class="panel">
        <div class="section-title">Create Ticket</div>
        <form method="POST" action="{{ route('panel.support.store') }}" class="stack-compact">
            @csrf
            <div><label>Subject</label><input name="subject" value="{{ old('subject') }}" required></div>
            <div><label>Category</label><select name="category"><option value="general">General</option><option value="tournament_approval">Tournament Approval</option><option value="payment">Payment</option><option value="technical">Technical</option></select></div>
            <div><label>Tournament</label><select name="tournament_id"><option value="">Select tournament (optional)</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}">{{ $tournament->name }}</option>@endforeach</select></div>
            <div><label>Message</label><textarea name="message" required>{{ old('message') }}</textarea></div>
            <button type="submit" class="btn">Create Ticket</button>
        </form>

        <div class="section-title" style="margin-top:18px;">My Tickets</div>
        <div class="stack-compact">
            @forelse($tickets as $item)
                <a href="{{ route('panel.support.show', $item) }}" style="display:block;padding:14px;border:1px solid var(--line);border-radius:14px;background:{{ $activeTicket && $activeTicket->id === $item->id ? 'linear-gradient(180deg,#fff5ea,#fff)' : '#fff' }};">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-weight:700;">{{ $item->subject }}</div>
                            <div class="muted" style="font-size:12px;">{{ ucfirst(str_replace('_', ' ', $item->status)) }} · {{ $item->last_message_at?->format('d M Y, h:i A') ?? '—' }}</div>
                        </div>
                        <span class="badge">{{ ucfirst($item->priority) }}</span>
                    </div>
                </a>
            @empty
                <div class="muted">No support tickets yet.</div>
            @endforelse
        </div>
    </div>

    <div class="panel">
        @if($activeTicket)
            <div class="card-head">
                <div>
                    <div class="card-title">{{ $activeTicket->subject }}</div>
                    <div class="muted" style="font-size:13px;">
                        {{ ucfirst(str_replace('_', ' ', $activeTicket->status)) }}
                        @if($activeTicket->tournament)
                            · {{ $activeTicket->tournament->name }}
                        @endif
                    </div>
                </div>
                <span class="badge">{{ ucfirst($activeTicket->category) }}</span>
            </div>

            <div class="stack-compact" style="margin:16px 0;">
                @foreach($activeTicket->messages->sortBy('created_at') as $message)
                    @php($isUser = $message->sender_type === 'user')
                    <div style="padding:14px;border-radius:16px;border:1px solid var(--line);background:{{ $isUser ? 'linear-gradient(180deg,#fff8f1,#fff)' : 'linear-gradient(180deg,#eefaf7,#fff)' }};">
                        <div style="font-size:12px;font-weight:700;margin-bottom:6px;color:{{ $isUser ? 'var(--brand-dark)' : 'var(--accent)' }};">
                            {{ $isUser ? 'You' : ($message->senderAdmin?->name ?? 'Admin') }}
                            · {{ $message->created_at?->format('d M Y, h:i A') }}
                        </div>
                        <div style="white-space:pre-wrap;line-height:1.7;">{{ $message->message }}</div>
                    </div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('panel.support.reply', $activeTicket) }}" class="stack-compact">
                @csrf
                <div><label>Reply</label><textarea name="message" required></textarea></div>
                <button type="submit" class="btn">Send Reply</button>
            </form>
        @else
            <div class="muted">Select or create a support ticket to start chatting with admin.</div>
        @endif
    </div>
</div>
@endsection
