<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\SaleDetail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

use function in_array;
use function strlen;

class SaleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $allowedRoles = [UserRole::ADMIN->value, UserRole::EMPLOYEE->value];
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
            // Sale fields
            'id' => ['sometimes', 'integer', 'exists:sales,id'],
            'payment_status' => ['required', new Enum(PaymentStatus::class)],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:tomorrow'],
            'total' => ['required', 'numeric', 'min:0'],

            // Sale details array
            'sale_details' => ['required', 'array', 'min:1'],
            'sale_details.*.id' => ['sometimes', 'integer', 'exists:sale_details,id'],
            'sale_details.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'sale_details.*.quantity' => ['required', 'integer', 'min:1'],
            'sale_details.*.unit_price' => ['required', 'numeric', 'min:0'],
            'sale_details.*.applied_tax' => ['required', 'numeric', 'min:0'],
            'sale_details.*.sub_total' => ['required', 'numeric', 'min:0'],

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
            'id.integer' => 'El ID de la venta debe ser un número entero.',
            'id.exists' => 'La venta que intentas actualizar no existe.',

            'payment_status.required' => 'El estado de pago es obligatorio.',
            'payment_status.enum' => 'El estado de pago debe ser uno de los siguientes: '.implode(', ', array_map(fn ($case) => $case->value, PaymentStatus::cases())),

            'date.required' => 'La fecha de la venta es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.after_or_equal' => 'La fecha debe ser igual o posterior a hoy: '.now()->toDateString(),
            'date.before' => 'La fecha debe ser anterior a mañana: '.now()->addDay()->toDateString(),

            'total.required' => 'El total de la venta es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total no puede ser negativo.',

            'sale_details.required' => 'Los detalles de la venta son obligatorios.',
            'sale_details.array' => 'Los detalles de la venta deben ser un arreglo.',
            'sale_details.min' => 'Debe haber al menos un detalle de venta.',

            'sale_details.*.id.integer' => 'El ID del detalle de venta debe ser un número entero.',
            'sale_details.*.id.exists' => 'El detalle de venta que intentas actualizar no existe.',

            'sale_details.*.product_id.required' => 'El ID del producto es obligatorio en cada detalle.',
            'sale_details.*.product_id.integer' => 'El ID del producto debe ser un número entero.',
            'sale_details.*.product_id.exists' => 'El producto seleccionado no existe.',

            'sale_details.*.quantity.required' => 'La cantidad es obligatoria en cada detalle.',
            'sale_details.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'sale_details.*.quantity.min' => 'La cantidad debe ser al menos 1.',

            'sale_details.*.unit_price.required' => 'El precio unitario es obligatorio en cada detalle.',
            'sale_details.*.unit_price.numeric' => 'El precio unitario debe ser un número.',
            'sale_details.*.unit_price.min' => 'El precio unitario no puede ser negativo.',

            'sale_details.*.applied_tax.required' => 'El impuesto aplicado es obligatorio en cada detalle.',
            'sale_details.*.applied_tax.numeric' => 'El impuesto aplicado debe ser un número.',
            'sale_details.*.applied_tax.min' => 'El impuesto aplicado no puede ser negativo.',

            'sale_details.*.sub_total.required' => 'El subtotal es obligatorio en cada detalle.',
            'sale_details.*.sub_total.numeric' => 'El subtotal debe ser un número.',
            'sale_details.*.sub_total.min' => 'El subtotal no puede ser negativo.',

            'payment_details.array' => 'Los detalles de pago deben ser un arreglo.',

            'payment_details.*.method.required' => 'El método de pago es obligatorio en cada detalle de pago.',
            'payment_details.*.method.enum' => 'El método de pago debe ser uno de los siguientes: '.implode(', ', array_map(fn ($case) => $case->value, PaymentMethod::cases())),

            'payment_details.*.change_amount.numeric' => 'El monto de cambio debe ser un número.',
            'payment_details.*.change_amount.min' => 'El monto de cambio no puede ser negativo.',

            'payment_details.*.reference.string' => 'La referencia debe ser una cadena de texto.',
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
            $this->validateTotalMatchesDetails(...),
            $this->validatePaymentIntegrity(...),
            $this->validateMethodSpecifics(...),
            $this->validateProductUniqueness(...),
            $this->validateProductStockAvailabity(...),
        ];
    }

    /**
     * Validates that the sale total matches the sum of all sale details.
     *
     * This method ensures data integrity by comparing the provided total against
     * the calculated sum of all individual sale detail sub-totals. Both values are
     * rounded to 2 decimal places before comparison.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     *
     * @throws ValidationException If totals don't match
     */
    private function validateTotalMatchesDetails(Validator $validator): void
    {
        $total = round((float) $this->input('total', 0), 2);
        $detailsTotalWithTax = round(collect($this->input('sale_details', []))
            ->sum(fn ($d) => (float) ($d['sub_total'] ?? 0) + (($d['sub_total'] ?? 0) * ($d['applied_tax'] ?? 1))), 2);

        if ($total !== $detailsTotalWithTax) {
            $validator->errors()->add('total', "El total ($total) no coincide con la suma de los productos ($detailsTotalWithTax).");
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
        $total = (float) $this->input('total', 0);
        $payments = collect($this->input('payment_details', []));
        $paidAmount = round($payments->sum('amount'), 2);

        // If the status is PAID, the total must be covered by the payments
        if ($status === PaymentStatus::PAID->value) {
            if ($payments->isEmpty()) {
                $validator->errors()->add('payment_details', 'Debe registrar al menos un pago para marcar la venta como Completa.');
            } elseif ($paidAmount < $total) {
                $validator->errors()->add('payment_details', "Monto insuficiente para completar la venta (Pagado: $paidAmount, Total: $total).");
            }
        }

        // If the status is PENDING, there should be no payments recorded yet
        if ($status === PaymentStatus::PENDING->value && ! $payments->isEmpty()) {
            $validator->errors()->add('payment_details', 'Una venta PENDIENTE no debería tener pagos registrados aún.');
        }
    }

    /**
     * Validates that all products in the sale details are unique.
     *
     * Checks if there are duplicate product IDs in the sale_details array.
     * If duplicates are found, adds a validation error message instructing
     * the user to combine quantities into a single row instead of having
     * the same product in multiple rows.
     *
     * @param  Validator  $validator  The validator instance to add errors to
     */
    private function validateProductUniqueness(Validator $validator): void
    {
        $productIds = collect($this->input('sale_details', []))->pluck('product_id');

        if ($productIds->duplicates()->isNotEmpty()) {
            $validator->errors()->add('sale_details', 'Hay productos duplicados en los detalles. Por favor, suma las cantidades en una sola fila.');
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

    /**
     * Validates that requested product quantities are available in stock.
     *
     * This method checks the inventory availability for each product in the sale details.
     * It only validates products that have inventory tracking enabled. For update operations,
     * it accounts for the existing quantity being replaced by adding it back to available stock.
     *
     * @param  Validator  $validator  The validator instance to add error messages to
     *
     * @note Skips validation for:
     *       - Invalid or missing product IDs
     *       - Quantities less than or equal to zero
     *       - Products with inventory tracking disabled
     *
     * @example When updating a sale detail that previously had 5 units, and the new request is for 8 units,
     *          the available stock is calculated as: currentStock + 5 (existing quantity)
     */
    private function validateProductStockAvailabity(Validator $validator): void
    {
        foreach ($this->input('sale_details', []) as $index => $detail) {
            $productId = $detail['product_id'] ?? null;
            $requestedQty = (int) ($detail['quantity'] ?? 0);

            if (! $productId || $requestedQty <= 0) {
                continue;
            }

            $product = Product::with('stock')->find($productId);

            // Only validates if product has inventory tracking enabled
            if ($product?->has_inventory) {
                $currentStock = $product->stock?->current_stock ?? 0;
                $availableStock = $currentStock;

                // If this is an update (detail has ID), we need to add back the existing quantity to the available stock
                if (isset($detail['id'])) {
                    $existingDetail = SaleDetail::find($detail['id']);
                    if ($existingDetail && $existingDetail->product_id == $productId) {
                        $availableStock += $existingDetail->quantity;
                    }
                }

                if ($requestedQty > $availableStock) {
                    $validator->errors()->add(
                        "sale_details.$index.quantity",
                        "Stock insuficiente para '{$product->name}'. Disponible: $availableStock, Solicitado: $requestedQty."
                    );
                }
            }
        }
    }
}
