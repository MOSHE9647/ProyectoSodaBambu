<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Determina el ID del cliente actual para ignorarlo en la regla 'unique' del email.
        $clientParam = $this->route('client');
        $clientId = $clientParam instanceof Client ? $clientParam->id : $clientParam;


        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            // La regla unique ignora el ID del cliente actual para permitir actualizar sin cambiar el email.
            'email' => 'required|email|unique:clients,email,' . $clientId,
            'registration_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.string' => 'First name must be text.',
            'last_name.required' => 'Last name is required.',
            'last_name.string' => 'Last name must be text.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be valid.',
            'email.unique' => 'Email is already registered.',
            'phone.string' => 'Phone must be text.',
            'registration_date.date' => 'Registration date must be valid.',
        ];
    }
}