<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\View\View;

class WalletTransactionController extends Controller
{
    public function index(): View
    {
        return view('admin.wallet-transactions.index', [
            'transactions' => WalletTransaction::query()
                ->with(['user', 'wallet', 'game', 'tournament'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
