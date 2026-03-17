<div class="card-container rounded-2 p-4">
    <h5 class="text-muted pb-3 border-bottom border-secondary">
        <i class="bi bi-currency-dollar me-3"></i>
        Calcular Salario por Colaborador
    </h5>

    <form id="employee-salary-form" action="#" method="POST">
        {{-- CSRF Token --}}
        @csrf

        <div class="row g-3 mt-1">
            {{-- Employee --}}
            @php
                $oldEmployeeId = old('employee_id', '');
            @endphp
            
            <div class="col-6">
                <x-form.select :id="'employee_id'" :name="'employee_id'" :class="'border-secondary'" :selectClass="$errors->has('employee_id') ? 'is-invalid' : ''" :errorMessage="$errors->first('employee_id') ?? ''" :iconLeft="'bi bi-person'" :required="true">
                    Colaborador <span class="text-danger">*</span>
                    <x-slot:options>
                        <option value="-1">Seleccionar colaborador...</option>
                        @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $oldEmployeeId === $employee->id ? 'selected' : '' }}>
                            {{ $employee->user?->name ?? 'Colaborador sin nombre' }}
                        </option>
                        @endforeach
                    </x-slot:options>
                </x-form.select>
            </div>

            {{-- Date --}}
            <div class="col-6">
                <x-form.input :id="'payroll_period_display'" :type="'month'" :name="'payroll_period_display'" :class="'border-secondary'" :max="now('America/Costa_Rica')->format('Y-m')" :value="$currentMonth ?? now('America/Costa_Rica')->format('Y-m')" :selectClass="$errors->has('payroll_period') ? 'is-invalid' : ''" :errorMessage="$errors->first('payroll_period') ?? ''" :iconLeft="'bi bi-calendar-month'" :required="true">
                    Período <span class="text-danger">*</span>
                </x-form.input>
                <input id="payroll_period" name="payroll_period" type="hidden" value="{{ $currentMonth ?? now('America/Costa_Rica')->format('Y-m') }}">
            </div>

            {{-- Payroll Half --}}
            @php
                $paymentFrequencyIsBiweekly = true;
            @endphp
            @if ($paymentFrequencyIsBiweekly)
            <x-form.input.radio-group :label="'Quincena'" :groupClass="'row g-3'">
                <div class="col-6">
                    <x-form.input.radio-button :id="'first_half'" :name="'payroll_half'" :value="'first_half'" :checked="old('payroll_half') === 'first_half'">
                        <div class="d-flex flex-row gap-2 justify-content-center align-items-center">
                            Primera Quincena
                            <small class="text-muted">Días 1-15</small>
                        </div>
                    </x-form.input.radio-button>
                </div>
                <div class="col-6">
                    <x-form.input.radio-button :id="'second_half'" :name="'payroll_half'" :value="'second_half'" :checked="old('payroll_half') === 'second_half'">
                        <div class="d-flex flex-row gap-2 justify-content-center align-items-center">
                            Segunda Quincena
                            <small class="text-muted">Días 16-30</small>
                        </div>
                    </x-form.input.radio-button>
                </div>
            </x-form.input.radio-group>
            @endif
        </div>

        {{-- Submit Button --}}
        <div class="d-flex justify-content-end mt-4">
            <x-form.submit :id="'submit-salary-form-button'" :spinnerId="'submit-salary-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="'Calculando salario...'">
                <div id="submit-salary-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-currency-dollar me-2"></i>
                    Calcular Salario
                </div>
            </x-form.submit>
        </div>
    </form>
</div>

{{-- Salary Calculation Results --}}
@php
    $hasSalaryCalcResults = $employee !== null;

    function getInitials($name) {
        $initials = '';
        $nameParts = explode(' ', $name);
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }
        return $initials;
    }

    function escapeHtml($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
@endphp
@if($hasSalaryCalcResults)
@php
    $employeeName = escapeHtml($employee->user->name ?? 'Colaborador sin nombre');
    $employeeEmail = escapeHtml($employee->user->email ?? 'Sin correo');
    $employeeStatusLabel = $employee->status?->label() ?? 'Activo';
    $employeeIsActive = ($employee->status?->value ?? 'active') === 'active';

    $timesheets = $employee->timesheets ?? collect();

    $hourlyWageRaw = (float) ($employee->getRawOriginal('hourly_wage') ?? 0);
    $hourlyWageLabel = '₡' . number_format($hourlyWageRaw, 0, ',', ' ') . '/hr';

    $workedDays ??= 2;
    $totalHours ??= 16;
    $totalSalaryAmount ??= 40000;
@endphp
<div id="salary-calculation-result" class="card-container rounded-3 p-3 p-lg-4 mt-4">
    <div class="d-flex flex-column flex-xxl-row align-items-stretch justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center fs-5 fw-semibold bg-secondary-subtle" style="width: 3.4rem; height: 3.4rem; min-width: 3.4rem;">
                {{ getInitials($employeeName) }}
            </div>
            <div class="d-flex flex-column gap-1 justify-content-center">
                <span class="fw-semibold fs-5 lh-sm">{{ $employeeName }}</span>
                <small class="lh-sm">{{ $employeeEmail }}</small>
                <div class="d-flex align-items-center gap-1 mt-2">
                    <span class="badge border rounded-pill {{ !$employeeIsActive ? 'text-success-emphasis bg-success-subtle' : 'text-danger-emphasis bg-danger-subtle' }} px-3 py-2">
                        {{ $employeeStatusLabel }}
                    </span>
                    <span class="badge border rounded-pill text-info-emphasis bg-info-subtle px-3 py-2">
                        {{ $hourlyWageLabel }}
                    </span>
                </div>
            </div>
        </div>

        <div class="salary-stats d-flex flex-wrap gap-2 justify-content-start justify-content-xxl-end">
            <div class="stat-card rounded-3 border bg-secondary-subtle px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Días Trab.</small>
                <span class="fw-bold fs-4 lh-1">{{ $workedDays }}</span>
            </div>

            <div class="stat-card total-hours rounded-3 border bg-secondary-subtle px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Horas Totales</small>
                <span class="fw-bold fs-4 lh-1">{{ number_format((float) $totalHours) }}h</span>
            </div>

            <div class="stat-card total-salary rounded-3 border bg-secondary-subtle px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Salario Total</small>
                <span class="fw-bold fs-4 lh-1">₡{{ number_format((float) $totalSalaryAmount, 0, ',', ' ') }}</span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        <hr class="mt-4 mb-0 border-secondary">
        <span class="form-label mb-0">Desglose por Día</span>

        <div class="d-flex flex-column gap-2 justify-content-center align-items-center">
            @foreach ($timesheets as $ts)
                @php
                    $workDate = mb_convert_case(
                        str_replace('.', '', Carbon\Carbon::parse($ts->work_date)
                            ->locale('es')
                            ->timezone('America/Costa_Rica')
                            ->isoFormat('ddd, DD MMM')
                        ),
                        MB_CASE_TITLE,
                        'UTF-8'
                    );
                    $startTime = Carbon\Carbon::parse($ts->start_time)->timezone('America/Costa_Rica')->format('g:i A');
                    $endTime = Carbon\Carbon::parse($ts->end_time)->timezone('America/Costa_Rica')->format('g:i A') ?? 'N/A';
                    $totalHours = number_format((float) $ts->total_hours);
                    $salaryAmount = '₡' . number_format($ts->salary_amount ?? rand(100000, 10000000), 0, ',', ' ');
                @endphp

                <div class="d-flex flex-row w-100 align-items-center rounded-3 border bg-secondary-subtle px-4 py-3">
                    <span class="fw-semibold">{{ $workDate }}</span>
                    <div class="d-flex flex-row ms-auto gap-4 align-items-center">
                        <div class="d-flex flex-row gap-4 justify-content-between align-items-end">
                            <span class="text-muted">{{ $startTime }} → {{ $endTime }}</span>
                            <span class="badge border rounded-pill text-success-emphasis bg-success-subtle px-2 py-2">
                                <i class="bi bi-stopwatch"></i>
                                {{ $totalHours }}h
                            </span>
                        </div>
                        <span class="fw-bold" style="min-width: 150px; text-align: right;">{{ $salaryAmount }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif