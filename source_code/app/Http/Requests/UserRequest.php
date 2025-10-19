<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
	        'name' => ['required', 'string', 'max:255'],
	        'email' => ['required', 'string', 'email', 'unique:users,email'],
	        'role' => ['required', new Enum(UserRole::class)],
	        'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
