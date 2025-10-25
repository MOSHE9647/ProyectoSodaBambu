<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    protected $table = 'payment_method';

    protected $fillable = [
        'amount', 
        'type_payment'
    ];

    public function sinpePayment()
    {
        return $this->hasOne(SinpePayment::class);
    }

    public function cardPayment()
    {
        return $this->hasOne(CardPayment::class);
    }

    public function cashPayment()
    {
        return $this->hasOne(CashPayment::class);
    }


    // Helper method to get the specific payment detail based on type_payment
    public function getDetalle()
    {
        switch ($this->type_payment) {
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
