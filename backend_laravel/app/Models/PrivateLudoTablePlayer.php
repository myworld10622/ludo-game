<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateLudoTablePlayer extends Model
{
    protected $fillable = [
        'private_table_id', 'user_id', 'fee_paid', 'wallet_transaction_id', 'status',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(PrivateLudoTable::class, 'private_table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
