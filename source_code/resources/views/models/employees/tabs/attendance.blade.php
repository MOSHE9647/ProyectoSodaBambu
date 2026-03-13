{{--
    Attendance Form - This form is designed to be included as a tab content in the attendance management section. 
    It allows for recording attendance details such as the employee, date, start and end times, and whether the day is a holiday.
--}}
<form id="employee-attendance-form" action="#" method="POST">
    {{-- CSRF Token --}}
    @csrf

    <div class="row g-3 mt-1">
        {{-- Employee --}}
        @php
            $oldEmployeeId = old('employee_id', $userEmployeeId ?? '');
        @endphp
        <div class="col-6">
            <x-form.select
                :id="'employee_id'" 
                :class="'border-secondary'"
                :selectClass="$errors->has('employee_id') ? 'is-invalid' : ''"
                :errorMessage="$errors->first('employee_id') ?? ''"
                :iconLeft="'bi bi-person'" 
                :required="true"
            >
                Empleado <span class="text-danger">*</span>
                <x-slot:options>
                    <option value="-1">Seleccionar empleado...</option>
                    @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" {{ $oldEmployeeId === $employee->id ? 'selected' : '' }}>
                        {{ $employee->user?->name ?? 'Empleado sin nombre' }}
                    </option>
                    @endforeach
                </x-slot:options>
            </x-form.select>
        </div>

        {{-- Date --}}
        <div class="col-6">
            <x-form.input 
				:id="'work_date_display'" 
                :type="'date'" 
				:name="'work_date_display'"
                :class="'border-secondary'" 
				:value="$todayDate ?? now('America/Costa_Rica')->toDateString()" 
                :selectClass="$errors->has('work_date') ? 'is-invalid' : ''" 
                :errorMessage="$errors->first('work_date') ?? ''" 
                :iconLeft="'bi bi-calendar-date'" 
                :disabled="true" 
                :readonly="true" 
                :required="true">
                Fecha
            </x-form.input>
			<input id="work_date" name="work_date" type="hidden" value="{{ $todayDate ?? now('America/Costa_Rica')->toDateString() }}">
        </div>
    </div>

    <x-alert id="attendance-complete-alert" type="warning" class="d-none mt-3 mb-0">
        El colaborador seleccionado ya tiene registrada su asistencia de hoy.
    </x-alert>

    @php
        $oldHoliday = old('is_holiday', '0');
    @endphp

    <x-form.input.radio-group :label="'¿Es feriado?'" :groupClass="'row g-3'" :labelClass="'mt-3'">
        <div class="col-6">
            <x-form.input.radio-button :id="'is_holiday_false'" :name="'is_holiday'" :value="'0'" :checked="$oldHoliday === '0'">
                <i class="bi bi-x-circle me-2"></i>
                No, no es feriado
            </x-form.input.radio-button>
        </div>

        <div class="col-6">
            <x-form.input.radio-button :id="'is_holiday_true'" :name="'is_holiday'" :value="'1'" :checked="$oldHoliday === '1'">
                <i class="bi bi-check-circle me-2"></i>
                Sí, es feriado
            </x-form.input.radio-button>
        </div>
    </x-form.input.radio-group>

    <div class="row g-3 p-2 mt-1">
        <x-alert id="attendance-start-time-added-alert" type="warning" class="d-none mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Entrada registrada a las 10:00 AM -- pendiente hora de salida
        </x-alert>

        {{-- Start / End Time --}}
        <x-tabs id="attendance-pills" navType="tabs" navContainerClass="p-0">
            <x-slot:buttons>
                <x-tabs.button target="nav-start" icon="bi bi-box-arrow-in-right" :active="true">
                    Entrada
                </x-tabs.button>
                <x-tabs.button target="nav-end" icon="bi bi-box-arrow-right">
                    Salida
                </x-tabs.button>
            </x-slot:buttons>
        </x-tabs>

        <x-tabs.content navId="attendance-pills" contentClass="p-0 mt-0 border-top-0 rounded-top-0 shadow-none">
            <x-tabs.item id="nav-start" :active="true">
                <x-form.input :id="'start_time'" :type="'time'" :class="'border-secondary'" :selectClass="$errors->has('start_time') ? 'is-invalid' : ''" :errorMessage="$errors->first('start_time') ?? ''" :iconLeft="'bi bi-clock'" :required="true">
                    Hora de Entrada
                    <span class="text-danger">*</span>
                </x-form.input>

                <p id="attendance-start-time-cant-be-modify" class="d-none text-muted mt-2 mb-0">
                    La hora de entrada ya fue registrada y no puede modificarse.
                </p>
            </x-tabs.item>

            <x-tabs.item id="nav-end">
                <x-form.input :id="'end_time'" :type="'time'" :class="'border-secondary'" :selectClass="$errors->has('end_time') ? 'is-invalid' : ''" :errorMessage="$errors->first('end_time') ?? ''" :iconLeft="'bi bi-clock'" :required="false">
                    Hora de Salida
                </x-form.input>

                <p id="attendance-end-time-cant-be-modify" class="d-none text-muted mt-2 mb-0">
                    La hora de salida ya fue registrada y no puede modificarse.
                </p>
            </x-tabs.item>
        </x-tabs.content>
    </div>

    <x-alert id="total-hours-info" type="success" class="d-none mt-3 mb-1" :showIcon="false">
        <div class="d-flex flex-row w-100 justify-content-between">
            <div>
                <span id="start-time">08:00 AM</span>
                <span>&RightArrow;</span>
                <span id="end-time">06:00 PM</span>
            </div>
            <span class="fw-bold" style="color: #22C55E;">
                <i class="bi bi-stopwatch me-1"></i>
                <span id="total-hours">12h trabajadas</span>
            </span>
        </div>
    </x-alert>

    <x-form.submit :id="'submit-attendance-form-button'" :spinnerId="'submit-attendance-form-spinner'" :class="'btn-primary w-100 mt-3'">
        <div id="submit-attendance-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
            <i class="bi bi-clipboard-check-fill me-2"></i>
            Registrar Asistencia
        </div>
    </x-form.submit>
</form>