<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserPanelSupportController extends Controller
{
    public function index(): View
    {
        $user = $this->user();

        $tickets = $user->supportTickets()
            ->with(['tournament', 'messages.senderAdmin', 'messages.senderUser'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        $tournaments = $user->tournaments()->latest()->get(['id', 'name']);

        return view('user.support.index', compact('tickets', 'tournaments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:50'],
            'tournament_id' => ['nullable', 'integer'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'tournament_id' => $validated['tournament_id'] ?: null,
            'subject' => $validated['subject'],
            'category' => $validated['category'] ?: 'general',
            'status' => 'open',
            'priority' => 'normal',
            'last_message_at' => now(),
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        return redirect()->route('panel.support.show', $ticket)->with('status', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless((int) $ticket->user_id === (int) $this->user()->id, 403);

        $ticket->load(['tournament', 'messages.senderAdmin', 'messages.senderUser']);
        $tickets = $this->user()->supportTickets()->latest('last_message_at')->latest()->get();
        $tournaments = $this->user()->tournaments()->latest()->get(['id', 'name']);

        return view('user.support.index', compact('tickets', 'tournaments', 'ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless((int) $ticket->user_id === (int) $this->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_user_id' => $this->user()->id,
            'message' => $validated['message'],
        ]);

        $ticket->update([
            'status' => 'open',
            'last_message_at' => now(),
            'closed_at' => null,
        ]);

        return back()->with('status', 'Reply sent to admin.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();
        return $user;
    }
}
