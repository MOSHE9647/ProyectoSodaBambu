<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimesheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize incoming payload before running validation.
     */
    protected function prepareForValidation(): void
    {
        $workDate = $this->input('work_date')
            ?? $this->input('attendance_date')
            ?? Carbon::now('America/Costa_Rica')->toDateString();

        $this->merge([
            'work_date' => $workDate,
            'end_time' => blank($this->input('end_time')) ? null : $this->input('end_time'),
            'is_holiday' => $this->has('is_holiday') ? $this->input('is_holiday') : false,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
            'work_date' => ['required', 'date', 'date_equals:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'is_holiday' => ['required', 'boolean'],
        ];
    }
}
