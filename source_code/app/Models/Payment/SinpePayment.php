<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class SinpePayment extends Model
{
    protected $table = 'sinpe_payment';
    protected $primaryKey = 'idSinpePayment';
    public $timestamps = false;

    protected $fillable = [
        'voucher', // comprobante
        'idPaymentMethod' 
    ];

    // Relación con el método de pago (padre)
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'idPaymentMethod', 'idPaymentMethod');
    }
}
