<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
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
        $clientId = $this->route('client')?->id;

        $emailRule = Rule::unique('clients', 'email')
            ->whereNull('deleted_at');

        if ($clientId) {
            $emailRule->ignore($clientId);
        }

        $requiredOnCreate = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'first_name' => [$requiredOnCreate, 'string', 'max:255'],
            'last_name' => [$requiredOnCreate, 'string', 'max:255'],
            'phone' => 'nullable|string|max:20',
            'email' => [$requiredOnCreate, 'email', $emailRule],
        ];
    }
}
