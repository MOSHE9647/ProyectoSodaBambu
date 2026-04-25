<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
