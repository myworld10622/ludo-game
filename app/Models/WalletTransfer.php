<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransfer extends Model
{
    protected $fillable = [
        'transfer_uuid',
        'sender_user_id',
        'receiver_user_id',
        'sender_wallet_id',
        'receiver_wallet_id',
        'sender_wallet_transaction_id',
        'receiver_wallet_transaction_id',
        'amount',
        'currency',
        'status',
        'note',
        'meta',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    public function receiverWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }

    public function senderTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'sender_wallet_transaction_id');
    }

    public function receiverTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'receiver_wallet_transaction_id');
    }
}
