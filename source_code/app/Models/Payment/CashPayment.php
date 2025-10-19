<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CashPayment extends Model
{
    protected $table = 'cash_payment';
    protected $primaryKey = 'idCashPayment';
    public $timestamps = false;

    protected $fillable = [
        'changeAmount',
        'idPaymentMethod'
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'idPaymentMethod', 'idPaymentMethod');
    }
}
