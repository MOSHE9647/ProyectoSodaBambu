<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
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
	 * @return array<string, Rule|array|string>
	 */
	public function rules(): array
	{
		return [
			'id' => ['required', 'exists:users'],
			'phone' => ['required', 'string', 'unique:employees,phone', 'min:12', 'max:14'],
			'status' => ['required', new Enum(EmployeeStatus::class)],
			'hourlyWage' => ['required', 'numeric', 'min:100'],
			'paymentFrequency' => ['required', new Enum(PaymentFrequency::class)],
		];
	}
}
