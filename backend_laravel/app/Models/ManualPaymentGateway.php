<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPaymentGateway extends Model
{
    protected $fillable = [
        'gateway_name',
        'type',
        'bank_name',
        'account_number',
        'ifsc_code',
        'account_holder',
        'upi_id',
        'qr_image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getQrImageUrlAttribute(): ?string
    {
        if (!$this->qr_image) return null;
        if (str_starts_with($this->qr_image, 'http')) return $this->qr_image;
        return url('storage/manual_gateways/'.$this->qr_image);
    }
}
