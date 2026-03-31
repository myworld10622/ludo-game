<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        $tickets = SupportTicket::query()
            ->with(['user', 'tournament', 'messages.senderAdmin', 'messages.senderUser'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        return view('admin.support.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'tournament', 'messages.senderAdmin', 'messages.senderUser']);
        $tickets = SupportTicket::query()->with(['user', 'tournament'])->latest('last_message_at')->latest()->get();

        return view('admin.support.index', compact('tickets', 'ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_admin_user_id' => Auth::guard('admin')->id(),
            'message' => $validated['message'],
        ]);

        $ticket->update([
            'status' => 'pending_user',
            'last_message_at' => now(),
            'closed_at' => null,
        ]);

        return back()->with('status', 'Reply sent to user.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,pending_user,closed'],
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ]);

        return back()->with('status', 'Ticket status updated.');
    }
}
