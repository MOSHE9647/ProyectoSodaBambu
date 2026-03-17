<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'       => 'required|exists:categories,id',
            'barcode'           => 'nullable|string|max:50|unique:products,barcode,' . $this->route('product'),
            'name'              => 'required|string|max:255',
            'type'              => 'required|in:dish',
            'has_inventory'     => 'boolean',
            'reference_cost'    => 'nullable|numeric|min:0',
            'tax_percentage'    => 'nullable|numeric|min:0|max:100',
            'margin_percentage' => 'nullable|numeric|min:0|max:100',
            'sale_price'        => 'nullable|numeric|min:0',
        ];
    }
}