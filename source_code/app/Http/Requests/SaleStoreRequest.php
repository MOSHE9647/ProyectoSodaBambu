<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class SaleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user && ($user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::EMPLOYEE->value))) {
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
            function (Validator $validator): void {
                $saleDetails = $this->input('sale_details', []);
                $expectedTotal = round((float) $this->input('total', 0), 2);
                $detailsTotal = round(array_reduce($saleDetails, static function (float $carry, mixed $detail): float {
                    return $carry + (float) ($detail['sub_total'] ?? 0);
                }, 0.0), 2);

                if ($expectedTotal !== $detailsTotal) {
                    $validator->errors()->add('total', 'El total debe ser igual a la suma de los subtotales de los detalles de la venta.');
                }
            },
        ];
    }
}
