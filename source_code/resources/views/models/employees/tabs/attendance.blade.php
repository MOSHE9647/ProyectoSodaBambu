{{-- 
    Attendance Form - This form is designed to be included as a tab content in the attendance management section. 
    It allows for recording attendance details such as the employee, date, start and end times, and whether the day is a holiday.
--}}
<form id="employee-attendance-form" action="{{ route('attendance.store') }}" method="POST">
    {{-- CSRF Token --}}
    @csrf
    @if(isset($update) && $update)
        @method('PUT')
    @endif

    <div class="row g-3 mt-1">
        {{-- Employee --}}
        @php
            $oldEmployeeId = old('employee_id', $userEmployeeId ?? '');
        @endphp
        <div class="col-6">
            <x-form.select :id="'employee_id'" :class="'border-secondary'" :selectClass="$errors->has('employee_id') ? 'is-invalid' : ''" :errorMessage="$errors->first('employee_id') ?? ''" :iconLeft="'bi bi-person'" :required="true">
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
            <x-form.input :id="'attendance_date'" type="date" :class="'border-secondary'" :selectClass="$errors->has('attendance_date') ? 'is-invalid' : ''" :errorMessage="$errors->first('attendance_date') ?? ''" :iconLeft="'bi bi-calendar-date'" :required="true">
                Fecha <span class="text-danger">*</span>
            </x-form.input>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <x-form.input.check-button :id="'is_holiday'">
                <x-slot:label>¿Es Feriado?</x-slot:label>
                No, no es feriado
            </x-form.input.check-button>
        </div>
    </div>

    <div class="row g-3 p-2 mt-1">
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
                <x-form.input :id="'start_time'" type="time" :class="'border-secondary'" :selectClass="$errors->has('start_time') ? 'is-invalid' : ''" :errorMessage="$errors->first('start_time') ?? ''" :iconLeft="'bi bi-clock'" :required="true">
                    Hora de Entrada <span class="text-danger">*</span>
                </x-form.input>
            </x-tabs.item>

            <x-tabs.item id="nav-end">
                <x-form.input :id="'end_time'" type="time" :class="'border-secondary'" :selectClass="$errors->has('end_time') ? 'is-invalid' : ''" :errorMessage="$errors->first('end_time') ?? ''" :iconLeft="'bi bi-clock'">
                    Hora de Salida
                </x-form.input>
            </x-tabs.item>
        </x-tabs.content>
    </div>

    <x-form.submit 
        :id="'submit-attendance-form-button'"
        :spinnerId="'submit-attendance-form-spinner'"
        :class="'btn-primary w-100 mt-3'"
    >
        <div id="submit-attendance-form-button-text" class="d-flex flex-row align-items-center justify-content-center">
            <i class="bi bi-clipboard-check-fill me-2"></i>
            Registrar Asistencia
        </div>
    </x-form.submit>
</form>
