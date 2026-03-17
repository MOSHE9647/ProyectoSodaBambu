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
        $marginInput = $this->input('margin_percentage');

        if (
            $this->isMethod('post')
            && $type === ProductType::MERCHANDISE->value
            && ($marginInput === null || $marginInput === '')
        ) {
            $marginInput = 0.35;
        }

        $this->merge([
            'tax_percentage' => $this->normalizePercentage($this->input('tax_percentage')),
            'margin_percentage' => $this->normalizePercentage($marginInput),
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

        $saleRules = [
            Rule::requiredIf(!$isMerchandise),
            'nullable',
            'numeric',
            'min:0',
            'regex:/^\d+(\.\d{1,2})?$/',
        ];

        if (!$isMerchandise) {
            $saleRules[] = 'gt:reference_cost';
        }

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'barcode' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->ignore($this->route('product'))
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ProductType::class)],
            'has_inventory' => ['required', 'boolean'],
            'reference_cost' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:1', 'regex:/^\d+(\.\d{1,2})?$/'],
            'margin_percentage' => ['required', 'numeric', 'min:0', 'max:1', 'regex:/^\d+(\.\d{1,2})?$/'],
            'sale_price' => $saleRules,
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'reference_cost.regex' => 'El costo de referencia debe tener máximo 2 decimales.',
            'tax_percentage.regex' => 'El impuesto debe tener máximo 2 decimales.',
            'margin_percentage.regex' => 'El margen debe tener máximo 2 decimales.',
            'sale_price.regex' => 'El precio de venta debe tener máximo 2 decimales.',
            'sale_price.gt' => 'El precio de venta debe ser mayor al costo de referencia.',
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