<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\WalletTransfer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletTransferController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $transfers = WalletTransfer::query()
            ->with(['sender', 'receiver', 'senderTransaction', 'receiverTransaction'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('transfer_uuid', 'like', '%'.$search.'%')
                    ->orWhereHas('sender', function ($userQuery) use ($search) {
                        $userQuery->where('username', 'like', '%'.$search.'%')
                            ->orWhere('user_code', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('receiver', function ($userQuery) use ($search) {
                        $userQuery->where('username', 'like', '%'.$search.'%')
                            ->orWhere('user_code', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.wallet-transfers.index', [
            'transfers' => $transfers,
            'search' => $search,
        ]);
    }
}
