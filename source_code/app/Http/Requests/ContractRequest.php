<?php

namespace App\Http\Requests;

use App\Enums\MealTime;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\WeekDay;
use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class ContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $allowedRoles = [UserRole::ADMIN->value];
        if ($user && ($user->hasAnyRole($allowedRoles))) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Contract fields
            'id' => ['sometimes', 'integer', 'exists:contracts,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'business_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', $this->isMethod('POST') ? 'after_or_equal:today' : ''],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days_to_serve' => ['required', 'array', 'min:1'],
            'days_to_serve.*' => ['required', 'string', Rule::in(WeekDay::cases())],
            'portions_per_day' => ['required', 'integer', 'min:1'],
            'total_value' => ['required', 'integer', 'min:25'],
            'payment_status' => ['required', Rule::in(PaymentStatus::cases())],

            // ContractDetail fields
            'contract_details' => ['required', 'array', 'min:1'],
            'contract_details.*.id' => ['sometimes', 'integer', 'exists:contract_details,id'],
            'contract_details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'contract_details.*.meal_time' => ['required', 'string', Rule::in(MealTime::cases())],
            'contract_details.*.serve_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:end_date'],

            // PaymentDetail fields
            'payment_details' => ['present', 'array'],
            'payment_details.*.id' => ['sometimes', 'integer', 'exists:payment_details,id'],
            'payment_details.*.method' => ['required', new Enum(PaymentMethod::class)],
            'payment_details.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payment_details.*.change_amount' => ['numeric', 'min:0'],
            'payment_details.*.reference' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Sale fields
            'id.integer' => 'El ID del contrato debe ser un número entero.',
            'id.exists' => 'El contrato que intentas actualizar no existe.',

            'client_id.required' => 'Debe seleccionar un cliente.',
            'client_id.integer' => 'El ID del cliente debe ser un número entero.',
            'client_id.exists' => 'El cliente seleccionado no existe.',

            'business_name.required' => 'El nombre de la empresa es obligatorio.',
            'business_name.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'business_name.max' => 'El nombre de la empresa no puede exceder 255 caracteres.',

            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',

            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date' => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',

            'days_to_serve.required' => 'Debe seleccionar al menos un día de servicio.',
            'days_to_serve.array' => 'El campo días de servicio debe ser un arreglo.',
            'days_to_serve.min' => 'Debe seleccionar al menos un día de servicio.',

            'days_to_serve.*.required' => 'Cada día de servicio es obligatorio.',
            'days_to_serve.*.string' => 'Cada día de servicio debe ser una cadena de texto.',
            'days_to_serve.*.in' => 'Cada día de servicio debe ser un día válido de la semana.',

            'portions_per_day.required' => 'Debe ingresar la cantidad de porciones que se van a servir por día.',
            'portions_per_day.integer' => 'El campo porciones por día debe ser un número entero.',
            'portions_per_day.min' => 'Debe servir al menos 1 porción por día.',

            'total_value.required' => 'El valor total del contrato es obligatorio.',
            'total_value.integer' => 'El valor total del contrato debe ser un número entero.',
            'total_value.min' => 'El valor total del contrato debe ser de al menos 25 colones.',

            'payment_status.required' => 'El estado de pago es obligatorio.',
            'payment_status.in' => 'El estado de pago seleccionado no es válido.',

            // ContractDetail fields
            'contract_details.required' => 'Debe agregar al menos un detalle de contrato.',
            'contract_details.array' => 'El campo detalles de contrato debe ser un arreglo.',
            'contract_details.min' => 'Debe agregar al menos un detalle de contrato.',

            'contract_details.*.id.integer' => 'El ID del detalle de contrato debe ser un número entero.',
            'contract_details.*.id.exists' => 'El detalle de contrato que intentas actualizar no existe.',

            'contract_details.*.product_id.required' => 'Debe seleccionar un producto para cada detalle de contrato.',
            'contract_details.*.product_id.integer' => 'El ID del producto en los detalles de contrato debe ser un número entero.',
            'contract_details.*.product_id.exists' => 'El producto seleccionado en los detalles de contrato no existe.',

            'contract_details.*.meal_time.required' => 'Debe seleccionar un tiempo de comida para cada detalle de contrato.',
            'contract_details.*.meal_time.string' => 'El tiempo de comida en los detalles de contrato debe ser una cadena de texto.',
            'contract_details.*.meal_time.in' => 'El tiempo de comida seleccionado en los detalles de contrato no es válido.',

            'contract_details.*.serve_date.required' => 'Debe ingresar una fecha de servicio para cada detalle de contrato.',
            'contract_details.*.serve_date.date' => 'La fecha de servicio en los detalles de contrato debe ser una fecha válida.',
            'contract_details.*.serve_date.after_or_equal' => 'La fecha de servicio en los detalles de contrato no puede ser anterior a la fecha de inicio del contrato.',
            'contract_details.*.serve_date.before_or_equal' => 'La fecha de servicio en los detalles de contrato no puede ser posterior a la fecha de fin del contrato.',

            // PaymentDetail fields
            'payment_details.array' => 'Los detalles de pago deben ser un arreglo.',

            'payment_details.*.id.integer' => 'El ID del detalle de pago debe ser un número entero.',
            'payment_details.*.id.exists' => 'El detalle de pago que intentas actualizar no existe.',

            'payment_details.*.method.required' => 'Debe seleccionar un método de pago para cada detalle de pago.',
            'payment_details.*.method.enum' => 'El método de pago seleccionado en los detalles de pago no es válido.',

            'payment_details.*.amount.required' => 'Debe ingresar un monto para cada detalle de pago.',
            'payment_details.*.amount.numeric' => 'El monto en los detalles de pago debe ser un número válido.',
            'payment_details.*.amount.min' => 'El monto en los detalles de pago debe ser al menos 0.01 colones.',

            'payment_details.*.change_amount.numeric' => 'El monto de cambio en los detalles de pago debe ser un número válido.',
            'payment_details.*.change_amount.min' => 'El monto de cambio en los detalles de pago no puede ser negativo.',

            'payment_details.*.reference.string' => 'La referencia en los detalles de pago debe ser una cadena de texto.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateUniquenessConstraint(...),
            $this->validatePaymentIntegrity(...),
            $this->validateMethodSpecifics(...),
        ];
    }

    /**
     * Validates the uniqueness constraint for contract details.
     *
     * This method ensures that each combination of product, meal time, and service date
     * appears only once within the contract details. Duplicate combinations are flagged
     * as validation errors.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     *
     * @throws void (Adds validation errors to the validator instead of throwing)
     *
     * Rules enforced:
     * - Each unique combination of product_id, meal_time, and serve_date must appear only once
     * - If a duplicate combination is found, an error is added to the product_id field of that entry
     *
     * Error messages are added in Spanish to match application localization.
     */
    private function validateUniquenessConstraint(Validator $validator): void
    {
        $seen = [];

        foreach ($this->input('contract_details', []) as $index => $detail) {
            $productId = $detail['product_id'] ?? null;
            $mealTime = $detail['meal_time'] ?? null;
            $serveDate = $detail['serve_date'] ?? null;

            if (! $productId || ! $mealTime || ! $serveDate) {
                continue;
            }

            $key = $productId.'|'.$mealTime.'|'.$serveDate;

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "contract_details.$index.product_id",
                    'Cada combinación de producto, tiempo de comida y fecha de servicio debe ser única dentro del contrato.'
                );

                continue;
            }

            $seen[$key] = true;
        }
    }

    /**
     * Validates the integrity of payment information against the sale total.
     *
     * This method ensures that when a sale is marked as PAID, the payment details
     * are properly recorded and the total amount paid covers the sale total.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     *
     * @throws void (Adds validation errors to the validator instead of throwing)
     *
     * Rules enforced:
     * - If payment_status is PAID:
     *   - At least one payment record must be present in payment_details
     *   - The sum of all payment amounts must be >= total sale amount
     *
     * - If payment_status is PENDING:
     *   - No payment records should be present in payment_details
     *
     * Error messages are added in Spanish to match application localization.
     */
    private function validatePaymentIntegrity(Validator $validator): void
    {
        $status = $this->input('payment_status');
        $total = (float) $this->input('total_value', 0);

        $newPayments = collect($this->input('payment_details', []));
        $newPaidAmount = round($newPayments->sum('amount') - $newPayments->sum('change_amount'), 2);

        // Intentamos obtener el contrato desde la ruta o el input de forma segura
        $contractParam = $this->route('contract') ?? $this->route('id') ?? $this->input('id');

        $contract = null;
        if ($contractParam) {
            $contract = ($contractParam instanceof Contract)
                ? $contractParam->loadMissing('payments')
                : Contract::withTrashed()->with('payments')->find($contractParam);
        }

        $historicalPaidAmount = 0;
        if ($contract?->payments) {
            $historicalPaidAmount = (float) round($contract->payments->sum(fn ($p) => (float) $p->amount - (float) $p->change_amount), 2);
        }

        $totalPaid = $historicalPaidAmount + $newPaidAmount;

        // If the status is PAID, the total must be covered by the payments
        if ($status === PaymentStatus::PAID->value) {
            if ($totalPaid < $total) {
                $validator->errors()->add('payment_details', "Monto insuficiente para completar la venta (Pagado: ₡$totalPaid, Total: ₡$total).");
            }
        }

        // If the status is PENDING, there should be no NEW payments recorded
        if ($status === PaymentStatus::PENDING->value && ! $newPayments->isEmpty()) {
            $validator->errors()->add('payment_details', 'Una venta PENDIENTE no debería tener pagos nuevos registrados.');
        }
    }

    /**
     * Validates payment method-specific requirements for each payment detail.
     *
     * This method enforces validation rules that are specific to different payment methods:
     * - SINPE and CARD payments require a reference number that must be between 4 and 12 characters long
     * - CASH payments require a change_amount value that does not exceed the amount paid
     *
     * @param  Validator  $validator  The validator instance to which errors will be added
     */
    private function validateMethodSpecifics(Validator $validator): void
    {
        foreach ($this->input('payment_details', []) as $index => $payment) {
            $method = $payment['method'] ?? null;
            $amount = (float) ($payment['amount'] ?? 0);
            $change = (float) ($payment['change_amount'] ?? 0);

            // Obligatory Reference for electronic payments (SINPE/Card)
            $requiresRef = [PaymentMethod::SINPE->value, PaymentMethod::CARD->value];
            if (in_array($method, $requiresRef) && empty($payment['reference'])) {
                $validator->errors()->add("payment_details.$index.reference", 'La referencia es obligatoria para este método de pago.');
            }

            // Reference for electronic payments must be between 4 and 12 characters if provided
            if (in_array($method, $requiresRef) && ! empty($payment['reference'])) {
                $refLength = strlen($payment['reference']);
                if ($method === PaymentMethod::SINPE->value && ($refLength < 8 || $refLength > 12)) {
                    $validator->errors()->add("payment_details.$index.reference", 'El número de comprobante debe tener entre 8 y 12 caracteres.');
                } elseif ($method === PaymentMethod::CARD->value && ($refLength < 4 || $refLength > 12)) {
                    $validator->errors()->add("payment_details.$index.reference", 'El número de referencia debe tener entre 4 y 12 caracteres.');
                }
            }

            // If method is CASH, change_amount must be provided and cannot exceed the amount paid
            if ($method === PaymentMethod::CASH->value) {
                if (blank($payment['change_amount'])) {
                    $validator->errors()->add("payment_details.$index.change_amount", 'El monto de cambio es obligatorio para pagos en efectivo.');
                } elseif ($change > $amount) {
                    $validator->errors()->add("payment_details.$index.change_amount", 'El vuelto no puede ser mayor al monto entregado.');
                }
            }
        }
    }
}
