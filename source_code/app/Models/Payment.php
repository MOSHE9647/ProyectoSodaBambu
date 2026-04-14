<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PaymentMethod;



class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'method',       // Enum: sinpe, cash, card
        'change_amount', // refunded in cash if it's a sale
        'reference',    // Card or SINPE receipt number
        'date',
        'origin_id',    // Sales, purchase, contract, or payroll ID
        'origin_type',  // Sales, purchase, contract, or payroll class
    
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'date' => 'datetime',
        'method' => PaymentMethod::class,
    ];

    /**
     * Obtains the related type (purchase, sale, contract or payroll payment)
     */
    public function origin():MorphTo
    {
        return $this->morphTo();
    }
    

    /**
     * Get the transaction associated with the payment.
     *
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
