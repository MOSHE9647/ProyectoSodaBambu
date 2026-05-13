<?php

namespace App\Http\Requests;

use App\Enums\MeasureUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Puedes agregar lógica de roles aquí si lo requieres en un futuro,
        // tal como en ContractRequest ($user->hasAnyRole(...))
        return true;
    }

    /**
     * Normalize optional values before validation.
     */
    protected function prepareForValidation(): void
    {
        $expirationDate = $this->input('expiration_date');

        $this->merge([
            'expiration_date' => blank($expirationDate) ? null : $expirationDate,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $supply = $this->route('supply');
        $supplyId = is_object($supply) ? $supply->id : $supply;

        $nameRule = Rule::unique('supplies', 'name')->whereNull('deleted_at');
        if ($supplyId) {
            $nameRule->ignore($supplyId);
        }

        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$requiredOnCreate, 'string', 'max:50', $nameRule],
            'quantity' => [$requiredOnCreate, 'integer', 'min:0'],
            'measure_unit' => [$requiredOnCreate, Rule::enum(MeasureUnit::class)],
            'measure_amount' => [$requiredOnCreate, 'numeric', 'min:0.01'],
            'unit_price' => [$requiredOnCreate, 'integer', 'min:5', 'multiple_of:5'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:'.now()->timezone('America/Costa_Rica')->toDateString()],
            'expiration_alert_days' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del insumo es obligatorio.',
            'name.max' => 'El nombre del insumo no puede exceder 50 caracteres.',
            'name.unique' => 'Ya existe un insumo activo con este nombre.',

            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad no puede ser menor a 0.',

            'measure_unit.required' => 'Debe seleccionar una unidad de medida.',
            'measure_unit.enum' => 'La unidad de medida seleccionada no es válida.',

            'measure_amount.required' => 'La cantidad por unidad es obligatoria.',
            'measure_amount.numeric' => 'La cantidad por unidad debe ser un número.',
            'measure_amount.min' => 'La cantidad por unidad debe ser mayor a 0.',

            'unit_price.required' => 'El precio unitario es obligatorio.',
            'unit_price.integer' => 'El precio unitario debe ser un número entero.',
            'unit_price.min' => 'El precio unitario no puede ser menor a 5.',
            'unit_price.multiple_of' => 'El precio debe ser múltiplo de 5 (ej. ₡5, ₡10, ₡50, ₡100).',

            'expiration_date.date' => 'La fecha de vencimiento debe tener un formato válido.',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento debe ser hoy o una fecha futura.',

            'expiration_alert_days.integer' => 'Los días de alerta de vencimiento deben ser un número entero.',
            'expiration_alert_days.min' => 'Los días de alerta de vencimiento no pueden ser menores a 0.',
        ];
    }
}
