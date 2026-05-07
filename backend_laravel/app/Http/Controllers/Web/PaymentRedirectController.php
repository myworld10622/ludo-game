<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentRedirectController extends Controller
{
    public function deposit(Request $request, string $trx): View
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_unless(Schema::hasTable('rox_gateway_transactions'), 404);

        $transaction = DB::table('rox_gateway_transactions')->where('trx', $trx)->first();
        abort_unless($transaction, 404);

        $paymentUrl = (string) ($transaction->payment_url ?? '');
        $iframeAllowed = $paymentUrl !== '';

        return view('payments.deposit-wrapper', [
            'transaction' => $transaction,
            'paymentUrl' => $paymentUrl,
            'iframeAllowed' => $iframeAllowed,
        ]);
    }
}
