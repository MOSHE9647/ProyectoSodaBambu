<div class="card-container rounded-2 p-4">
    <h5 class="text-muted pb-3 border-bottom border-secondary">
        <i class="bi bi-currency-dollar me-3"></i>
        Calcular Salario por Colaborador
    </h5>

    @php
        $selectedEmployeeId = (string) request('employee_id', '-1');
        $selectedPayrollPeriod = data_get($employee, 'payroll_period', now('America/Costa_Rica')->format('Y-m'));
        $defaultPayrollHalf = now('America/Costa_Rica')->day <= 15 ? 'first_half' : 'second_half';
        $selectedPayrollHalf = (string) request('payroll_half', data_get($employee, 'payroll_half', $defaultPayrollHalf));
        $showPayrollHalf = $selectedEmployeeId !== '-1' && (bool) data_get($employee, 'is_biweekly', false);
    @endphp

    <form id="employee-salary-form" action="{{ route('attendance.tabs', ['tab' => 'salary']) }}" method="GET">
        <div class="row g-3 mt-1">
            <div class="col-6">
                <x-form.select :id="'salary-employee_id'" :name="'employee_id'" :class="'border-secondary'" :selectClass="$errors->has('employee_id') ? 'is-invalid' : ''" :errorMessage="$errors->first('employee_id') ?? ''" :iconLeft="'bi bi-person'" :required="true">
                    Colaborador <span class="text-danger">*</span>
                    <x-slot:options>
                        <option value="-1">Seleccionar colaborador...</option>
                        @foreach ($employees as $employeeOption)
                        <option
                            value="{{ $employeeOption->id }}"
                            data-payment-frequency="{{ $employeeOption->payment_frequency?->value }}"
                            {{ $selectedEmployeeId === (string) $employeeOption->id ? 'selected' : '' }}>
                            {{ $employeeOption->display_name }}
                        </option>
                        @endforeach
                    </x-slot:options>
                </x-form.select>
            </div>

            <div class="col-6">
                <x-form.input :id="'salary-payroll_period_display'" :type="'month'" :name="'payroll_period_display'" :class="'border-secondary'" :max="now('America/Costa_Rica')->format('Y-m')" :value="$selectedPayrollPeriod" :selectClass="$errors->has('payroll_period') ? 'is-invalid' : ''" :errorMessage="$errors->first('payroll_period') ?? ''" :iconLeft="'bi bi-calendar-month'" :required="true">
                    Periodo <span class="text-danger">*</span>
                </x-form.input>
                <input id="salary-payroll_period" name="payroll_period" type="hidden" value="{{ $selectedPayrollPeriod }}">
            </div>

            <div class="{{ $showPayrollHalf ? '' : 'd-none' }}" data-payroll-half-group>
                <x-form.input.radio-group :label="'Quincena'" :groupClass="'row g-3'">
                    <div class="col-6">
                        <x-form.input.radio-button :id="'salary-first_half'" :name="'payroll_half'" :value="'first_half'" :checked="$selectedPayrollHalf === 'first_half'">
                            <div class="d-flex flex-row gap-2 justify-content-center align-items-center">
                                Primera Quincena
                                <small class="text-muted">Dias 1-15</small>
                            </div>
                        </x-form.input.radio-button>
                    </div>
                    <div class="col-6">
                        <x-form.input.radio-button :id="'salary-second_half'" :name="'payroll_half'" :value="'second_half'" :checked="$selectedPayrollHalf === 'second_half'">
                            <div class="d-flex flex-row gap-2 justify-content-center align-items-center">
                                Segunda Quincena
                                <small class="text-muted">Dias 16-31</small>
                            </div>
                        </x-form.input.radio-button>
                    </div>
                </x-form.input.radio-group>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <x-form.submit :id="'salary-submit-form-button'" :spinnerId="'salary-submit-form-spinner'" :class="'btn-primary px-4'" :loadingMessage="'Calculando salario...'">
                <div id="salary-submit-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
                    <i class="bi bi-currency-dollar me-2"></i>
                    Calcular Salario
                </div>
            </x-form.submit>
        </div>
    </form>
</div>

@php
    $hasSalaryCalcResults =
        $selectedEmployeeId !== '-1'
        && filled($employee)
        && (string) data_get($employee, 'id') === $selectedEmployeeId;
    $timesheets = collect(data_get($employee, 'timesheets', []));
@endphp

@if($hasSalaryCalcResults)
<div id="salary-calculation-result" class="card-container rounded-3 p-3 p-lg-4 mt-4">
    <div class="d-flex flex-column flex-xxl-row align-items-stretch justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center fs-5 fw-semibold bg-secondary-subtle" style="width: 3.4rem; height: 3.4rem; min-width: 3.4rem;">
                {{ data_get($employee, 'initials', 'SN') }}
            </div>
            <div class="d-flex flex-column gap-1 justify-content-center">
                <span class="fw-semibold fs-5 lh-sm">{{ data_get($employee, 'name', 'Colaborador sin nombre') }}</span>
                <small class="lh-sm">{{ data_get($employee, 'email', 'Sin correo') }}</small>
                <div class="d-flex align-items-center gap-1 mt-2">
                    <span class="badge border rounded-pill {{ data_get($employee, 'is_active', true) ? 'text-success-emphasis bg-success-subtle' : 'text-danger-emphasis bg-danger-subtle' }} px-3 py-2">
                        {{ data_get($employee, 'status_label', 'Activo') }}
                    </span>
                    <span class="badge border rounded-pill text-info-emphasis bg-info-subtle px-3 py-2">
                        {{ data_get($employee, 'hourly_wage_label', 'CRC 0/hr') }}
                    </span>
                    <span class="badge border rounded-pill text-primary-emphasis bg-primary-subtle px-3 py-2">
                        {{ data_get($employee, 'payment_frequency_label', 'Sin definir') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="salary-stats d-flex flex-wrap gap-2 justify-content-start justify-content-xxl-end">
            <div class="stat-card rounded-3 border px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Dias Trab.</small>
                <span class="fw-bold fs-4 lh-1">{{ data_get($employee, 'worked_days', 0) }}</span>
            </div>

            <div class="stat-card total-hours rounded-3 border px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Horas Totales</small>
                <span class="fw-bold fs-4 lh-1">{{ data_get($employee, 'total_worked_hours_label', '0h') }}</span>
            </div>

            <div class="stat-card total-salary rounded-3 border px-4 py-3">
                <small class="text-uppercase text-muted fw-semibold d-block mb-1">Salario Total</small>
                <span class="fw-bold fs-4 lh-1">{{ data_get($employee, 'total_salary_amount_label', 'CRC 0') }}</span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        <hr class="mt-4 mb-0 border-secondary">

        @if ($timesheets->isEmpty())
        <div class="d-flex flex-column gap-3 mt-4 justify-content-center align-items-center">
            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
            <span class="text-muted">No se encontraron registros de asistencia para este colaborador en el periodo seleccionado.</span>
        </div>
        @else
        <span class="form-label mb-0">Desglose por Dia</span>

        <div class="d-flex flex-column gap-2 justify-content-center align-items-center">
            @foreach ($timesheets as $ts)
            <div class="d-flex flex-row w-100 align-items-center rounded-3 border px-4 py-3" style="background-color: var(--employee-salary-card-bg);">
                <div class="d-flex flex-row me-auto gap-4 justify-content-between align-items-center">
                    <span class="fw-semibold fs-6">{{ data_get($ts, 'work_date_label') }}</span>
                    @if (data_get($ts, 'is_holiday', false))
                    <span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2">
                        <i class="bi bi-stars me-1"></i>Feriado
                        <small>(x2)</small>
                    </span>
                    @endif
                </div>
                <div class="d-flex flex-row ms-auto gap-4 align-items-center">
                    <div class="d-flex flex-row gap-4 justify-content-between align-items-end">
                        <span class="text-muted">{{ data_get($ts, 'start_time_label', 'N/A') }} -> {{ data_get($ts, 'end_time_label', 'N/A') }}</span>
                        <span class="badge border rounded-pill text-success-emphasis bg-success-subtle px-2 py-2" style="width: 80px;">
                            <i class="bi bi-stopwatch"></i>
                            {{ data_get($ts, 'total_hours_label', '0h') }}
                        </span>
                    </div>
                    <span class="fw-bolder fs-6 {{ data_get($ts, 'is_holiday', false) ? 'text-warning-emphasis' : '' }}" style="min-width: 130px; text-align: right; {{ data_get($ts, 'is_holiday', false) ? '' : 'color: var(--bambu-logo-bg);' }}">
                        {{ data_get($ts, 'salary_amount_label', 'CRC 0') }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex flex-row total-salary-amount w-100 justify-content-between align-items-center rounded-3 border px-4 py-3">
            <div class="d-flex flex-row gap-2 justify-content-center align-items-start text-muted">
                <span>
                    <i class="bi bi-calendar-check me-1"></i>
                    {{ data_get($employee, 'worked_days', 0) }} dias trabajados
                </span>
                <span>&centerdot;</span>
                <span>
                    <i class="bi bi-stopwatch me-1"></i>
                    {{ data_get($employee, 'total_worked_hours_label', '0h') }} totales
                </span>
                @if (data_get($employee, 'includes_holiday_days', false))
                <span>&centerdot;</span>
                <span><i class="bi bi-stars me-1"></i>Incluye dias feriados</span>
                @endif
            </div>
            <span class="fw-bold fs-5" style="text-align: right;">Total a Pagar: {{ data_get($employee, 'total_salary_amount_label', 'CRC 0') }}</span>
        </div>
        @endif
    </div>
</div>
@else
<div id="salary-calculation-result-empty" class="card-container rounded-3 p-3 p-lg-4 mt-4">
    <div class="d-flex flex-column gap-3 justify-content-center align-items-center text-center">
        <i class="bi bi-person-badge fs-1 text-muted"></i>
        <span class="text-muted">Selecciona un colaborador y presiona "Calcular Salario" para ver el desglose.</span>
    </div>
</div>
@endif
