<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize optional values before validation.
     */
    protected function prepareForValidation(): void
    {
        $expirationDate = $this->input('expiration_date');

        $this->merge([
            'expiration_date' => $expirationDate === '' ? null : $expirationDate,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $supply = $this->route('supply');
        $supplyId = is_object($supply) ? $supply->id : $supply;

        $nameRule = Rule::unique('supplies', 'name')
            ->whereNull('deleted_at');

        if ($supplyId) {
            $nameRule->ignore($supplyId);
        }

        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$requiredOnCreate, 'string', 'max:50', $nameRule],
            'measure_unit' => [$requiredOnCreate, 'string', 'max:255'],
            'quantity' => [$requiredOnCreate, 'integer', 'min:0'],
            'unit_price' => [$requiredOnCreate, 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'name.max' => 'El nombre del insumo no puede exceder 50 caracteres.',
            'name.unique' => 'Ya existe un insumo activo con este nombre.',
            'measure_unit.required' => 'La unidad de medida es obligatoria.',
            'measure_unit.max' => 'La unidad de medida no puede exceder 255 caracteres.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad no puede ser menor a 0.',
            'unit_price.required' => 'El precio unitario es obligatorio.',
            'unit_price.numeric' => 'El precio unitario debe ser un número válido.',
            'unit_price.min' => 'El precio unitario no puede ser menor a 0.',
            'unit_price.regex' => 'El precio unitario debe tener máximo 2 decimales.',
            'expiration_date.date' => 'La fecha de vencimiento debe tener un formato válido.',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento debe ser hoy o una fecha futura.',
        ];
    }
}
