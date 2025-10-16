<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    protected $table = 'payment_method';
    protected $primaryKey = 'idPaymentMethod';

    protected $fillable = [
        'amount', // monto
        'type_payment' // tipo de pago
    ];

    // Relación polimórfica para obtener el detalle específico
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

    // Método helper para obtener el detalle según el tipo
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
