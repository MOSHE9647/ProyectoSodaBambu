<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment\PaymentMethod;
use App\Models\Payment\SinpePayment;
use App\Models\Payment\CardPayment;
use App\Models\Payment\CashPayment;

class MethodPaymentController extends Controller
{
    /**
     * Show all payment methods
     */
    public function index()
    {
        $payments = PaymentMethod::with(['sinpePayment', 'cardPayment', 'cashPayment'])->get();
        return response()->json($payments);
    }

    /**
     * save a new payment method
     */
    public function store(Request $request)
{
    // validation
    $request->validate([
        'amount' => 'required|numeric|min:0',
        'type_payment' => 'required|in:sinpe,card,cash',

        // Validations for specific payment types
        'voucher' => 'required_if:type_payment,sinpe|string|nullable',
        'reference' => 'required_if:type_payment,card|string|nullable',
        'changeAmount' => 'required_if:type_payment,cash|numeric|nullable'
    ], [
        'amount.required' => 'El monto es obligatorio.',
        'amount.numeric' => 'El monto debe ser un número.',
        'amount.min' => 'El monto debe ser mayor que cero.',
        'type_payment.required' => 'El tipo de pago es obligatorio.',
        'voucher.required_if' => 'El comprobante es obligatorio para pagos SINPE.',
        'reference.required_if' => 'La referencia es obligatoria para pagos con tarjeta.',
        'changeAmount.required_if' => 'El monto de cambio es obligatorio para pagos en efectivo.'
    ]);

    try {
        // Create the parent payment method
        $paymentMethod = new PaymentMethod();
        $paymentMethod->amount = $request->amount;
        $paymentMethod->type_payment = $request->type_payment;
        $paymentMethod->save();

        // Create a child payment method based on the type
        switch ($request->type_payment) {
            case 'sinpe':
                $sinpePayment = new SinpePayment();
                $sinpePayment->voucher = $request->voucher;
                $sinpePayment->idPaymentMethod = $paymentMethod->idPaymentMethod;
                $sinpePayment->save();
                break;

            case 'card':
                $cardPayment = new CardPayment();
                $cardPayment->reference = $request->reference;
                $cardPayment->idPaymentMethod = $paymentMethod->idPaymentMethod;
                $cardPayment->save();
                break;

            case 'cash':
                $cashPayment = new CashPayment();
                $cashPayment->changeAmount = $request->changeAmount;
                $cashPayment->idPaymentMethod = $paymentMethod->idPaymentMethod;
                $cashPayment->save();
                break;
        }

        return response()->json([
            'message' => 'Método de pago registrado correctamente',
            'data' => $paymentMethod->load(['sinpePayment', 'cardPayment', 'cashPayment'])
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al registrar el método de pago',
            'details' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Show a specific payment method
     */
    public function show($id)
    {
        $payment = PaymentMethod::with(['sinpePayment', 'cardPayment', 'cashPayment'])
            ->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Update a payment method
     */
    public function update(Request $request, $id)
    {
        $payment = PaymentMethod::findOrFail($id);

        $payment->update([
            'monto' => $request->input('monto', $payment->monto),
        ]);

        return response()->json(['message' => 'Método de pago actualizado']);
    }

    /**
     * Delete a payment method
     */
    public function destroy($id)
    {
        $payment = PaymentMethod::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Método de pago eliminado']);
    }
}
