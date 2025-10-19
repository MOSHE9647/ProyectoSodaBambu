<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    protected $table = 'payment_method';
    protected $primaryKey = 'idPaymentMethod';

    protected $fillable = [
        'amount', 
        'type_payment'
    ];

    public function sinpePayment()
    {
        return $this->hasOne(SinpePayment::class, 'idPaymentMethod', 'idPaymentMethod');
    }

    public function cardPayment()
    {
        return $this->hasOne(CardPayment::class, 'idPaymentMethod', 'idPaymentMethod');
    }

    public function cashPayment()
    {
        return $this->hasOne(CashPayment::class, 'idPaymentMethod', 'idPaymentMethod');
    }

    // Helper method to get the specific payment detail based on type_payment
    public function getDetalle()
    {
        switch ($this->tipo_pago) {
            case 'sinpe':
                return $this->sinpePayment;
            case 'card':
                return $this->cardPayment;
            case 'cash':
                return $this->cashPayment;
            default:
                return null;
        }
    }
}
