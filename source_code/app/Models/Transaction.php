<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'type',         // Enum: income, expense
        'concept',      // Description of the transaction
        'payment_id',   // Associated payment ID
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
    ];

    /**
    * Scope to filter only incomes.
     */
    public function scopeIncomes($query)
    {
        return $query->where('type', TransactionType::INCOME);
    }

    /**
    * Scope to filter only expenses.
     */
    public function scopeExpenses($query)
    {
        return $query->where('type', TransactionType::EXPENSE);
    }


    /**
     * Get the payment associated with the transaction.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
