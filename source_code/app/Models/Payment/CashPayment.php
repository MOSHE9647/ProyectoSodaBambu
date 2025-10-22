<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CashPayment extends Model
{
    protected $table = 'cash_payment';
    
    protected $fillable = [
        'changeAmount',
        'payment_method_id'
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}