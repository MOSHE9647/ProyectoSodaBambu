<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class SinpePayment extends Model
{
    protected $table = 'sinpe_payment';

    protected $fillable = [
        'voucher', 
        'payment_method_id'
    ];

    public function paymentMethod()
    {
       return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}