<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class PurchaseRequest extends FormRequest
{
    private const PURCHASABLE_TYPES = [
        'product' => 'products',
        'supply' => 'supplies',
    ];

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
            // Purchase fields
            'id' => ['sometimes', 'integer', 'exists:purchases,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number' => ['required', 'string', 'min:2', 'max:255', 'unique:purchases,invoice_number'],
            'payment_status' => ['required', new Enum(PaymentStatus::class)],
            'date' => ['required', 'before_or_equal:now'],
            'total' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Purchase details array
            'purchase_details' => ['required', 'array', 'min:1'],
            'purchase_details.*.id' => ['sometimes', 'integer', 'exists:purchase_details,id'],
            'purchase_details.*.quantity' => ['required', 'integer', 'min:1'],
            'purchase_details.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'purchase_details.*.sub_total' => ['required', 'numeric', 'min:0'],
            'purchase_details.*.purchasable_type' => ['required', 'string', Rule::in(array_keys(self::PURCHASABLE_TYPES))],
            'purchase_details.*.purchasable_id' => ['required', 'integer'],

            // Payment fields (optional, but if present must be valid)
            'payment_details' => ['sometimes', 'array'],
            'payment_details.*.id' => ['sometimes', 'integer', 'exists:payments,id'],
            'payment_details.*.method' => ['required', new Enum(PaymentMethod::class)],
            'payment_details.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payment_details.*.change_amount' => ['numeric', 'min:0'],
            'payment_details.*.reference' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id.integer' => 'El ID de la compra debe ser un número entero.',
            'id.exists' => 'La compra que intentas actualizar no existe.',

            'supplier_id.required' => 'El ID del proveedor es obligatorio.',
            'supplier_id.integer' => 'El ID del proveedor debe ser un número entero.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',

            'invoice_number.required' => 'El número de factura es obligatorio.',
            'invoice_number.string' => 'El número de factura debe ser una cadena de texto.',
            'invoice_number.min' => 'El número de factura debe tener al menos 2 caracteres.',
            'invoice_number.max' => 'El número de factura no puede exceder los 255 caracteres.',
            'invoice_number.unique' => 'El número de factura ya existe. Por favor, ingresa uno diferente.',

            'payment_status.required' => 'El estado de pago es obligatorio.',
            'payment_status.enum' => 'El estado de pago debe ser uno de los siguientes: ' . implode(', ', array_map(fn($case) => $case->value, PaymentStatus::cases())),

            'date.required' => 'La fecha de la compra es obligatoria.',
            'date.before_or_equal' => 'La fecha de la compra no puede ser futura.',

            'total.required' => 'El total de la compra es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total no puede ser negativo.',

            'notes.string' => 'Las notas deben ser una cadena de texto.',
            'notes.max' => 'Las notas no pueden superar los 1000 caracteres.',

            'purchase_details.required' => 'Los detalles de la compra son obligatorios.',
            'purchase_details.array' => 'Los detalles de la compra deben ser un arreglo.',
            'purchase_details.min' => 'Debe haber al menos un detalle de compra.',

            'purchase_details.*.id.integer' => 'El ID del detalle de compra debe ser un número entero.',
            'purchase_details.*.id.exists' => 'El detalle de compra que intentas actualizar no existe.',

            'purchase_details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
            'purchase_details.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'purchase_details.*.quantity.min' => 'La cantidad debe ser al menos 1.',

            'purchase_details.*.unit_price.required' => 'El precio unitario es obligatorio en cada detalle.',
            'purchase_details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
            'purchase_details.*.unit_price.min' => 'El precio unitario no puede ser negativo.',

            'purchase_details.*.sub_total.required' => 'El subtotal es obligatorio en cada detalle.',
            'purchase_details.*.sub_total.numeric' => 'El subtotal debe ser un número.',
            'purchase_details.*.sub_total.min' => 'El subtotal no puede ser negativo.',

            'purchase_details.*.purchasable_type.required' => 'El tipo de producto o suministro es obligatorio en cada detalle.',
            'purchase_details.*.purchasable_type.string' => 'El tipo de producto o suministro debe ser una cadena de texto.',
            'purchase_details.*.purchasable_type.in' => 'El tipo de producto o suministro debe ser uno de los siguientes: ' . implode(', ', array_keys(self::PURCHASABLE_TYPES)) . '.',

            'purchase_details.*.purchasable_id.required' => 'El ID del producto o suministro es obligatorio en cada detalle.',
            'purchase_details.*.purchasable_id.integer' => 'El ID del producto o suministro debe ser un número entero.',

            'payment_details.array' => 'Los detalles de pago deben ser un arreglo.',

            'payment_details.*.method.required' => 'El método de pago es obligatorio en cada detalle de pago.',
            'payment_details.*.method.enum' => 'El método de pago debe ser uno de los siguientes: ' . implode(', ', array_map(fn($case) => $case->value, PaymentMethod::cases())),

            'payment_details.*.change_amount.numeric' => 'El monto de cambio debe ser un número.',
            'payment_details.*.change_amount.min' => 'El monto de cambio no puede ser negativo.',

            'payment_details.*.reference.string' => 'La referencia debe ser una cadena de texto.',
        ];
    }

    /**
     * Get the "after" validation callables for the request
     * 
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            $this->validateTotalMatchesDetails(...),
            $this->validatePaymentIntegrity(...),
            $this->validatePurchasableIdsExist(...),
            $this->validatePurchasableUniqueness(...),
            $this->validateMethodSpecifics(...),
        ];
    }

    /**
     * Validates that the purchase total matches the sum of all purchase details.
     *
     * This method ensures data integrity by comparing the provided total against
     * the calculated sum of all individual purchase detail sub-totals. Both values are
     * rounded to 2 decimal places before comparison.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     *
     * @throws ValidationException If totals don't match
     */
    private function validateTotalMatchesDetails(Validator $validator): void
    {
        $total = round((float) $this->input('total', 0), 2);
        $detailsTotal = round(collect($this->input('purchase_details', []))
            ->sum(fn($d) => (float) ($d['sub_total'] ?? 0)), 2);

        if ($total !== $detailsTotal) {
            $validator->errors()->add('total', "El total ($total) no coincide con la suma de los productos ($detailsTotal).");
        }
    }

    /**
     * Validates the integrity of payment information against the purchase total.
     *
     * This method ensures that when a purchase is marked as PAID, the payment details
     * are properly recorded and the total amount paid covers the purchase total.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     *
     * @throws void (Adds validation errors to the validator instead of throwing)
     *
     * Rules enforced:
     * - If payment_status is PAID:
     *   - At least one payment record must be present in payment_details
     *   - The sum of all payment amounts must be >= total purchase amount
     *
     * - If payment_status is PENDING:
     *   - No payment records should be present in payment_details
     *
     * Error messages are added in Spanish to match application localization.
     */
    private function validatePaymentIntegrity(Validator $validator): void
    {
        $status = $this->input('payment_status');
        $total = (float) $this->input('total', 0);
        $payments = collect($this->input('payment_details', []));
        $paidAmount = round($payments->sum('amount'), 2);

        // If the status is PAID, the total must be covered by the payments
        if ($status === PaymentStatus::PAID->value) {
            if ($payments->isEmpty()) {
                $validator->errors()->add('payment_details', 'Debe registrar al menos un pago para marcar la compra como Completa.');
            } elseif ($paidAmount < $total) {
                $validator->errors()->add('payment_details', "Monto insuficiente para completar la compra (Pagado: $paidAmount, Total: $total).");
            }
        }

        // If the status is PENDING, there should be no payments recorded yet
        if ($status === PaymentStatus::PENDING->value && !$payments->isEmpty()) {
            $validator->errors()->add('payment_details', 'Una compra PENDIENTE no debería tener pagos registrados aún.');
        }
    }

    /**
     * Validates that each purchasable_id exists in its corresponding table
     * based on the provided purchasable_type for every purchase detail.
     *
     * The method maps the type to a table name using PURCHASABLE_TYPES and
     * checks database existence. If a record is not found, it adds a
     * field-specific validation error to the validator.
     *
     * @param  Validator  $validator  The validator instance used to collect errors
     */
    private function validatePurchasableIdsExist(Validator $validator): void
    {
        $details = $this->input('purchase_details', []);
        foreach ($details as $index => $detail) {
            $type = $detail['purchasable_type'] ?? null;
            $id = $detail['purchasable_id'] ?? null;

            if ($type && $id) {
                $table = self::PURCHASABLE_TYPES[$type] ?? null;
                if ($table && !\DB::table($table)->where('id', $id)->exists()) {
                    $capitalizedType = ucfirst($type);
                    $validator->errors()->add("purchase_details.$index.purchasable_id", "El ID del {$capitalizedType} con valor {$id} no existe. (Fila: $index)");
                }
            }
        }
    }

    /**
     * Validates that each purchasable item in the purchase details is unique.
     *
     * This method checks for duplicate combinations of purchasable_type and purchasable_id
     * within the purchase_details array. If duplicates are found, it adds a validation error
     * to the corresponding field in the validator, prompting the user to combine quantities
     * into a single row.
     *
     * @param Validator $validator The validator instance used to collect errors
     */
    private function validatePurchasableUniqueness(Validator $validator): void
    {
        collect($this->input('purchase_details', []))
            ->filter(fn($d) => filled($d['purchasable_type'] ?? null) && filled($d['purchasable_id'] ?? null))
            ->map(fn($d) => $d['purchasable_type'] . '_' . $d['purchasable_id'])
            ->duplicates()
            ->each(fn($val, $index) => $validator->errors()->add(
                "purchase_details.$index.purchasable_id",
                "Este producto/suministro está duplicado. Por favor, combina las cantidades en una sola fila."
            ));
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
