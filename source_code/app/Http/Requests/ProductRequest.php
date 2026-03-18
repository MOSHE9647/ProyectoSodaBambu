<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize incoming percentage values to decimal format before validation.
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $isMerchandise = $type === ProductType::MERCHANDISE->value;
        $marginInput = $this->input('margin_percentage');
        $barcode = $this->input('barcode');

        if (
            $this->isMethod('post')
            && $isMerchandise
            && ($marginInput === null || $marginInput === '')
        ) {
            $marginInput = 0.35;
        }

        $taxInput = $this->input('tax_percentage');
        $referenceCost = $this->input('reference_cost');
        $salePrice = $this->input('sale_price');

        if ($type === ProductType::DRINK->value || $type === ProductType::PACKAGED->value) {
            $salePrice = 0;
        }

        $this->merge([
            'barcode' => $barcode === null || trim((string) $barcode) === '' ? null : trim((string) $barcode),
            'reference_cost' => $referenceCost === '' ? null : $referenceCost,
            'sale_price' => $salePrice === '' ? null : $salePrice,
            'tax_percentage' => $isMerchandise ? $this->normalizePercentage($taxInput) : null,
            'margin_percentage' => $isMerchandise ? $this->normalizePercentage($marginInput) : null,
            'current_stock' => $this->input('current_stock') === '' ? null : $this->input('current_stock'),
            'minimum_stock' => $this->input('minimum_stock') === '' ? null : $this->input('minimum_stock'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isMerchandise = $this->input('type') === ProductType::MERCHANDISE->value;
        $hasInventory = $this->boolean('has_inventory');

        $pricingRules = [
            Rule::requiredIf($isMerchandise),
            'nullable',
            'numeric',
            'min:0',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];

        $saleRules = [
            'nullable',
            'numeric',
            'min:0',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];

        return [
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
            'reference_cost' => $pricingRules,
            'tax_percentage' => [
                ...$pricingRules,
                'max:1',
            ],
            'margin_percentage' => [
                ...$pricingRules,
                'max:1',
            ],
            'sale_price' => $saleRules,
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
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isDish = $this->input('type') === ProductType::DISH->value;
            $referenceCost = $this->input('reference_cost');
            $salePrice = $this->input('sale_price');

            if (!$isDish || $referenceCost === null || $referenceCost === '' || $salePrice === null || $salePrice === '') {
                return;
            }

            if ((float) $salePrice <= (float) $referenceCost) {
                $validator->errors()->add('sale_price', 'El precio de venta debe ser mayor al costo de referencia.');
            }
        });
    }

    /**
     * Get custom validation messages.
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
            'has_inventory.required' => 'Debe indicar si el producto maneja inventario.',
            'has_inventory.boolean' => 'El valor de inventario no es válido.',
            'reference_cost.required' => 'El costo de referencia es obligatorio para productos de mercadería.',
            'reference_cost.numeric' => 'El costo de referencia debe ser un número válido.',
            'reference_cost.min' => 'El costo de referencia no puede ser menor a 0.',
            'reference_cost.regex' => 'El costo de referencia debe tener máximo 2 decimales.',
            'tax_percentage.required' => 'El impuesto es obligatorio para productos de mercadería.',
            'tax_percentage.numeric' => 'El impuesto debe ser un número válido.',
            'tax_percentage.min' => 'El impuesto no puede ser menor a 0.',
            'tax_percentage.max' => 'El impuesto debe estar entre 0 y 1.',
            'tax_percentage.regex' => 'El impuesto debe tener máximo 2 decimales.',
            'margin_percentage.required' => 'El margen es obligatorio para productos de mercadería.',
            'margin_percentage.numeric' => 'El margen debe ser un número válido.',
            'margin_percentage.min' => 'El margen no puede ser menor a 0.',
            'margin_percentage.max' => 'El margen debe estar entre 0 y 1.',
            'margin_percentage.regex' => 'El margen debe tener máximo 2 decimales.',
            'sale_price.numeric' => 'El precio de venta debe ser un número válido.',
            'sale_price.min' => 'El precio de venta no puede ser menor a 0.',
            'sale_price.regex' => 'El precio de venta debe tener máximo 2 decimales.',
            'sale_price.gt' => 'El precio de venta debe ser mayor al costo de referencia.',
            'current_stock.integer' => 'El stock actual debe ser un número entero.',
            'current_stock.min' => 'El stock actual no puede ser menor a 0.',
            'minimum_stock.required' => 'El stock mínimo es obligatorio cuando el producto maneja inventario.',
            'minimum_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'minimum_stock.min' => 'El stock mínimo no puede ser menor a 0.',
        ];
    }

    /**
     * Converts percentages greater than 1 (13, 35) into decimal values (0.13, 0.35).
     */
    private function normalizePercentage(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        if ($number > 1) {
            $number /= 100;
        }

        return round($number, 4);
    }
}