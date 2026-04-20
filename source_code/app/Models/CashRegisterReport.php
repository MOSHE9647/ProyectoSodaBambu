<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterReport extends Model
{
    protected $fillable = [
        'cash_register_id',
        'total_system_amount',
        'total_physical_amount',
        'total_difference',
        'notes',
    ];

    /**
     * The cash register session to which this report belongs.
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Breakdown by payment method (Cash, Card, SINPE).
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashRegisterDetail::class);
    }
}
