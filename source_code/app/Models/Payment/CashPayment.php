<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CashPayment extends Model
{
    protected $table = 'cash_payment';
    protected $primaryKey = 'idCashPayment';
    public $timestamps = false;

    protected $fillable = [
        'changeAmount', // monto vuelto
        'idPaymentMethod'
    ];

    // Relación con el método de pago (padre)
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'idPaymentMethod', 'idPaymentMethod');
    }
}
