<?php

namespace App\Models;

use app\Enums\CashRegisterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'status', // Enum : open, closed
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'status' => CashRegisterStatus::class,
    ];

    /**
    * The user (collaborator/administrator) who opened the cash register.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
    * Accounting movements (Transactions) made during this session.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
    * The final validation report (CashClosure).
     */
    public function report(): HasOne
    {
        return $this->hasOne(CashRegisterReport::class);
    }
}
