<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CardPayment extends Model
{
    protected $table = 'card_payment';
    protected $primaryKey = 'idCardPayment';
    public $timestamps = false;

    protected $fillable = [
        'reference', // referencia
        'idPaymentMethod'
    ];

    // Relación con el método de pago (padre)
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'idPaymentMethod', 'idPaymentMethod');
    }
}
