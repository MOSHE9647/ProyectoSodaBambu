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
        ];
    }
}
