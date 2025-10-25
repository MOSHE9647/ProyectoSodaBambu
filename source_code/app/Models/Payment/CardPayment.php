<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CardPayment extends Model
{
    protected $table = 'card_payment';

    protected $fillable = [
        'reference',
    ];
    
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}