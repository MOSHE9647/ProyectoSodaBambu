<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment\PaymentMethod;
use App\Models\Payment\SinpePayment;
use App\Models\Payment\CardPayment;
use App\Models\Payment\CashPayment;
use Yajra\DataTables\DataTables;

class MethodPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $payments = PaymentMethod::with(['sinpePayment', 'cardPayment', 'cashPayment'])
                ->select('payment_method.*');

            return DataTables::of($payments)
                ->addColumn('id', function ($payment) {
                    return $payment->id;
                })
                ->editColumn('type_payment', function ($payment) {
                    return $this->getPaymentTypeLabel($payment->type_payment);
                })
                ->editColumn('amount', function ($payment) {
                    return '₡' . number_format($payment->amount, 2);
                })
                ->editColumn('created_at', function ($payment) {
                    return $payment->created_at->format('d-m-Y');
                })
                ->make(true);
        }

        return view('models.method-payments.index');
    }

    public function create()
    {
        return view('models.method-payments.create');
    }

    public function edit()
    {
        return view('models.method-payments.edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type_payment' => 'required|in:sinpe,card,cash',
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
                    $sinpePayment->payment_method_id = $paymentMethod->id;
                    $sinpePayment->save();
                    break;

                case 'card':
                    $cardPayment = new CardPayment();
                    $cardPayment->reference = $request->reference;
                    $cardPayment->payment_method_id = $paymentMethod->id;
                    $cardPayment->save();
                    break;

                case 'cash':
                    $cashPayment = new CashPayment();
                    $cashPayment->changeAmount = $request->changeAmount;
                    $cashPayment->payment_method_id = $paymentMethod->id;
                    $cashPayment->save();
                    break;
            }

            return redirect()->route('method-payments.index')
                ->with('success', 'Método de pago registrado correctamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al registrar el método de pago: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type_payment' => 'required|in:sinpe,card,cash',
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
            // Find the payment method
            $paymentMethod = PaymentMethod::findOrFail($id);

            // Update main payment method data
            $paymentMethod->amount = $request->amount;
            $paymentMethod->type_payment = $request->type_payment;
            $paymentMethod->save();

            // Delete old child payment records from all types
            SinpePayment::where('payment_method_id', $id)->delete();
            CardPayment::where('payment_method_id', $id)->delete();
            CashPayment::where('payment_method_id', $id)->delete();

            // Create new child payment method based on the updated type
            switch ($request->type_payment) {
                case 'sinpe':
                    $sinpePayment = new SinpePayment();
                    $sinpePayment->voucher = $request->voucher;
                    $sinpePayment->payment_method_id = $paymentMethod->id;
                    $sinpePayment->save();
                    break;

                case 'card':
                    $cardPayment = new CardPayment();
                    $cardPayment->reference = $request->reference;
                    $cardPayment->payment_method_id = $paymentMethod->id; 
                    $cardPayment->save();
                    break;

                case 'cash':
                    $cashPayment = new CashPayment();
                    $cashPayment->changeAmount = $request->changeAmount;
                    $cashPayment->payment_method_id = $paymentMethod->id; 
                    $cashPayment->save();
                    break;
            }

            return redirect()->route('method-payments.index')
                ->with('success', 'Método de pago actualizado correctamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Método de pago no encontrado');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar el método de pago: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $payment = PaymentMethod::findOrFail($id);
            $payment->delete();

            return redirect()->route('method-payments.index')
                ->with('success', 'Método de pago eliminado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el método de pago: ' . $e->getMessage());
        }
    }

    /**
     * Get the label for payment type
     */
    public static function getPaymentTypeLabel($type)
    {
        $types = [
            'sinpe' => 'SINPE Móvil',
            'card' => 'Tarjeta',
            'cash' => 'Efectivo'
        ];

        return $types[$type] ?? $type;
    }
}