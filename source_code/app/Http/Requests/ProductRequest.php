<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProductRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     *
     * @return bool Always true, as authorization is handled elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepares the data for validation.
     *
     * - Trims and nullifies blank fields.
     * - Sets default margin for merchandise products on creation.
     * - Handles conditional logic for sale price and inventory fields.
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $isMerchandise = $type === ProductType::MERCHANDISE->value;
        $requiresManualSalePrice = in_array($type, [
            ProductType::DISH->value,
            ProductType::DRINK->value,
            ProductType::PACKAGED->value,
        ], true);

        $marginInput = $this->input('margin_percentage');

        // Assign default margin (35%) for merchandise creation if empty
        if ($this->isMethod('post') && $isMerchandise && blank($marginInput)) {
            $marginInput = 35;
        }

        $this->merge([
            'barcode' => blank($this->input('barcode')) ? null : trim((string) $this->input('barcode')),
            'reference_cost' => blank($this->input('reference_cost')) ? null : $this->input('reference_cost'),
            'sale_price' => blank($this->input('sale_price')) ? ($requiresManualSalePrice ? '' : null) : $this->input('sale_price'),
            'expiration_date' => $isMerchandise && ! blank($this->input('expiration_date')) ? $this->input('expiration_date') : null,
            'expiration_alert_days' => $isMerchandise && ! blank($this->input('expiration_alert_days')) ? $this->input('expiration_alert_days') : null,
            'tax_percentage' => $isMerchandise && ! blank($this->input('tax_percentage')) ? $this->input('tax_percentage') : null,
            'margin_percentage' => $isMerchandise && ! blank($marginInput) ? $marginInput : null,
            'current_stock' => blank($this->input('current_stock')) ? null : $this->input('current_stock'),
            'minimum_stock' => blank($this->input('minimum_stock')) ? null : $this->input('minimum_stock'),
        ]);
    }

    /**
     * Returns the validation rules that apply to the request.
     *
     * Rules are dynamically set based on product type and inventory requirements.
     *
     * @return array<string, ValidationRule|array<mixed>|string> Validation rules for each field.
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $isMerchandise = $type === ProductType::MERCHANDISE->value;
        $requiresManualSalePrice = in_array($type, [
            ProductType::DISH->value,
            ProductType::DRINK->value,
            ProductType::PACKAGED->value,
        ], true);

        $hasInventory = $this->boolean('has_inventory');

        return [
            // General information
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->ignore($this->route('product'))
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ProductType::class)],
            'has_inventory' => ['required', 'boolean'],

            // Expiration and alert dates
            'expiration_date' => [
                Rule::requiredIf($isMerchandise),
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'expiration_alert_days' => [
                Rule::requiredIf($isMerchandise),
                'nullable',
                'integer',
                'min:0',
            ],

            // Pricing (must be integer and multiple of 5)
            'reference_cost' => [
                Rule::requiredIf($isMerchandise),
                'nullable',
                'integer',
                'min:0',
                'multiple_of:5',
            ],
            'sale_price' => [
                Rule::requiredIf($requiresManualSalePrice),
                'nullable',
                'integer',
                'min:0',
                'multiple_of:5',
            ],

            // Percentages (must be integer between 0 and 100)
            'tax_percentage' => [
                Rule::requiredIf($isMerchandise),
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'margin_percentage' => [
                Rule::requiredIf($isMerchandise),
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            // Inventory
            'current_stock' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'minimum_stock' => [
                Rule::requiredIf($hasInventory),
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Adds custom validation logic after the main validation rules are applied.
     * Ensures that the sale price is greater than the reference cost if both are present.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $referenceCost = $this->input('reference_cost');
            $salePrice = $this->input('sale_price');

            // Ensure sale price is greater than reference cost
            if (! blank($referenceCost) && ! blank($salePrice)) {
                if ((int) $salePrice <= (int) $referenceCost) {
                    $validator->errors()->add('sale_price', 'El precio de venta debe ser mayor al costo de referencia.');
                }
            }
        });
    }

    /**
     * Returns custom validation messages.
     * UI-related messages are omitted from this documentation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'barcode.max' => 'El código de barras no puede exceder 255 caracteres.',
            'barcode.unique' => 'Ya existe un producto activo con este código de barras.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.max' => 'El nombre del producto no puede exceder 255 caracteres.',
            'type.required' => 'El tipo de producto es obligatorio.',
            'expiration_date.required' => 'La fecha de vencimiento es obligatoria para productos de mercadería.',
            'expiration_date.date' => 'La fecha de vencimiento debe tener un formato válido.',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento debe ser hoy o una fecha futura.',
            'expiration_alert_days.required' => 'Los días de alerta de vencimiento son obligatorios.',
            'expiration_alert_days.integer' => 'Los días de alerta de vencimiento deben ser un número entero.',
            'expiration_alert_days.min' => 'Los días de alerta de vencimiento no pueden ser menores a 0.',
            'has_inventory.required' => 'Debe indicar si el producto maneja inventario.',
            'has_inventory.boolean' => 'El valor de inventario no es válido.',

            // Mensajes de Precios
            'reference_cost.required' => 'El costo de referencia es obligatorio para mercadería.',
            'reference_cost.integer' => 'El costo de referencia no acepta decimales.',
            'reference_cost.min' => 'El costo de referencia no puede ser menor a 0.',
            'reference_cost.multiple_of' => 'El costo de referencia debe ser un múltiplo de 5.',

            'sale_price.required' => 'El precio de venta es obligatorio para este tipo de producto.',
            'sale_price.integer' => 'El precio de venta no acepta decimales.',
            'sale_price.min' => 'El precio de venta no puede ser menor a 0.',
            'sale_price.multiple_of' => 'El precio de venta debe ser un múltiplo de 5.',

            // Mensajes de Porcentajes
            'tax_percentage.required' => 'El impuesto es obligatorio para productos de mercadería.',
            'tax_percentage.integer' => 'El impuesto debe ser un número entero (Ej: 13).',
            'tax_percentage.min' => 'El impuesto no puede ser menor a 0.',
            'tax_percentage.max' => 'El impuesto no puede ser mayor a 100.',

            'margin_percentage.required' => 'El margen es obligatorio para productos de mercadería.',
            'margin_percentage.integer' => 'El margen debe ser un número entero (Ej: 35).',
            'margin_percentage.min' => 'El margen no puede ser menor a 0.',
            'margin_percentage.max' => 'El margen no puede ser mayor a 100.',

            // Mensajes de Inventario
            'current_stock.integer' => 'El stock actual debe ser un número entero.',
            'current_stock.min' => 'El stock actual no puede ser menor a 0.',
            'minimum_stock.required' => 'El stock mínimo es obligatorio cuando el producto maneja inventario.',
            'minimum_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'minimum_stock.min' => 'El stock mínimo no puede ser menor a 0.',
        ];
    }
}
