@extends('layouts.app')

@section('content')
    <div class="container p-0">
        {{-- Page Header --}}
        <x-header title="Registro de Asistencia" subtitle="Registro de entradas/salidas, historial y cálculo de salarios" />

        {{-- Tabs Navigation --}}
        <x-tabs id="main-tab" navClass="w-50 p-1 mb-3">
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

        {{-- Tabs Content --}}
        <x-tabs.content navId="main-tab" :container="false">
            {{-- Attendance Section --}}
            <x-tabs.item
                :active="true" 
                :container="true"
                :itemClass="'w-50'" 
                id="nav-attendance"
                icon="bi bi-calendar-plus" 
                title="Registrar Horas Trabajadas"
            >
                <div class="js-tab-lazy-content"
                    data-tab="attendance"
                    data-url="{{ route('attendance.tabs', ['tab' => 'attendance']) }}">
                    <x-alert type="info" message="Cargando contenido..." />
                </div>
            </x-tabs.item>

            {{-- History Section --}}
            <x-tabs.item
                :container="true"
                id="nav-history"
                icon="bi bi-clock-history"
                title="Historial de Asistencia"
            >
                <div class="js-tab-lazy-content mt-3"
                    data-tab="history"
                    data-url="{{ route('attendance.tabs', ['tab' => 'history']) }}">
                    <x-alert type="info" message="Abre esta pestaña para cargar el historial." />
                </div>
            </x-tabs.item>

            {{-- Salary Calculation Section --}}
            <x-tabs.item
                :container="true"
                id="nav-salary"
                itemClass="w-75"
                icon="bi bi-currency-dollar"
                title="Cálcular Salario"
            >
                <div class="js-tab-lazy-content"
                    data-tab="salary"
                    data-url="{{ route('attendance.tabs', ['tab' => 'salary']) }}">
                    <x-alert type="info" message="Abre esta pestaña para calcular el salario." />
                </div>
            </x-tabs.item>
        </x-tabs>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        window.AttendanceAppData = {
            initialTab: @json($activeTab ?? 'nav-attendance'),
        };
    </script>
    @vite(['resources/js/models/employees/main.js'])
@endsection