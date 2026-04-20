<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterDetail extends Model
{
    protected $fillable = [
        'cash_register_report_id',
        'payment_method', // Enum: cash, card, sinpe
        'system_amount',
        'physical_amount',
        'difference',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'system_amount' => 'decimal:2',
        'physical_amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(CashRegisterReport::class);
    }
}
