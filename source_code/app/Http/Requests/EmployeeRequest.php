<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule as ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->id : $routeUser;

        return self::rulesFor($userId ? (int) $userId : null);
    }

    /**
     * Reusable rules for employee data validation.
     *
     * @param  int|null  $userId  User id used to ignore unique phone on update.
     * @return array<string, ValidationRule|array|string>
     */
    public static function rulesFor(?int $userId = null): array
    {
        return [
            'phone' => [
                'required',
                'string',
                Rule::unique('employees', 'phone')
                    ->ignore($userId)
                    ->whereNull('deleted_at'),
                'min:12',
                'max:14',
            ],
            'status' => ['required', new Enum(EmployeeStatus::class)],
            'hourly_wage' => ['required', 'numeric', 'min:100', 'gt:0'],
            'payment_frequency' => ['required', new Enum(PaymentFrequency::class)],
        ];
    }
}
