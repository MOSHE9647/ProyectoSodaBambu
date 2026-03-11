@extends('layouts.app')

@section('content')
<div class="container p-0">
    {{-- Page Header --}}
    <x-header title="Registro de Asistencia" subtitle="Registro de entradas/salidas, historial y cálculo de salarios" />

    {{-- Tabs Navigation --}}
    <x-tabs id="main-tab">
        <x-slot:buttons>
            <x-tabs.button target="nav-attendance" icon="bi bi-calendar-plus" :active="true">
                Registrar Horas
            </x-tabs.button>
            <x-tabs.button target="nav-history" icon="bi bi-clock-history">
                Ver Historial
            </x-tabs.button>
            <x-tabs.button target="nav-salary" icon="bi bi-currency-dollar">
                Calcular Salario
            </x-tabs.button>
        </x-slot:buttons>
    </x-tabs>

    <form id="employee-attendance-form" class="d-flex flex-column w-50 justify-content-start align-items-center" action="" method="POST">
        {{-- CSRF Token --}}
        @csrf
        @if(isset($user))
        @method('PUT')
        @endif

        {{-- Tabs Content --}}
        <x-tabs.content navId="main-tab">
            {{-- Attendance Section --}}
            <x-tabs.item id="nav-attendance" :active="true" icon="bi bi-calendar-plus" title="Registrar Horas Trabajadas">
                {{-- Attendance Form --}}
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

                <div class="row g-3 mt-1 p-2">
                    {{-- Start / End Time --}}
                    <x-tabs id="attendance-pills">
                        <x-slot:buttons>
                            <x-tabs.button target="nav-start" icon="bi bi-box-arrow-in-right" :active="true">
                                Entrada
                            </x-tabs.button>
                            <x-tabs.button target="nav-end" icon="bi bi-box-arrow-right">
                                Salida
                            </x-tabs.button>
                        </x-slot:buttons>
                    </x-tabs>

                    <x-tabs.content navId="attendance-pills" contentContainerClass="p-0 mt-0">
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
            </x-tabs.item>

            {{-- History Section --}}
            <x-tabs.item id="nav-history" icon="bi bi-clock-history" title="Historial de Asistencia" />

            {{-- Salary Calculation Section --}}
            <x-tabs.item id="nav-salary" icon="bi bi-currency-dollar" title="Cálcular Salario" />
        </x-tabs>
    </form>
</div>
@endsection
